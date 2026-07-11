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
 * ID photo upload endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../../config.php');
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

    if (empty($_FILES['idphoto']) || !is_uploaded_file($_FILES['idphoto']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => get_string('error_no_idphoto', 'quizaccess_tagproctoring')]);
        exit;
    }

    $fs = get_file_storage();
    $filename = 'idphoto_' . $quizid . '_' . $USER->id . '_' . time() . '.jpg';

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'quizaccess_tagproctoring',
        'filearea' => 'idphoto',
        'itemid' => $USER->id,
        'filepath' => '/',
        'filename' => $filename,
    ];

    // Delete previous ID photo for this user quiz.
    foreach ($fs->get_area_files($context->id, 'quizaccess_tagproctoring', 'idphoto', $USER->id, 'timecreated DESC', false) as $f) {
        $f->delete();
    }

    $fs->create_file_from_pathname($fileinfo, $_FILES['idphoto']['tmp_name']);

    // Face-match params sent by the JS client after client-side AI comparison.
    // verified: 1=match, 0=mismatch, -1=unknown/no face detected.
    $faceverified = optional_param('face_match_verified', null, PARAM_INT);
    $facedistance = optional_param('face_match_distance', null, PARAM_FLOAT);

    // Clamp/sanitise: keep -1 sentinel as NULL in DB; reject out-of-range values.
    if ($facedistance !== null && $facedistance < 0) {
        $facedistance = null;
    }
    if ($faceverified !== null && !in_array((int) $faceverified, [-1, 0, 1], true)) {
        $faceverified = null;
    }

    // Update or create scan record with ID photo filename and face-match result.
    $existing = $DB->get_record('quizaccess_tagproctoring_scan', ['quizid' => $quizid, 'userid' => $USER->id]);
    if ($existing) {
        $update = (object) ['id' => $existing->id, 'idphoto_filename' => $filename];
        if ($faceverified !== null) {
            $update->face_match_verified = (int) $faceverified;
            $update->face_match_distance = $facedistance;
        }
        $DB->update_record('quizaccess_tagproctoring_scan', $update);
    } else {
        $record = (object) [
            'quizid'           => $quizid,
            'userid'           => $USER->id,
            'attemptid'        => 0,
            'timecreated'      => time(),
            'filename'         => '',
            'selfie_filename'  => '',
            'idphoto_filename' => $filename,
        ];
        if ($faceverified !== null) {
            $record->face_match_verified = (int) $faceverified;
            $record->face_match_distance = $facedistance;
        }
        $DB->insert_record('quizaccess_tagproctoring_scan', $record);
    }


    ob_end_clean();
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => get_string('error_general', 'quizaccess_tagproctoring')]);
}
exit;
