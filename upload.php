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
 * Room scan video upload endpoint for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Moodle requires this even if config.php is included.
defined('MOODLE_INTERNAL') || die();

// Prevent output corruption.
ob_start();

header('Content-Type: application/json');

$logdir = $CFG->dataroot . '/quizaccess_tagproctoring/logs';
$logfile = $logdir . '/upload.log';

if (!is_dir($logdir)) {
    make_writable_directory($logdir);
}

file_put_contents($logfile, date('[H:i:s] ') . "HIT\n", FILE_APPEND);

try {
    require_login();
    require_sesskey();

    $quizid = required_param('quizid', PARAM_INT);

    if (empty($_FILES['video']) || !is_uploaded_file($_FILES['video']['tmp_name'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => get_string('error_no_video', 'quizaccess_tagproctoring'),
        ]);
        exit;
    }

    global $DB, $USER;

    $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);


    $context = context_module::instance($cm->id, MUST_EXIST);
    require_capability('mod/quiz:attempt', $context);
    $fs = get_file_storage();

    $filename = 'roomscan_' . $quizid . '_' . $USER->id . '_' . time() . '.webm';

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'quizaccess_tagproctoring',
        'filearea' => 'roomscan',
        'itemid' => $USER->id,
        'filepath' => '/',
        'filename' => $filename,
    ];

    foreach (
        $fs->get_area_files(
            $context->id,
            'quizaccess_tagproctoring',
            'roomscan',
            $USER->id,
            'timecreated DESC',
            false
        ) as $f
    ) {
        $f->delete();
    }

    $fs->create_file_from_pathname($fileinfo, $_FILES['video']['tmp_name']);

    $record = (object) [
        'quizid' => $quizid,
        'userid' => $USER->id,
        'attemptid' => 0,
        'timecreated' => time(),
        'filename' => $filename,
    ];

    // AI scan parsing.
    // JSON payload — PARAM_TEXT strips control characters/null bytes while preserving valid JSON.
    // The decoded value is further validated below with json_decode + is_array.
    $rawobjects = optional_param('scan_objects', '{}', PARAM_TEXT);
    $scanobjects = '';
    $scansuspicious = 0;

    $decoded = json_decode($rawobjects, true);

    if (is_array($decoded) && !empty($decoded)) {
        $scanobjects = json_encode($decoded);

        // Minimum number of detection intervals (each ~1.5 s) an object must appear
        // in before being flagged as suspicious. Requiring at least 3 (~4.5 s of
        // presence) eliminates brief false positives from momentary misclassification.
        $minthreshold = 3;

        $flaggedclasses = [
            'cell phone',
            'book',
            'tablet',
            'remote',
            'extra_person',
            'face_turned',
        ];

        $flaggedfound = [];
        foreach ($flaggedclasses as $cls) {
            if (!empty($decoded[$cls]) && (int)$decoded[$cls] >= $minthreshold) {
                $flaggedfound[] = $cls;
                $scansuspicious = 1;
            }
        }
    }

    $record->scan_objects = $scanobjects;
    $record->scan_suspicious = $scansuspicious;

    $existingrow = $DB->get_record(
        'quizaccess_tagproctoring_scan',
        [
            'quizid' => $quizid,
            'userid' => $USER->id,
        ]
    );

    if ($existingrow) {
        $record->id = $existingrow->id;
        $DB->update_record('quizaccess_tagproctoring_scan', $record);
    } else {
        $DB->insert_record('quizaccess_tagproctoring_scan', $record);
    }

    ob_end_clean();

    echo json_encode([
        'status' => 'ok',
        'suspicious' => (bool) $scansuspicious,
        'flagged' => $flaggedfound ?? [],
    ]);
} catch (Throwable $e) {
    ob_end_clean();

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
exit;
