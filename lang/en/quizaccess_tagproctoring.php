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
 * English language strings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'I consent to recording a short room scan video for exam integrity purposes.';
$string['duration'] = 'Room scan duration (seconds)';
$string['enableproctor'] = 'Enable TAG proctoring';

$string['event_cameradisabled'] = 'Camera disabled';
$string['event_contextmenu'] = 'Right-click detected';
$string['event_copypaste'] = 'Copy/paste detected';
$string['event_devtools'] = 'DevTools opened';
$string['event_fullscreenexit'] = 'Exited full screen';
$string['event_multiplescreen'] = 'Multiple screens detected';
$string['event_nocamera'] = 'Camera unavailable';
$string['event_screenshare'] = 'Screen share detected';
$string['event_screensharestop'] = 'Screen share stopped';
$string['event_tabswitch'] = 'Tab switched';
$string['event_visibilityhidden'] = 'Window hidden';

$string['idphotorequired'] = 'You must photograph your ID before starting the attempt.';
$string['pluginname'] = 'TAG Proctoring';

$string['report_empty'] = 'No proctoring records found for this quiz.';
$string['report_export_csv'] = 'Export CSV';
$string['report_link_caption'] = 'Proctoring Report';

$string['report_risk_factors'] = 'Risk Factors';
$string['report_risk_high'] = 'High';
$string['report_risk_low'] = 'Low';
$string['report_risk_medium'] = 'Medium';
$string['report_risk_score'] = 'Risk Score: {$a}';
$string['report_risk_suspicious_scan'] = 'Suspicious objects in room scan';

$string['report_table_header_attempt'] = '#';
$string['report_table_header_attempt_results'] = 'Attempt';
$string['report_table_header_delete_scan'] = 'Delete Scan';
$string['report_table_header_email'] = 'Email';
$string['report_table_header_events'] = 'Events';
$string['report_table_header_name'] = 'Student Name';
$string['report_table_header_risk'] = 'Risk Level';
$string['report_table_header_room_scan'] = 'Room Scan';
$string['report_table_header_score'] = 'Score';
$string['report_table_header_screenshots'] = 'Screenshots';
$string['report_table_header_time_taken'] = 'Time Taken';
$string['report_table_header_timefinish'] = 'Finished';

$string['report_table_row_no_scan'] = 'No scan';
$string['report_table_row_no_screenshots'] = 'None';
$string['report_table_row_notyetgraded'] = 'Not yet graded';
$string['report_table_row_overdue'] = 'Overtime by {$a}';
$string['report_table_row_view_scan'] = 'View Scan';
$string['report_table_row_view_screenshots'] = 'View ({$a})';

$string['scan_detected_objects'] = 'Detected Objects';
$string['scan_flagged'] = 'Flagged — Suspicious Objects';
$string['scan_no_objects'] = 'No suspicious objects detected';
$string['scan_object_book'] = 'Book';
$string['scan_object_cellphone'] = 'Cell phone';
$string['scan_object_extraperson'] = 'Extra person in room';
$string['scan_object_laptop'] = 'Laptop (extra)';
$string['scan_object_remote'] = 'Remote control';
$string['scan_object_tablet'] = 'Tablet';

$string['scanrequired'] = 'You must complete the room scan before starting the attempt.';
$string['selfierequired'] = 'You must complete the selfie verification step before starting the attempt.';

$string['settings_enableevents'] = 'Enable suspicious event tracking';
$string['settings_enableevents_desc'] = 'Log tab switches, copy/paste, DevTools, and other suspicious events.';
$string['settings_enablescreenshots'] = 'Enable webcam screenshots';
$string['settings_enablescreenshots_desc'] = 'Capture periodic webcam screenshots during quiz attempts.';
$string['settings_general'] = 'TAG Proctoring Settings';
$string['settings_maxscreenshots'] = 'Max screenshots per attempt';
$string['settings_maxscreenshots_desc'] = 'Maximum number of webcam screenshots to store per quiz attempt.';
$string['settings_retentiondays'] = 'Data retention period (days)';
$string['settings_retentiondays_desc'] = 'Number of days to keep room scan videos and screenshots before automatic deletion.';
$string['settings_screenshotinterval'] = 'Screenshot interval (seconds)';
$string['settings_screenshotinterval_desc'] = 'How often (in seconds) a webcam screenshot is captured during an attempt.';

$string['tagproctoring:viewreport'] = 'View TAG proctoring reports';
$string['task_cleanup_files'] = 'TAG Proctoring: delete expired scan videos and screenshots';

// Upload endpoints.
$string['error_no_video'] = 'No video file received';
$string['error_no_selfie'] = 'No selfie file received';
$string['error_no_screenshot'] = 'No screenshot received';
$string['error_no_idphoto'] = 'No ID photo file received';

// Delete scan page.
$string['deletescan_title'] = 'Delete Scan Data';
$string['deletescan_heading'] = 'Delete scan data for {$a->user} (Quiz: {$a->quiz})?';
$string['deletescan_description'] = 'This will permanently delete the room scan video, selfie photo, and ID photo for this student on this quiz. The quiz attempt, screenshots, and event log are not affected.';
$string['deletescan_confirm'] = 'Yes, delete scan data';
$string['deletescan_cancel'] = 'Cancel';
$string['deletescan_success'] = 'Scan data deleted.';

// Screenshots page.
$string['screenshots_title'] = 'Screenshots — {$a}';
$string['screenshots_inprogress'] = 'In progress';
$string['screenshots_attemptinfo'] = 'Attempt started: {$a->started} | Finished: {$a->finished}';
$string['screenshots_events_heading'] = 'Suspicious Events';
$string['screenshots_col_time'] = 'Time';
$string['screenshots_col_event'] = 'Event';
$string['screenshots_col_detail'] = 'Detail';
$string['screenshots_grid_heading'] = 'Webcam Screenshots ({$a})';
$string['screenshots_none'] = 'No screenshots captured for this attempt.';

// Report page.
$string['report_no_attempts'] = 'No completed attempts found for this quiz.';

// Rule link.
$string['report_link_label'] = 'Proctoring Report';

// Privacy API.
$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Stores room scan video filenames, selfie and ID photo filenames, and AI scan results for each student attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'The ID of the student who performed the room scan.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'The quiz this scan belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'The quiz attempt this scan belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'The time the scan record was created.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'The filename of the room scan video.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'The filename of the student selfie image.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'The filename of the student ID photo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON list of objects detected in the room scan by the AI.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Whether the AI flagged this scan as suspicious.';

$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Stores periodic webcam screenshots captured during a quiz attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'The ID of the student the screenshot belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'The quiz this screenshot belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'The quiz attempt this screenshot belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'The time the screenshot was captured.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'The filename of the screenshot image.';

$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Stores suspicious behavioural events (tab switches, copy/paste, DevTools, etc.) logged during a quiz attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'The ID of the student who triggered the event.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'The quiz this event belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'The quiz attempt this event belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'The time the event was logged.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'The type of suspicious event detected.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Additional detail about the event.';

$string['privacy:path:scans'] = 'Room Scans';
$string['privacy:path:screenshots'] = 'Screenshots';
$string['privacy:path:events'] = 'Event Log';
