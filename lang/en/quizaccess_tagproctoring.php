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
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consentlabel'] = 'I consent to recording a short room scan video for exam integrity purposes.';
$string['duration'] = 'Room scan max duration (seconds)';
$string['enableroomscan'] = 'Require room scan for this exam';
$string['enableproctor'] = 'Enable TAG proctoring';

$string['event_cameradisabled'] = 'Camera disabled';
$string['event_contextmenu'] = 'Right-click detected';
$string['event_copypaste'] = 'Copy/paste detected';
$string['event_devtools'] = 'DevTools opened';
$string['event_faceturn'] = 'Face turned away';
$string['event_fullscreenexit'] = 'Exited full screen';
$string['event_multiplescreen'] = 'Multiple screens detected';
$string['event_nocamera'] = 'Camera unavailable';
$string['event_screenshare'] = 'Screen share detected';
$string['event_screensharestop'] = 'Screen share stopped';
$string['event_tabswitch'] = 'Tab switched';
$string['event_visibilityhidden'] = 'Window hidden';
$string['event_windowblur'] = 'Window lost focus';

// Violation labels used in the proctoring report viewer.
$string['violation_tabswitch'] = 'Tab Switch / Focus Loss';
$string['violation_visibilityhidden'] = 'Tab Hidden';
$string['violation_fullscreenexit'] = 'Fullscreen Exited';
$string['violation_screenshare'] = 'Screen Sharing Initiated';
$string['violation_multiplescreen'] = 'Multiple Screens Detected';
$string['violation_copypaste'] = 'Copy / Paste Activity';
$string['violation_contextmenu'] = 'Right-Click Menu';
$string['violation_devtools'] = 'Developer Tools Open';
$string['violation_nocamera'] = 'Camera Unavailable';
$string['violation_noface'] = 'No Face Detected';
$string['violation_faceaway'] = 'Looking Away from Screen';
$string['violation_faceturn'] = 'Face Turned Away';
$string['violation_multiplefaces'] = 'Multiple Faces During Exam';

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

$string['settings_enableroomscan'] = 'Enable room scan step';
$string['settings_enableroomscan_desc'] = 'When enabled, students must record a short room scan video before starting the exam.';
$string['settings_scanduration'] = 'Room scan maximum duration (seconds)';
$string['settings_scanduration_desc'] = 'Maximum length of the room scan recording (default 45 seconds). Minimum enforced is 30 seconds.';
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
$string['task_renew_license'] = 'TAG Proctoring: weekly license token renewal';

// License settings.
$string['settings_license'] = 'TAG Proctoring License';
$string['settings_license_desc'] = 'Enter the license key provided by TAG Web Design. To request a free 60-day trial key, contact <a href="mailto:andrei@tandreig.com">andrei@tandreig.com</a>.';
$string['settings_license_key'] = 'License key';
$string['settings_license_key_desc'] = 'Paste your license key here and save. Activation is automatic.';

// License status badges.
$string['license_status_valid']      = 'License Active';
$string['license_status_grace']      = 'Grace Period — Renew Soon';
$string['license_status_expired']    = 'License Expired';
$string['license_status_unlicensed'] = 'Not Activated';

// License detail labels.
$string['license_plan']    = 'Plan';
$string['license_expires'] = 'Expires';
$string['license_domain']  = 'Domain';

// License activation messages.
$string['license_activated']         = 'License activated successfully.';
$string['license_invalid_key']       = 'Invalid or revoked license key. Please check your key and try again.';
$string['license_server_unreachable'] = 'License server unreachable. Please try again later.';
$string['license_trial_local']       = 'Trial token issued locally (license server not yet reachable). Valid for 60 days.';

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

// Grades table proctoring column.
$string['grades_col_proctoring'] = 'Proctoring';
$string['grades_status_high'] = 'High Risk';
$string['grades_status_medium'] = 'Medium';
$string['grades_status_low'] = 'Clean';

// Report page.
$string['report_no_attempts'] = 'No completed attempts found for this quiz.';

// Rule link.
$string['report_link_label'] = 'Proctoring Report';

// Detection settings.
$string['settings_detection'] = 'AI Detection';
$string['settings_ai_confidence_threshold'] = 'Object detection confidence threshold';
$string['settings_ai_confidence_threshold_desc'] = 'Minimum confidence score (0.0–1.0) for the AI to count a detected object. Lower values detect more objects but increase false positives. Default: 0.35.';
$string['settings_ai_min_detections'] = 'Minimum detections before flagging';
$string['settings_ai_min_detections_desc'] = 'How many times an object must appear (each interval is ~1.5 s) before it is marked suspicious. Higher values reduce false positives. Default: 3.';
$string['settings_ai_flagged_objects'] = 'Flagged object types';
$string['settings_ai_flagged_objects_desc'] = 'One object class per line. Objects in this list trigger a suspicious scan flag when detected above the minimum threshold. Default classes: cell phone, book, tablet, remote, extra_person, laptop.';
$string['settings_faceturn_sensitivity'] = 'Face-turn sensitivity';
$string['settings_faceturn_sensitivity_desc'] = 'Nose-to-eye ratio threshold for detecting a deliberate look-away (0.0–1.0). Lower values are more sensitive. Default: 0.30 (~17° head turn).';

// Risk scoring settings.
$string['settings_risk'] = 'Risk Scoring';
$string['settings_risk_desc'] = 'Points awarded for each suspicious event type. The total score determines the risk level shown in the proctoring report.';
$string['settings_risk_threshold_high'] = 'High risk threshold';
$string['settings_risk_threshold_high_desc'] = 'Minimum score to classify an attempt as High risk. Default: 50.';
$string['settings_risk_threshold_medium'] = 'Medium risk threshold';
$string['settings_risk_threshold_medium_desc'] = 'Minimum score to classify an attempt as Medium risk. Default: 20.';
$string['settings_risk_score_screenshare'] = 'Score: screen share detected';
$string['settings_risk_score_multiplescreen'] = 'Score: multiple screens detected';
$string['settings_risk_score_fullscreenexit'] = 'Score: exited full screen';
$string['settings_risk_score_tabswitch'] = 'Score: tab switched';
$string['settings_risk_score_visibilityhidden'] = 'Score: window hidden';
$string['settings_risk_score_copypaste'] = 'Score: copy/paste detected';
$string['settings_risk_score_devtools'] = 'Score: DevTools opened';
$string['settings_risk_score_suspicious_scan'] = 'Score: suspicious room scan';

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
