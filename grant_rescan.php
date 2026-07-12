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
 * AJAX endpoint: grant or revoke a rescan request for a student's room scan.
 *
 * Sets needs_rescan = 1 on the scan record so that the student is forced
 * through the preflight wizard again on their next attempt start.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

ob_start();
header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $userid  = required_param('userid', PARAM_INT);
    $quizid  = required_param('quizid', PARAM_INT);
    $grant   = required_param('grant', PARAM_INT); // 1 = grant rescan, 0 = revoke

    global $DB;

    $cm      = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('quizaccess/tagproctoring:manage', $context);

    $scan = $DB->get_record('quizaccess_tagproctoring_scan', ['quizid' => $quizid, 'userid' => $userid]);

    if (!$scan) {
        ob_end_clean();
        echo json_encode(['status' => 'ok', 'note' => 'no_scan_record']);
        exit;
    }

    $DB->update_record('quizaccess_tagproctoring_scan', (object) [
        'id'          => $scan->id,
        'needs_rescan' => $grant ? 1 : 0,
    ]);

    ob_end_clean();
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}
exit;
