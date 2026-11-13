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
 * The RSMAX classroom: the design of the theme inside the courses.
 *
 * Light pages, the «your next step» header, the map of modules, areas, the presentation of each
 * module, the type and duration of each activity and the delivery panel of the tasks. It is on in
 * every course of the site unless the administrator turns it off in the settings of the theme.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classroom {
    /** Body class of the pages of a course with the classroom. */
    const BODYCLASS = 'rsmax-aula';

    /** Version of the theme that brought the classroom. */
    const SINCE = 2026110500;

    /**
     * Leaves the classroom off on a site that had the theme before the classroom existed.
     *
     * Called from the upgrade. The setting is written only when the administrator never chose:
     * a site that upgrades keeps the look of its courses, and a new site, which never runs the
     * upgrade, gets the default of the setting, on.
     *
     * @param int $oldversion Version of the theme the site upgrades from.
     */
    public static function keep_off_on_upgrade(int $oldversion): void {
        if ($oldversion < self::SINCE && get_config('theme_rsmax', 'classroom') === false) {
            set_config('classroom', 0, 'theme_rsmax');
        }
    }

    /**
     * Whether the site uses the classroom in its courses.
     *
     * @return bool
     */
    public static function enabled_on_site(): bool {
        $value = get_config('theme_rsmax', 'classroom');
        return $value === false || $value === null || $value === '' || !empty($value);
    }

    /**
     * Whether a course shows the classroom.
     *
     * @param int $courseid
     * @return bool
     */
    public static function enabled(int $courseid): bool {
        return $courseid > 0 && $courseid != SITEID && self::enabled_on_site();
    }

    /**
     * The short code and the title of a section named «M1 · Title»; other names keep their number.
     *
     * @param string $name Name of the section, as plain text.
     * @param int $number Number of the section.
     * @return array short, title and hasshort
     */
    public static function split_section_name(string $name, int $number): array {
        $parts = explode(' · ', $name, 2);
        if (count($parts) == 2 && \core_text::strlen($parts[0]) <= 8) {
            return ['short' => $parts[0], 'title' => $parts[1], 'hasshort' => true];
        }
        return ['short' => (string) $number, 'title' => $name, 'hasshort' => false];
    }
}
