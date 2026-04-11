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

$logdir = __DIR__ . '/logs';
$logfile = $logdir . '/upload.log';

if (!is_dir($logdir)) {
    mkdir($logdir, 0750, true);
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
            'message' => 'No video file received',
        ]);
        exit;
    }

    global $DB, $USER;

    $cm = get_coursemodule_from_id('quiz', $quizid, 0, false, MUST_EXIST);
    $quizid = $cm->instance;

    $context = context_module::instance($cm->id, MUST_EXIST);
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
    $rawobjects = optional_param('scan_objects', '{}', PARAM_RAW);
    $scanobjects = '';
    $scansuspicious = 0;

    $decoded = json_decode($rawobjects, true);

    if (is_array($decoded) && !empty($decoded)) {
        $scanobjects = json_encode($decoded);

        $flaggedclasses = [
            'cell phone',
            'book',
            'tablet',
            'remote',
            'extra_person',
            'face_turned',
        ];

        foreach ($flaggedclasses as $cls) {
            if (!empty($decoded[$cls])) {
                $scansuspicious = 1;
                break;
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

    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    ob_end_clean();

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
exit;
