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
 * Suspicious event logging endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $cmid = required_param('cmid', PARAM_INT);
    $attemptid = required_param('attemptid', PARAM_INT);
    $eventtype = required_param('eventtype', PARAM_ALPHANUMEXT);
    $detail = optional_param('detail', '', PARAM_TEXT);

    global $DB, $USER;

    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $quizid = $cm->instance;
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('mod/quiz:attempt', $context);

    // Validate attempt belongs to this user.
    $DB->get_record('quiz_attempts', [
        'id' => $attemptid,
        'userid' => $USER->id,
    ], '*', MUST_EXIST);

    // Avoid duplicate screenshare events (only log once per attempt).
    if ($eventtype === 'screenshare') {
        $existing = $DB->record_exists('quizaccess_tagproctoring_events', [
            'attemptid' => $attemptid,
            'eventtype' => 'screenshare',
        ]);

        if ($existing) {
            echo json_encode(['status' => 'ok', 'skipped' => true]);
            exit;
        }
    }

    $DB->insert_record('quizaccess_tagproctoring_events', (object) [
        'quizid' => $quizid,
        'userid' => $USER->id,
        'attemptid' => $attemptid,
        'timecreated' => time(),
        'eventtype' => $eventtype,
        'detail' => $detail,
    ]);

    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}

exit;
