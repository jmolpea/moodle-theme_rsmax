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
 * Upgrade steps.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the theme.
 *
 * @param int $oldversion Version being upgraded from.
 * @return bool
 */
function xmldb_theme_rsmax_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026100710) {
        // Sites that installed an earlier version get the dashboard arrangement new installs get.
        \theme_rsmax\local\dashboard_setup::apply();
        upgrade_plugin_savepoint(true, 2026100710, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100716) {
        // The table with the options of each course: where its banner is shown.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('theme_rsmax_course');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('bannerscope', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // The palette became warm. Colours still at their old default follow the new one;
        // a colour somebody chose is left alone.
        require_once(__DIR__ . '/../lib.php');
        foreach (THEME_RSMAX_OLD_COLOURS as $setting => $old) {
            if (strtolower((string) get_config('theme_rsmax', $setting)) === $old) {
                set_config($setting, THEME_RSMAX_COLOURS[$setting]['default'], 'theme_rsmax');
            }
        }
        theme_reset_all_caches();
        upgrade_plugin_savepoint(true, 2026100716, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100719) {
        // New courses start with the blocks of the theme, unless the site already decided otherwise.
        \theme_rsmax\local\course_blocks::setup();
        upgrade_plugin_savepoint(true, 2026100719, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100725) {
        // The text of the buttons was white by default, which does not read on a light brand
        // colour. The default is now whichever reads; sites still on the old default follow it.
        if (get_config('theme_rsmax', 'buttontext') === 'light') {
            set_config('buttontext', 'auto', 'theme_rsmax');
        }
        theme_reset_all_caches();
        upgrade_plugin_savepoint(true, 2026100725, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100726) {
        // The header of the activities and their background now follow the dark colour and the
        // background of the site unless they are given a colour of their own.
        foreach (['stagecolor' => '#2b2624', 'papercolor' => '#f6f1ea'] as $setting => $old) {
            if (strtolower((string) get_config('theme_rsmax', $setting)) === $old) {
                set_config($setting, '', 'theme_rsmax');
            }
        }
        theme_reset_all_caches();
        upgrade_plugin_savepoint(true, 2026100726, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100727) {
        // The AI assistant: in new courses, and one for the site on its public pages.
        \theme_rsmax\local\assistant::ensure();
        upgrade_plugin_savepoint(true, 2026100727, 'theme', 'rsmax');
    }
    if ($oldversion < 2026100731) {
        // The content column is wider by default. Sites still on the old default follow it; a
        // width somebody chose is left alone.
        if ((int) get_config('theme_rsmax', 'contentwidth') === 1120) {
            set_config('contentwidth', 1280, 'theme_rsmax');
        }
        theme_reset_all_caches();
        upgrade_plugin_savepoint(true, 2026100731, 'theme', 'rsmax');
    }
    if ($oldversion < 2026110400) {
        // Each course chooses the look of its pages: the dark stage or light pages.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('theme_rsmax_course');
        $field = new xmldb_field('pagestyle', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'bannerscope');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026110400, 'theme', 'rsmax');
    }
    if ($oldversion < 2026110500) {
        // The presentation of each module: photo or video, text and «About this module».
        $dbman = $DB->get_manager();
        $table = new xmldb_table('theme_rsmax_section');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('heading', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('transcriptformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('videourl', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('videoplaceholder', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('abouttitle', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('aboutsummary', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('about', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('aboutformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('sectionid', XMLDB_KEY_FOREIGN_UNIQUE, ['sectionid'], 'course_sections', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        theme_reset_all_caches();
        upgrade_plugin_savepoint(true, 2026110500, 'theme', 'rsmax');
    }
    if ($oldversion < 2026111300) {
        // The classroom changes every page of a course. A site that already had the theme keeps the
        // look of its courses until its administrator turns the classroom on; a new site has it on.
        \theme_rsmax\local\classroom::keep_off_on_upgrade((int) $oldversion);
        upgrade_plugin_savepoint(true, 2026111300, 'theme', 'rsmax');
    }
    return true;
}
