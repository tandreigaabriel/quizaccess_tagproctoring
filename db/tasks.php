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
 * Scheduled task definitions for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname'   => 'quizaccess_tagproctoring\task\cleanup_files',
        'blocking'    => 0,
        'minute'      => '0',
        'hour'        => '3',
        'day'         => '*',
        'month'       => '*',
        'dayofweek'   => '*',
        'disabled'    => 0,
    ],
    [
        'classname'   => 'quizaccess_tagproctoring\task\renew_license',
        'blocking'    => 0,
        'minute'      => '0',
        'hour'        => '2',
        'day'         => '*',
        'month'       => '*',
        'dayofweek'   => '0', // Sunday
        'disabled'    => 0,
    ],
];
