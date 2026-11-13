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

use stdClass;

/**
 * Areas inside a section: a «Text and media area» whose content is marked as an area.
 *
 * In edit mode an area is an ordinary label, so the teacher moves it and the activities around it
 * with Moodle's own drag and drop. On the course page the theme turns each one into a heading
 * that groups the activities that follow it, up to the next area. Sections, restrictions and
 * completion are untouched: a label has no completion and is not a step of the section.
 *
 * @package    theme_rsmax
 * @copyright  2026 RSMAX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_areas {
    /** Class that marks the content of a label as an area. */
    const MARKER = 'rsmax-area';

    /**
     * The areas of a course, in course order, as the course page needs them.
     *
     * @param stdClass $course
     * @return array of ['id' => cmid, 'label' => text]
     */
    public static function for_course(stdClass $course): array {
        $areas = [];
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname !== 'label' || !$cm->uservisible || $cm->deletioninprogress) {
                continue;
            }
            $label = self::label_of($cm);
            if ($label !== null) {
                $areas[] = ['id' => (int) $cm->id, 'label' => $label];
            }
        }
        return $areas;
    }

    /**
     * The name of an area, or null when the label is not one.
     *
     * @param \cm_info $cm
     * @return string|null
     */
    public static function label_of(\cm_info $cm): ?string {
        $content = (string) $cm->content;
        if (strpos($content, self::MARKER) === false) {
            return null;
        }
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return $text === '' ? null : \core_text::substr($text, 0, 120);
    }

    /**
     * Add an area to a section, at its start or just before an activity.
     *
     * @param stdClass $course
     * @param int $sectionnum
     * @param string $name
     * @param int|null $beforecmid
     * @return int the id of the new course module
     */
    public static function create(stdClass $course, int $sectionnum, string $name, ?int $beforecmid = null): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('An area needs a name.');
        }
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info($sectionnum, MUST_EXIST);
        if ($beforecmid === null) {
            // At the start of the section, where the teacher sees it and drags it to its place.
            $first = $modinfo->sections[$sectionnum][0] ?? null;
            $beforecmid = $first ? (int) $first : null;
        }

        $moduleinfo = new stdClass();
        $moduleinfo->modulename = 'label';
        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'label'], MUST_EXIST);
        $moduleinfo->course = $course->id;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->completion = COMPLETION_TRACKING_NONE;
        $moduleinfo->introeditor = [
            'text' => '<div class="' . self::MARKER . '">' . s($name) . '</div>',
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->name = $name;
        $moduleinfo->cmidnumber = '';
        $moduleinfo = add_moduleinfo($moduleinfo, $course);

        if ($beforecmid) {
            \core_courseformat\formatactions::cm($course->id)->move_before((int) $moduleinfo->coursemodule, $beforecmid);
        }
        rebuild_course_cache($course->id, true);
        return (int) $moduleinfo->coursemodule;
    }
}
