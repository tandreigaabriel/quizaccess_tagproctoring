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
 * Event observer class for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\local;

/**
 * Handles Moodle events relevant to TAG Proctoring.
 */
class observer {
    /**
     * Fires when a quiz attempt is submitted.
     *
     * Queues a lightweight ad-hoc task that does all the heavy DB work and email
     * sending outside the event-dispatch critical path.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     * @return void
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        if (!get_config('quizaccess_tagproctoring', 'emailalerts')) {
            return;
        }

        $quizid = (int) ($event->other['quizid'] ?? 0);
        $userid = (int) $event->relateduserid;

        if (!$quizid || !$userid) {
            return;
        }

        $task = new \quizaccess_tagproctoring\task\send_attempt_alert();
        $task->set_custom_data([
            'attemptid' => (int) $event->objectid,
            'quizid'    => $quizid,
            'userid'    => $userid,
            'courseid'  => (int) $event->courseid,
            'cmid'      => (int) $event->get_context()->instanceid,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
