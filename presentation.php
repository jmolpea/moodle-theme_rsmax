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

/**
 * Edits the presentation of a module: photo or video, text and «About this module».
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
// The options of the editors are prepared before the form is: they need the constants of the forms library.
require_once($CFG->libdir . '/formslib.php');

use theme_rsmax\local\section_presentation;

$sectionid = required_param('id', PARAM_INT);
$section = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
$course = get_course($section->course);
require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

$sectioninfo = get_fast_modinfo($course)->get_section_info_by_id($sectionid, MUST_EXIST);
$sectionname = get_section_name($course, $sectioninfo);
$returnurl = course_get_url($course, $sectioninfo->section);

$PAGE->set_url('/theme/rsmax/presentation.php', ['id' => $sectionid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('presentation:edit', 'theme_rsmax') . ': ' . format_string($sectionname));
$PAGE->set_heading(format_string($course->fullname));

$record = section_presentation::get($sectionid);
$editor = section_presentation::editor_options($course);
$data = $record ? clone $record : new stdClass();
$data->id = $sectionid;
foreach (section_presentation::AREA_TEXTS as $field => $area) {
    $data->$field = $data->$field ?? '';
    $data->{$field . 'format'} = $data->{$field . 'format'} ?? FORMAT_HTML;
    $data = file_prepare_standard_editor($data, $field, $editor, $context, 'theme_rsmax', $area, $sectionid);
}
$draft = file_get_submitted_draft_itemid('media_filemanager');
file_prepare_draft_area(
    $draft,
    $context->id,
    'theme_rsmax',
    section_presentation::AREA_MEDIA,
    $sectionid,
    section_presentation::media_options()
);
$data->media_filemanager = $draft;

$form = new \theme_rsmax\form\section_presentation_form(null, ['course' => $course, 'exists' => (bool) $record]);
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($submitted = $form->get_data()) {
    if (!empty($submitted->remove)) {
        section_presentation::delete($course, $sectionid);
        redirect($returnurl, get_string('presentation:removed', 'theme_rsmax'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    foreach (section_presentation::AREA_TEXTS as $field => $area) {
        $submitted = file_postupdate_standard_editor($submitted, $field, $editor, $context, 'theme_rsmax', $area, $sectionid);
    }
    file_save_draft_area_files(
        $submitted->media_filemanager,
        $context->id,
        'theme_rsmax',
        section_presentation::AREA_MEDIA,
        $sectionid,
        section_presentation::media_options()
    );
    section_presentation::save($course, $sectionid, $submitted);
    redirect($returnurl, get_string('presentation:saved', 'theme_rsmax'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('presentation:edit', 'theme_rsmax') . ': ' . format_string($sectionname));
echo html_writer::tag('p', get_string('presentation:intro', 'theme_rsmax'), ['class' => 'text-muted']);
$form->display();
echo $OUTPUT->footer();
