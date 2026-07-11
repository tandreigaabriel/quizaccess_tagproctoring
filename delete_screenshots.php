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
 * Delete attempt screenshots AJAX endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $attemptid = required_param('attemptid', PARAM_INT);

    global $DB;

    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    require_capability('quizaccess/tagproctoring:viewreport', $context);

    // Delete files individually (screenshots are keyed by userid, not attemptid in file storage).
    $fs = get_file_storage();
    $screenshots = $DB->get_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $attemptid]);

    foreach ($screenshots as $shot) {
        $file = $fs->get_file(
            $context->id,
            'quizaccess_tagproctoring',
            'screenshots',
            $shot->userid,
            '/',
            $shot->filename
        );

        if ($file) {
            $file->delete();
        }
    }

    $DB->delete_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $attemptid]);

    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}

exit;
