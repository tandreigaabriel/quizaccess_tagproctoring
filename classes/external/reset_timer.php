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
 * External function: reset quiz attempt timer after the pre-exam wizard completes.
 *
 * Moves quiz_attempts.timestart to now so students do not lose quiz time
 * while completing the proctoring pre-exam checks (room scan, selfie, ID, screen share).
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
 * Resets the quiz attempt start time to now so the student's timer is not eaten by the wizard.
 */
class reset_timer extends external_api {
    /**
     * Describe the parameters accepted by reset_timer.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'      => new external_value(PARAM_INT, 'Course-module ID'),
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt ID'),
        ]);
    }

    /**
     * Reset timestart on the quiz attempt to now.
     *
     * Only resets if the attempt belongs to the current user, is still in progress,
     * and the quiz has a time limit (otherwise there is nothing to gain).
     *
     * @param  int   $cmid      Course-module ID.
     * @param  int   $attemptid Quiz attempt ID.
     * @return array ['status' => 'ok', 'added_seconds' => int]
     */
    public static function execute(int $cmid, int $attemptid): array {
        global $DB, $USER, $CFG;

        [
            'cmid'      => $cmid,
            'attemptid' => $attemptid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid'      => $cmid,
            'attemptid' => $attemptid,
        ]);

        // Resolve context and check capability.
        $cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id, MUST_EXIST);
        self::validate_context($context);
        require_capability('mod/quiz:attempt', $context);

        // Attempt must belong to the current user and be in-progress.
        $attempt = $DB->get_record('quiz_attempts', [
            'id'     => $attemptid,
            'userid' => $USER->id,
            'state'  => 'inprogress',
        ], '*', MUST_EXIST);

        // Only reset once: if timestart is already close to now (< 30 s ago) skip.
        $now = time();
        $elapsed = $now - (int) $attempt->timestart;
        if ($elapsed <= 30) {
            return ['status' => 'ok', 'added_seconds' => 0];
        }

        // Check that the quiz actually has a time limit — no point resetting otherwise.
        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], 'id, timelimit', MUST_EXIST);
        if (empty($quiz->timelimit)) {
            return ['status' => 'ok', 'added_seconds' => 0];
        }

        // Reset: move timestart forward to now so the student gets their full quota.
        $DB->set_field('quiz_attempts', 'timestart', $now, ['id' => $attemptid]);

        return ['status' => 'ok', 'added_seconds' => $elapsed];
    }

    /**
     * Describe the return value of reset_timer.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'        => new external_value(PARAM_TEXT, 'ok'),
            'added_seconds' => new external_value(PARAM_INT, 'Seconds added back to the quiz timer'),
        ]);
    }
}
