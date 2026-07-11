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
 * External function: log a proctoring suspicious event.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_module;

/**
 * External function class for logging proctoring events.
 */
class log_event extends external_api {
    /**
     * Describe the parameters accepted by log_event.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'      => new external_value(PARAM_INT, 'Course-module ID'),
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt ID'),
            'eventtype' => new external_value(PARAM_ALPHANUMEXT, 'Event type identifier'),
            'detail'    => new external_value(PARAM_TEXT, 'Optional detail string', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Log a single proctoring suspicious event for the current user's attempt.
     *
     * @param  int    $cmid      Course-module ID
     * @param  int    $attemptid Quiz attempt ID
     * @param  string $eventtype Short event-type token
     * @param  string $detail    Optional free-text detail
     * @return array  ['status' => 'ok']
     */
    public static function execute(int $cmid, int $attemptid, string $eventtype, string $detail = ''): array {
        global $DB, $USER;

        // Validate and clean parameters.
        [
            'cmid'      => $cmid,
            'attemptid' => $attemptid,
            'eventtype' => $eventtype,
            'detail'    => $detail,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid'      => $cmid,
            'attemptid' => $attemptid,
            'eventtype' => $eventtype,
            'detail'    => $detail,
        ]);

        // Resolve context and check capability.
        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id, MUST_EXIST);
        self::validate_context($context);
        require_capability('mod/quiz:attempt', $context);

        // Ensure the attempt belongs to the current user.
        $DB->get_record('quiz_attempts', [
            'id'     => $attemptid,
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        // Deduplicate: only log one screenshare event per attempt.
        if ($eventtype === 'screenshare') {
            if (
                $DB->record_exists('quizaccess_tagproctoring_events', [
                'attemptid' => $attemptid,
                'eventtype' => 'screenshare',
                ])
            ) {
                return ['status' => 'ok'];
            }
        }

        // Server-side guard rails for large exams: cap rows per attempt and
        // suppress repeated identical events fired within a few seconds.
        $maxevents = (int) get_config('quizaccess_tagproctoring', 'maxevents') ?: 200;
        if ($DB->count_records('quizaccess_tagproctoring_events', ['attemptid' => $attemptid]) >= $maxevents) {
            return ['status' => 'ok'];
        }

        $now = time();
        $lastsameevent = $DB->get_field_sql(
            'SELECT MAX(timecreated)
               FROM {quizaccess_tagproctoring_events}
              WHERE attemptid = ? AND eventtype = ?',
            [$attemptid, $eventtype]
        );
        if ($lastsameevent && ($now - (int) $lastsameevent) < 5) {
            return ['status' => 'ok'];
        }

        $DB->insert_record('quizaccess_tagproctoring_events', (object) [
            'quizid'      => $cm->instance,
            'userid'      => $USER->id,
            'attemptid'   => $attemptid,
            'timecreated' => $now,
            'eventtype'   => $eventtype,
            'detail'      => $detail,
        ]);

        return ['status' => 'ok'];
    }

    /**
     * Describe the return value of log_event.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'ok'),
        ]);
    }
}
