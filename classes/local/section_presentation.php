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

use core\context\course as context_course;
use core\url;
use stdClass;

/**
 * The presentation of a module (a section of the course): a photo or a video, a text beside it
 * and «About this module», filled in with fields and drawn the same way in every course.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_presentation {
    /** File area of the photo or the video (item id: the section). */
    const AREA_MEDIA = 'sectionmedia';

    /** File areas of the texts (item id: the section). */
    const AREA_TEXTS = ['description' => 'sectiondescription', 'transcript' => 'sectiontranscript', 'about' => 'sectionabout'];

    /** Fields of a presentation. */
    const FIELDS = ['heading', 'description', 'descriptionformat', 'transcript', 'transcriptformat', 'videourl',
        'videoplaceholder', 'abouttitle', 'aboutsummary', 'about', 'aboutformat'];

    /**
     * Options of the file picker of the photo or the video. A video can go with its subtitles in a .vtt file.
     *
     * @return array
     */
    public static function media_options(): array {
        return ['subdirs' => 0, 'maxfiles' => 2, 'maxbytes' => 0, 'accepted_types' => ['web_image', 'web_video', '.vtt']];
    }

    /**
     * Whether a file of the media area holds the subtitles of the video.
     *
     * @param \stored_file $file
     * @return bool
     */
    private static function is_captions(\stored_file $file): bool {
        return strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) === 'vtt';
    }

    /**
     * Options of the text editors.
     *
     * @param stdClass $course
     * @return array
     */
    public static function editor_options(stdClass $course): array {
        return ['maxfiles' => EDITOR_UNLIMITED_FILES, 'subdirs' => 0, 'noclean' => false, 'trusttext' => false,
            'context' => context_course::instance($course->id)];
    }

    /**
     * The saved presentation of a section, or null.
     *
     * @param int $sectionid
     * @return stdClass|null
     */
    public static function get(int $sectionid): ?stdClass {
        global $DB;
        return $DB->get_record('theme_rsmax_section', ['sectionid' => $sectionid]) ?: null;
    }

    /**
     * Saves the presentation of a section.
     *
     * @param stdClass $course
     * @param int $sectionid
     * @param stdClass $data Fields of the presentation (texts already in their final form).
     * @return stdClass the record
     */
    public static function save(stdClass $course, int $sectionid, stdClass $data): stdClass {
        global $DB;
        $record = self::get($sectionid) ?? (object) ['courseid' => $course->id, 'sectionid' => $sectionid];
        foreach (self::FIELDS as $field) {
            if (property_exists($data, $field)) {
                $record->$field = $data->$field;
            }
        }
        $record->timemodified = time();
        if (empty($record->id)) {
            $record->id = $DB->insert_record('theme_rsmax_section', $record);
        } else {
            $DB->update_record('theme_rsmax_section', $record);
        }
        return $record;
    }

    /**
     * Removes the presentation of a section and its files.
     *
     * @param stdClass $course
     * @param int $sectionid
     */
    public static function delete(stdClass $course, int $sectionid): void {
        global $DB;
        $DB->delete_records('theme_rsmax_section', ['sectionid' => $sectionid]);
        $fs = get_file_storage();
        $contextid = context_course::instance($course->id)->id;
        foreach (array_merge([self::AREA_MEDIA], array_values(self::AREA_TEXTS)) as $area) {
            $fs->delete_area_files($contextid, 'theme_rsmax', $area, $sectionid);
        }
    }

    /**
     * Whether a presentation has anything to show.
     *
     * @param stdClass $record
     * @param bool $hasmedia
     * @return bool
     */
    private static function has_content(stdClass $record, bool $hasmedia): bool {
        foreach (['heading', 'description', 'transcript', 'videourl', 'videoplaceholder', 'about'] as $field) {
            if (trim(strip_tags((string) ($record->$field ?? ''), '<img><video><iframe>')) !== '') {
                return true;
            }
        }
        return $hasmedia;
    }

    /**
     * Whether the person looking may open a section, and so see what presents it.
     *
     * A hidden section, or one still locked by a restriction, is closed to a learner; whoever
     * may see hidden sections, such as its teachers, sees them all.
     *
     * @param stdClass $course
     * @param int $sectionid
     * @return bool False as well when the course has no such section.
     */
    public static function section_visible(stdClass $course, int $sectionid): bool {
        $section = get_fast_modinfo($course)->get_section_info_by_id($sectionid);
        return $section !== null && $section->uservisible;
    }

    /**
     * What the template theme_rsmax/section_presentation needs for each section of a course.
     *
     * Only the sections the person looking may open: the page carries the presentations in its
     * HTML, so one of a hidden or locked section must not travel with it.
     *
     * @param stdClass $course
     * @return array sectionid => context
     */
    public static function export_for_course(stdClass $course): array {
        global $DB;
        $records = $DB->get_records('theme_rsmax_section', ['courseid' => $course->id]);
        $records = array_filter($records, fn($record) => self::section_visible($course, (int) $record->sectionid));
        if (!$records) {
            return [];
        }
        $context = context_course::instance($course->id);
        $fs = get_file_storage();
        $media = [];
        $captions = [];
        foreach ($fs->get_area_files($context->id, 'theme_rsmax', self::AREA_MEDIA, false, 'itemid, id', false) as $file) {
            if (self::is_captions($file)) {
                $captions[$file->get_itemid()] = $file;
            } else if (!isset($media[$file->get_itemid()])) {
                $media[$file->get_itemid()] = $file;
            }
        }
        $result = [];
        foreach ($records as $record) {
            $file = $media[$record->sectionid] ?? null;
            if (!self::has_content($record, (bool) $file)) {
                continue;
            }
            $result[(int) $record->sectionid] = self::export($record, $context, $file, $captions[$record->sectionid] ?? null);
        }
        return $result;
    }

    /**
     * Context of the template for one presentation.
     *
     * @param stdClass $record
     * @param \context $context
     * @param \stored_file|null $file
     * @param \stored_file|null $captions Subtitles of the video (.vtt), if any.
     * @return array
     */
    private static function export(
        stdClass $record,
        \context $context,
        ?\stored_file $file,
        ?\stored_file $captions = null
    ): array {
        $text = function (string $field) use ($record, $context): string {
            $value = (string) ($record->$field ?? '');
            if (trim(strip_tags($value, '<img><video><iframe>')) === '') {
                return '';
            }
            $value = file_rewrite_pluginfile_urls(
                $value,
                'pluginfile.php',
                $context->id,
                'theme_rsmax',
                self::AREA_TEXTS[$field],
                $record->sectionid
            );
            return format_text($value, $record->{$field . 'format'} ?? FORMAT_HTML, ['context' => $context]);
        };

        $mediahtml = '';
        $mediakind = '';
        if ($file) {
            $address = url::make_pluginfile_url(
                $context->id,
                'theme_rsmax',
                self::AREA_MEDIA,
                $record->sectionid,
                '/' . $file->get_timemodified() . '/',
                $file->get_filename()
            )->out(false);
            if (str_starts_with((string) $file->get_mimetype(), 'video/')) {
                $mediakind = 'video';
                $track = '';
                if ($captions) {
                    $track = \html_writer::empty_tag('track', [
                        'kind' => 'captions',
                        'srclang' => substr(current_language(), 0, 2),
                        'label' => get_string('presentation:captions', 'theme_rsmax'),
                        'src' => url::make_pluginfile_url(
                            $context->id,
                            'theme_rsmax',
                            self::AREA_MEDIA,
                            $record->sectionid,
                            '/' . $captions->get_timemodified() . '/',
                            $captions->get_filename()
                        )->out(false)]);
                }
                $mediahtml = \html_writer::tag(
                    'video',
                    \html_writer::empty_tag(
                        'source',
                        ['src' => $address, 'type' => $file->get_mimetype()]
                    ) . $track,
                    ['controls' => 'controls', 'preload' => 'metadata', 'playsinline' => 'playsinline']
                );
            } else {
                $mediakind = 'image';
                $mediahtml = \html_writer::empty_tag('img', ['src' => $address, 'alt' => '', 'loading' => 'lazy']);
            }
        } else if (trim((string) $record->videourl) !== '') {
            $embed = self::embed_url(trim($record->videourl));
            if ($embed !== '') {
                $mediakind = 'video';
                $mediahtml = \html_writer::tag('iframe', '', [
                    'src' => $embed,
                    'loading' => 'lazy',
                    'allowfullscreen' => 'allowfullscreen',
                    'allow' => 'encrypted-media; picture-in-picture; fullscreen',
                    'title' => format_string($record->heading ?? ''),
                ]);
            } else {
                $mediakind = 'link';
                $mediahtml = \html_writer::link(
                    clean_param($record->videourl, PARAM_URL),
                    get_string('presentation:watchvideo', 'theme_rsmax'),
                    ['target' => '_blank', 'rel' => 'noopener']
                );
            }
        }
        $placeholder = format_string((string) ($record->videoplaceholder ?? ''), true, ['context' => $context]);

        $description = $text('description');
        $transcript = $text('transcript');
        $about = $text('about');
        $abouttitle = format_string((string) ($record->abouttitle ?? ''), true, ['context' => $context]);
        $aboutsummary = format_string((string) ($record->aboutsummary ?? ''), true, ['context' => $context]);
        $heading = format_string((string) ($record->heading ?? ''), true, ['context' => $context]);
        $hasmedia = $mediahtml !== '' || $placeholder !== '';
        return [
            'sectionid' => (int) $record->sectionid,
            'hasmedia' => $hasmedia,
            'media' => $mediahtml,
            'mediakind' => $mediakind,
            'placeholder' => $mediahtml === '' ? $placeholder : '',
            'hasbrief' => $heading !== '' || $description !== '' || $transcript !== '',
            'heading' => $heading,
            'description' => $description,
            'transcript' => $transcript,
            'about' => $about,
            'abouttitle' => $abouttitle !== '' ? $abouttitle : get_string('presentation:abouttitledefault', 'theme_rsmax'),
            'aboutsummary' => $aboutsummary,
            'aboutbutton' => get_string('presentation:aboutopen', 'theme_rsmax'),
            'aboutclose' => get_string('presentation:aboutclose', 'theme_rsmax'),
        ];
    }

    /**
     * The address to embed a YouTube or Vimeo video, or an empty string.
     *
     * @param string $address
     * @return string
     */
    public static function embed_url(string $address): string {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $address, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0';
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $address, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return '';
    }
}
