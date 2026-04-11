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

    return true;
}
