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
 * Selfie photo upload endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

ob_start();
header('Content-Type: application/json');

try {
    require_login();
    require_sesskey();

    $quizid = required_param('quizid', PARAM_INT);

    global $DB, $USER;

    $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('mod/quiz:attempt', $context);

    if (empty($_FILES['selfie']) || !is_uploaded_file($_FILES['selfie']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => get_string('error_no_selfie', 'quizaccess_tagproctoring')]);
        exit;
    }

    $fs = get_file_storage();
    $filename = 'selfie_' . $quizid . '_' . $USER->id . '_' . time() . '.jpg';

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'quizaccess_tagproctoring',
        'filearea' => 'selfie',
        'itemid' => $USER->id,
        'filepath' => '/',
        'filename' => $filename,
    ];

    // Delete previous selfie for this user/quiz.
    foreach ($fs->get_area_files($context->id, 'quizaccess_tagproctoring', 'selfie', $USER->id, 'timecreated DESC', false) as $f) {
        $f->delete();
    }

    $fs->create_file_from_pathname($fileinfo, $_FILES['selfie']['tmp_name']);

    // Update or create scan record with selfie filename.
    $existing = $DB->get_record('quizaccess_tagproctoring_scan', ['quizid' => $quizid, 'userid' => $USER->id]);
    if ($existing) {
        $DB->update_record('quizaccess_tagproctoring_scan', (object) [
            'id' => $existing->id,
            'selfie_filename' => $filename,
        ]);
    } else {
        $DB->insert_record('quizaccess_tagproctoring_scan', (object) [
            'quizid' => $quizid,
            'userid' => $USER->id,
            'attemptid' => 0,
            'timecreated' => time(),
            'filename' => '',
            'selfie_filename' => $filename,
            'idphoto_filename' => '',
        ]);
    }

    ob_end_clean();
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}
exit;
