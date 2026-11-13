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
 * Add an area to a section: a heading that groups the activities placed under it.
 *
 * @package    theme_rsmax
 * @copyright  2026 RSMAX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$courseid = required_param('course', PARAM_INT);
$sectionnum = required_param('section', PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $context);
$section = get_fast_modinfo($course)->get_section_info($sectionnum, MUST_EXIST);

$returnurl = course_get_url($course, $sectionnum);
$PAGE->set_url('/theme/rsmax/area.php', ['course' => $course->id, 'section' => $sectionnum]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('area:add', 'theme_rsmax'));
$PAGE->set_heading(format_string($course->fullname));

$name = optional_param('name', '', PARAM_TEXT);
if ($name !== '' && confirm_sesskey()) {
    \theme_rsmax\local\course_areas::create($course, $sectionnum, $name);
    redirect($returnurl, get_string('area:added', 'theme_rsmax', s($name)), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('area:addto', 'theme_rsmax', format_string(get_section_name($course, $section))));
echo html_writer::tag('p', get_string('area:intro', 'theme_rsmax'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false), 'class' => 'rsmax-area-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::label(get_string('area:name', 'theme_rsmax'), 'rsmax-area-name', true, ['class' => 'form-label fw-bold']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'id' => 'rsmax-area-name', 'class' => 'form-control mb-3',
    'required' => 'required', 'maxlength' => 120, 'placeholder' => get_string('area:placeholder', 'theme_rsmax')]);
echo html_writer::start_div('d-flex gap-2');
echo html_writer::tag('button', get_string('area:add', 'theme_rsmax'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
