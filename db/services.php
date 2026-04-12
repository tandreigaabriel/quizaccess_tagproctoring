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
 * External services registration for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'quizaccess_tagproctoring_log_event' => [
        'classname'   => 'quizaccess_tagproctoring\external\log_event',
        'methodname'  => 'execute',
        'description' => 'Log a suspicious proctoring event for the current user\'s quiz attempt.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
        'capabilities' => 'mod/quiz:attempt',
    ],
    'quizaccess_tagproctoring_reset_timer' => [
        'classname'   => 'quizaccess_tagproctoring\external\reset_timer',
        'methodname'  => 'execute',
        'description' => 'Reset quiz attempt timestart to now after the pre-exam wizard completes.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
        'capabilities' => 'mod/quiz:attempt',
    ],
];
