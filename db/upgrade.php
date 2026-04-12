<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Database upgrade steps for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute database upgrades for quizaccess_tagproctoring.
 *
 * @param int $oldversion The current plugin version.
 * @return bool
 */
function xmldb_quizaccess_tagproctoring_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    // 2026041100 - Initial release of tagproctoring.
    if ($oldversion < 2026041100) {
        upgrade_plugin_savepoint(true, 2026041100, 'quizaccess', 'tagproctoring');
    }

    // 2026041200 - Add face_match_verified and face_match_distance to scan table.
    if ($oldversion < 2026041200) {
        $table = new xmldb_table('quizaccess_tagproctoring_scan');

        $field1 = new xmldb_field(
            'face_match_verified', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'scan_suspicious'
        );
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        $field2 = new xmldb_field(
            'face_match_distance', XMLDB_TYPE_NUMBER, '5', null, null, null, null, 'face_match_verified'
        );
        $field2->setDecimals(3);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        upgrade_plugin_savepoint(true, 2026041200, 'quizaccess', 'tagproctoring');
    }

    return true;
}
