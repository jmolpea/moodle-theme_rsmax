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
 * The configuration manual of the theme: where it is and the link that opens it.
 *
 * The address lives here only, so a new edition of the manual is one change.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual {
    /** Address of the configuration manual. */
    public const URL = 'https://claude.ai/artifact/UgYL8Job3Sh5p6a56HMjSQ';

    /**
     * Returns the link to the manual, which opens in a new tab.
     *
     * @return string HTML
     */
    public static function link(): string {
        return \html_writer::link(
            self::URL,
            get_string('manuallinkopen', 'theme_rsmax'),
            ['target' => '_blank', 'rel' => 'noopener']
        );
    }

    /**
     * Returns the sentence that presents the manual, with its link.
     *
     * @return string HTML
     */
    public static function description(): string {
        return get_string('manuallink_desc', 'theme_rsmax', self::link());
    }
}
