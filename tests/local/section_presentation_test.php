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
 * Tests of the presentation of the modules of a course.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\theme_rsmax\local\section_presentation::class)]
final class section_presentation_test extends \advanced_testcase {
    /**
     * The presentation of a hidden section reaches those who may open it, and nobody else.
     */
    public function test_hidden_sections_are_left_out(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $open = (int) $modinfo->get_section_info(1)->id;
        $hidden = (int) $modinfo->get_section_info(2)->id;
        foreach ([$open, $hidden] as $sectionid) {
            section_presentation::save($course, $sectionid, (object) [
                'heading' => 'Brief',
                'description' => '<p>What this module is about</p>',
                'descriptionformat' => FORMAT_HTML,
            ]);
        }
        set_section_visible($course->id, 2, 0);
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');

        $this->setUser($student);
        $this->assertSame([$open], array_keys(section_presentation::export_for_course($course)));
        $this->assertTrue(section_presentation::section_visible($course, $open));
        $this->assertFalse(section_presentation::section_visible($course, $hidden));
        // A section the course does not have is not shown either.
        $this->assertFalse(section_presentation::section_visible($course, 0));

        $this->setUser($teacher);
        $this->assertEqualsCanonicalizing([$open, $hidden], array_keys(section_presentation::export_for_course($course)));
        $this->assertTrue(section_presentation::section_visible($course, $hidden));
    }
}
