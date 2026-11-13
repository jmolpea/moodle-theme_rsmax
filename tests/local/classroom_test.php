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
 * The classroom: on for a new site, off for a site that upgrades until its administrator chooses.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(classroom::class)]
final class classroom_test extends \advanced_testcase {
    /**
     * A site that never chose has the classroom on.
     */
    public function test_on_when_never_set(): void {
        $this->resetAfterTest();
        unset_config('classroom', 'theme_rsmax');
        $course = $this->getDataGenerator()->create_course();
        $this->assertTrue(classroom::enabled_on_site());
        $this->assertTrue(classroom::enabled($course->id));
        $this->assertFalse(classroom::enabled(SITEID));
    }

    /**
     * Upgrading from a version without the classroom leaves it off, and the choice is saved.
     */
    public function test_upgrade_from_before_the_classroom_keeps_it_off(): void {
        $this->resetAfterTest();
        unset_config('classroom', 'theme_rsmax');
        classroom::keep_off_on_upgrade(2026101305);
        $this->assertSame('0', get_config('theme_rsmax', 'classroom'));
        $this->assertFalse(classroom::enabled_on_site());
    }

    /**
     * The upgrade respects what the administrator chose and sites that already had the classroom.
     */
    public function test_upgrade_respects_a_choice_and_later_versions(): void {
        $this->resetAfterTest();
        set_config('classroom', 1, 'theme_rsmax');
        classroom::keep_off_on_upgrade(2026101305);
        $this->assertTrue(classroom::enabled_on_site());

        unset_config('classroom', 'theme_rsmax');
        classroom::keep_off_on_upgrade(classroom::SINCE);
        $this->assertFalse(get_config('theme_rsmax', 'classroom'));
        $this->assertTrue(classroom::enabled_on_site());
    }
}
