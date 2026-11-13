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

/**
 * The type of each activity in words and its duration, as the classroom shows them in every list.
 *
 * The type comes from the kind of activity (a page is a reading, a task is assessed...). Teachers
 * change it, and add the duration, with tags on the activity: «tipo:evaluable», «tipo:hito»,
 * «duracion:30min», «duracion:1.5h». English tags work too: «type:milestone», «duration:2h».
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_meta {
    /** Words accepted after «tipo:» and the type each one means. */
    const KINDS = [
        'leccion' => 'lesson', 'lesson' => 'lesson',
        'practica' => 'practice', 'practice' => 'practice',
        'evaluable' => 'assessed', 'assessed' => 'assessed', 'graded' => 'assessed',
        'hito' => 'milestone', 'milestone' => 'milestone',
        'lectura' => 'reading', 'reading' => 'reading',
        'foro' => 'forum', 'forum' => 'forum',
        'encuesta' => 'survey', 'survey' => 'survey',
        'activacion' => 'warmup', 'warmup' => 'warmup',
        'cierre' => 'wrapup', 'wrapup' => 'wrapup',
        'material' => 'material', 'materials' => 'material',
        'glosario' => 'glossary', 'glossary' => 'glossary',
        'certificado' => 'certificate', 'certificate' => 'certificate',
    ];

    /** Type of each kind of activity when it has no tag. */
    const DEFAULTS = [
        'page' => 'reading', 'book' => 'reading',
        'resource' => 'material', 'url' => 'material', 'folder' => 'material', 'imscp' => 'material',
        'assign' => 'assessed', 'workshop' => 'assessed',
        'lesson' => 'lesson', 'h5pactivity' => 'practice', 'scorm' => 'practice',
        'forum' => 'forum', 'glossary' => 'glossary',
        'feedback' => 'survey', 'questionnaire' => 'survey', 'choice' => 'survey', 'survey' => 'survey',
        'customcert' => 'certificate', 'certificate' => 'certificate',
    ];

    /** Types with a colour of their own in the lists. */
    const MODIFIERS = ['assessed' => 'ev', 'milestone' => 'hito', 'wrapup' => 'cierre'];

    /** @var array cache by course id: cmid => [label, duration, modifier] */
    private static $cache = [];

    /**
     * Type, duration and colour of every activity of a course.
     *
     * @param \stdClass $course
     * @return array cmid => [type in words, duration in words, modifier]
     */
    public static function for_course(\stdClass $course): array {
        global $DB;
        if (isset(self::$cache[$course->id])) {
            return self::$cache[$course->id];
        }
        $modinfo = get_fast_modinfo($course);
        $cms = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname !== 'label' && $cm->modname !== 'subsection' && !$cm->deletioninprogress) {
                $cms[(int) $cm->id] = $cm;
            }
        }
        $tags = $cms ? \core_tag_tag::get_items_tags('core', 'course_modules', array_keys($cms)) : [];
        $quizgrades = isset($modinfo->instances['quiz'])
            ? $DB->get_records_menu('quiz', ['course' => $course->id], '', 'id, grade') : [];

        $result = [];
        foreach ($cms as $cmid => $cm) {
            $kind = null;
            $duration = '';
            foreach ($tags[$cmid] ?? [] as $tag) {
                [$key, $value] = self::read_tag($tag->rawname);
                if ($key === 'kind') {
                    $kind = $value;
                } else if ($key === 'duration') {
                    $duration = $value;
                }
            }
            if ($kind === null) {
                $kind = self::DEFAULTS[$cm->modname] ?? null;
                if ($cm->modname === 'quiz') {
                    $kind = (float) ($quizgrades[$cm->instance] ?? 0) > 0 ? 'assessed' : 'practice';
                }
            }
            if ($kind === null) {
                $label = get_string('modulename', $cm->modname);
            } else if (in_array($kind, self::KINDS, true)) {
                $label = get_string('kind:' . $kind, 'theme_rsmax');
            } else {
                $label = $kind;
            }
            $result[$cmid] = [$label, $duration, self::MODIFIERS[$kind] ?? ''];
        }
        self::$cache[$course->id] = $result;
        return $result;
    }

    /**
     * Type, duration and colour of one activity.
     *
     * @param \cm_info $cm
     * @return array|null [type in words, duration in words, modifier]
     */
    public static function for_cm(\cm_info $cm): ?array {
        return self::for_course($cm->get_course())[(int) $cm->id] ?? null;
    }

    /**
     * Reads a tag: «tipo:hito» gives the type, «duracion:1.5h» the duration.
     *
     * @param string $raw Name of the tag as it was typed.
     * @return array [kind|duration|null, value]
     */
    public static function read_tag(string $raw): array {
        $text = trim(\core_text::strtolower($raw));
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        if (!preg_match('/^(tipo|type|duracion|duration)\s*:\s*(.+)$/u', $text, $m)) {
            return [null, ''];
        }
        if ($m[1] === 'tipo' || $m[1] === 'type') {
            $word = trim($m[2]);
            if (isset(self::KINDS[$word])) {
                return ['kind', self::KINDS[$word]];
            }
            // A type of the teacher's own: shown as it was typed, after the colon.
            $own = trim(explode(':', $raw, 2)[1]);
            return ['kind', \core_text::strtoupper(\core_text::substr($own, 0, 1)) . \core_text::substr($own, 1)];
        }
        if (!preg_match('/^(\d+(?:[.,]\d+)?)\s*(min|m|h)$/u', str_replace(' ', '', $m[2]), $d)) {
            return [null, ''];
        }
        $number = (float) str_replace(',', '.', $d[1]);
        $shown = format_float($number, (floor($number) == $number) ? 0 : 1, true, true);
        return ['duration', $shown . ($d[2] === 'h' ? ' h' : ' min')];
    }

    /**
     * Name with its leading code shortened: «Lección 0.2 · » becomes «0.2 · » and «Hito H1 · » becomes «H1 · ».
     *
     * The code stays because lessons and tasks refer to each other by it.
     *
     * @param string $name
     * @return string
     */
    public static function clean(string $name): string {
        return preg_replace('/^(?:Lección |Hito )(?=(\d+(\.\d+)*|H\d+)\s*·)/u', '', $name);
    }
}
