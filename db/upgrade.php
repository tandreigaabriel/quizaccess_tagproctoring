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
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute database upgrades for quizaccess_tagproctoring.
 *
 * @param int $oldversion The current plugin version.
 * @return bool
 */
function xmldb_quizaccess_tagproctoring_upgrade($oldversion) {
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
            'face_match_verified',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            null,
            null,
            null,
            'scan_suspicious'
        );
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        $field2 = new xmldb_field(
            'face_match_distance',
            XMLDB_TYPE_NUMBER,
            '5',
            null,
            null,
            null,
            null,
            'face_match_verified'
        );
        $field2->setDecimals(3);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        upgrade_plugin_savepoint(true, 2026041200, 'quizaccess', 'tagproctoring');
    }

    // 2026041300 - Add source column to screenshots table ('webcam' or 'screen').
    if ($oldversion < 2026041300) {
        $table = new xmldb_table('quizaccess_tagproctoring_screenshots');

        $field = new xmldb_field(
            'source',
            XMLDB_TYPE_CHAR,
            '20',
            null,
            false,
            null,
            null,
            'filename'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Back-fill existing rows by deriving source from filename prefix.
        $DB->execute(
            "UPDATE {quizaccess_tagproctoring_screenshots}
                SET source = CASE WHEN " . $DB->sql_like('filename', ':pfx', false) . "
                             THEN 'screen' ELSE 'webcam' END
              WHERE source IS NULL",
            ['pfx' => 'screen_%']
        );

        upgrade_plugin_savepoint(true, 2026041300, 'quizaccess', 'tagproctoring');
    }

    // 2026041400 - Add enableroomscan column to per-quiz settings table.
    if ($oldversion < 2026041400) {
        $table = new xmldb_table('quizaccess_tagproctoring_settings');

        $field = new xmldb_field(
            'enableroomscan',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            1,
            'enabled'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026041400, 'quizaccess', 'tagproctoring');
    }

    // 2026070502 - Add rescanperattempt to per-quiz settings table.
    if ($oldversion < 2026070502) {
        $table = new xmldb_table('quizaccess_tagproctoring_settings');

        $field = new xmldb_field(
            'rescanperattempt',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            0,
            'durationsec'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026070502, 'quizaccess', 'tagproctoring');
    }

    // 2026070501 - Add reviewed fields to overrides table; add needs_rescan to scan table.
    if ($oldversion < 2026070501) {
        $ovtable = new xmldb_table('quizaccess_tagproctoring_overrides');

        $f1 = new xmldb_field('reviewed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'timecreated');
        if (!$dbman->field_exists($ovtable, $f1)) {
            $dbman->add_field($ovtable, $f1);
        }

        $f2 = new xmldb_field('reviewedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reviewed');
        if (!$dbman->field_exists($ovtable, $f2)) {
            $dbman->add_field($ovtable, $f2);
        }

        $f3 = new xmldb_field('timereviewed', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reviewedby');
        if (!$dbman->field_exists($ovtable, $f3)) {
            $dbman->add_field($ovtable, $f3);
        }

        $scantable = new xmldb_table('quizaccess_tagproctoring_scan');

        $f4 = new xmldb_field('needs_rescan', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'scan_suspicious');
        if (!$dbman->field_exists($scantable, $f4)) {
            $dbman->add_field($scantable, $f4);
        }

        upgrade_plugin_savepoint(true, 2026070501, 'quizaccess', 'tagproctoring');
    }

    // 2026070505 - Add performance indexes for large concurrent exams.
    if ($oldversion < 2026070505) {
        $scantable = new xmldb_table('quizaccess_tagproctoring_scan');
        $scanattempt = new xmldb_index('attemptid', XMLDB_INDEX_NOTUNIQUE, ['attemptid']);
        if (!$dbman->index_exists($scantable, $scanattempt)) {
            $dbman->add_index($scantable, $scanattempt);
        }

        $screenshotstable = new xmldb_table('quizaccess_tagproctoring_screenshots');
        $screenshotsattempt = new xmldb_index('attemptid', XMLDB_INDEX_NOTUNIQUE, ['attemptid']);
        if (!$dbman->index_exists($screenshotstable, $screenshotsattempt)) {
            $dbman->add_index($screenshotstable, $screenshotsattempt);
        }

        $eventstable = new xmldb_table('quizaccess_tagproctoring_events');
        $eventattempttime = new xmldb_index(
            'attemptid_event_time',
            XMLDB_INDEX_NOTUNIQUE,
            ['attemptid', 'eventtype', 'timecreated']
        );
        if (!$dbman->index_exists($eventstable, $eventattempttime)) {
            $dbman->add_index($eventstable, $eventattempttime);
        }

        upgrade_plugin_savepoint(true, 2026070505, 'quizaccess', 'tagproctoring');
    }

    // 2026071207 - Add timecreated index to scan table for the cleanup task.
    if ($oldversion < 2026071207) {
        $scantable = new xmldb_table('quizaccess_tagproctoring_scan');
        $scantimecreated = new xmldb_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        if (!$dbman->index_exists($scantable, $scantimecreated)) {
            $dbman->add_index($scantable, $scantimecreated);
        }

        upgrade_plugin_savepoint(true, 2026071207, 'quizaccess', 'tagproctoring');
    }

    return true;
}
