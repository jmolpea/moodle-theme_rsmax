<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_rsmax\local;

use completion_info;
use stdClass;

/**
 * Works out how far a learner is through a course, overall and section by section.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_progress {
    /**
     * Returns the progress of a user in a course.
     *
     * Activities with completion tracking that the user can see are counted, and so are those of
     * sections that are still locked but shown to the learner as not available yet: the total is
     * the whole of what the course asks, not only what is open today. A section or an activity
     * hidden from the learner does not count.
     *
     * @param stdClass $course The course record.
     * @param int $userid The learner.
     * @return array|null Null when there is nothing to show (completion off, user not tracked or
     *     no tracked activities); otherwise percentage, completed, total, sections, bysection
     *     (completed and total by section id, subsections included) and next (the first activity
     *     still to do, or false).
     */
    public static function get_for_user(stdClass $course, int $userid): ?array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new completion_info($course);
        if (!$completion->is_enabled() || !$completion->is_tracked_user($userid)) {
            return null;
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $untracked = self::untracked($modinfo);
        $completed = 0;
        $total = 0;
        $sections = [];
        $bysection = [];
        $next = null;
        foreach ($modinfo->get_section_info_all() as $section) {
            if (empty($modinfo->sections[$section->section])) {
                continue;
            }
            // A locked section counts when the learner sees it, greyed, with what opens it.
            $locked = !$section->uservisible;
            if ($locked && (!$section->visible || empty($section->availableinfo))) {
                continue;
            }
            $sectioncompleted = 0;
            $sectiontotal = 0;
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                $counts = $locked ? $cm->visible && $cm->visibleoncoursepage : $cm->uservisible;
                if (!$counts || $cm->deletioninprogress || !$completion->is_enabled($cm) || isset($untracked[(int) $cm->id])) {
                    continue;
                }
                $sectiontotal++;
                $state = $completion->get_data($cm, true, $userid)->completionstate;
                if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                    $sectioncompleted++;
                } else if ($next === null && !$locked && $cm->url) {
                    // The first activity still to do, in course order, is where the learner goes next.
                    $next = ['id' => (int) $cm->id, 'name' => $cm->get_formatted_name(), 'url' => $cm->url->out(false)];
                }
            }
            $completed += $sectioncompleted;
            $total += $sectiontotal;
            if ($sectiontotal) {
                $bysection[$section->id] = ['completed' => $sectioncompleted, 'total' => $sectiontotal];
            }
            // Subsections count towards the course total but are not listed on their own.
            if ($sectiontotal && !$section->is_delegated()) {
                $sections[] = [
                    'id' => (int) $section->id,
                    'sectionnumber' => (int) $section->section,
                    'islocked' => $locked,
                    'name' => get_section_name($course, $section),
                    'url' => course_get_url($course, $section->section)->out(false),
                    'completed' => $sectioncompleted,
                    'total' => $sectiontotal,
                    'percentage' => (int) floor($sectioncompleted * 100 / $sectiontotal),
                    'iscomplete' => $sectioncompleted == $sectiontotal,
                    'isstarted' => $sectioncompleted > 0,
                ];
            }
        }
        // Classroom: return to the most recently viewed unfinished activity when it is
        // still available. The normal course-order suggestion remains the fallback.
        if (classroom::enabled((int) $course->id) && $next !== null) {
            $recent = self::recent_unfinished($course, $userid, $modinfo, $completion, $untracked);
            if ($recent !== null) {
                $next = $recent;
            }
        }

        if (!$total) {
            return null;
        }

        return [
            'percentage' => (int) floor($completed * 100 / $total),
            'underone' => $completed > 0 && $completed * 100 < $total,
            'completed' => $completed,
            'total' => $total,
            'remaining' => $total - $completed,
            'iscomplete' => $completed == $total,
            'sections' => $sections,
            'bysection' => $bysection,
            'next' => $next ?? false,
        ];
    }

    /**
     * Finds the activity the learner viewed last that is accessible, tracked and still unfinished.
     *
     * @param stdClass $course The course.
     * @param int $userid The learner.
     * @param \course_modinfo $modinfo The activities of the course, as the learner sees them.
     * @param completion_info $completion The completion of the course.
     * @param array $untracked Ids of the activities that do not count as steps.
     * @return array|null The activity, as the next step of the course, or null when there is none.
     */
    private static function recent_unfinished(
        stdClass $course,
        int $userid,
        $modinfo,
        completion_info $completion,
        array $untracked = []
    ): ?array {
        global $DB;
        if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
            return null;
        }
        $events = $DB->get_records_select(
            'logstore_standard_log',
            'courseid = :courseid AND userid = :userid AND contextlevel = :level AND action = :action',
            ['courseid' => $course->id, 'userid' => $userid, 'level' => CONTEXT_MODULE, 'action' => 'viewed'],
            'timecreated DESC, id DESC',
            'id, contextinstanceid',
            0,
            200
        );
        foreach ($events as $event) {
            try {
                $cm = $modinfo->get_cm((int) $event->contextinstanceid);
            } catch (\moodle_exception $e) {
                continue;
            }
            if (
                !$cm->uservisible || $cm->deletioninprogress || !$cm->url || !$completion->is_enabled($cm)
                    || isset($untracked[(int) $cm->id])
            ) {
                continue;
            }
            $state = $completion->get_data($cm, true, $userid)->completionstate;
            if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                continue;
            }
            return ['id' => (int) $cm->id, 'name' => $cm->get_formatted_name(), 'url' => $cm->url->out(false)];
        }
        return null;
    }

    /**
     * Activities kept out of the progress count with the tag «progreso:no» (or «progress:no»).
     *
     * For an activity whose completion mark is a switch and not a step: «choose your path» is ticked only by
     * those who take the technical path, so for everybody else it would stay pending for ever and the section
     * would never show as completed.
     *
     * @param \course_modinfo $modinfo
     * @return array cmid => true
     */
    private static function untracked($modinfo): array {
        $ids = array_keys($modinfo->get_cms());
        if (!$ids) {
            return [];
        }
        $out = [];
        foreach (\core_tag_tag::get_items_tags('core', 'course_modules', $ids) as $cmid => $tags) {
            foreach ($tags as $tag) {
                if (preg_match('/^(progreso|progress)\s*:\s*(no|off)$/u', trim(\core_text::strtolower($tag->rawname)))) {
                    $out[(int) $cmid] = true;
                }
            }
        }
        return $out;
    }
}
