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
 * Webcam screenshot upload endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Prevent any PHP notices/warnings from corrupting the JSON response.
ob_start();

header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $cmid = required_param('cmid', PARAM_INT);
    $attemptid = required_param('attemptid', PARAM_INT);

    global $DB, $USER;

    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $quizid = $cm->instance;
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('mod/quiz:attempt', $context);

    // Validate attempt belongs to this user.
    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid, 'userid' => $USER->id], '*', MUST_EXIST);

    if (empty($_FILES['screenshot']) || !is_uploaded_file($_FILES['screenshot']['tmp_name'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => get_string('error_no_screenshot', 'quizaccess_tagproctoring')]);
        exit;
    }

    // Enforce the admin-configured maximum screenshots per attempt to prevent storage overload.
    // With 500+ concurrent students each triggering events, this cap keeps server load predictable.
    $maxshots = (int) get_config('quizaccess_tagproctoring', 'maxscreenshots') ?: 20;
    $existingcount = $DB->count_records('quizaccess_tagproctoring_screenshots', ['attemptid' => $attemptid]);
    if ($existingcount >= $maxshots) {
        ob_end_clean();
        echo json_encode(['status' => 'ok', 'skipped' => true]);
        exit;
    }

    // Server-side rate limit: max 1 screenshot per 5 seconds per attempt.
    // Prevents bursts even if the client throttle is bypassed (e.g. multiple tabs).
    $lastshot = $DB->get_field_sql(
        'SELECT MAX(timecreated) FROM {quizaccess_tagproctoring_screenshots} WHERE attemptid = ?',
        [$attemptid]
    );
    if ($lastshot && (time() - $lastshot) < 5) {
        ob_end_clean();
        echo json_encode(['status' => 'ok', 'skipped' => true, 'reason' => 'rate_limit']);
        exit;
    }

    $fs = get_file_storage();
    // Screen or webcam. PARAM_ALPHA strips everything except letters — safe for filenames.
    $source = optional_param('source', 'webcam', PARAM_ALPHA);
    // Append a short uniqid suffix so concurrent uploads in the same second never collide.
    $filename = $source . '_' . $attemptid . '_' . $USER->id . '_' . time() . '_' . substr(uniqid(), -8) . '.jpg';

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'quizaccess_tagproctoring',
        'filearea' => 'screenshots',
        'itemid' => $USER->id,
        'filepath' => '/',
        'filename' => $filename,
    ];

    $fs->create_file_from_pathname($fileinfo, $_FILES['screenshot']['tmp_name']);

    $DB->insert_record('quizaccess_tagproctoring_screenshots', (object) [
        'quizid' => $quizid,
        'userid' => $USER->id,
        'attemptid' => $attemptid,
        'timecreated' => time(),
        'filename' => $filename,
        'source' => $source,
    ]);

    ob_end_clean();
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}
exit;
