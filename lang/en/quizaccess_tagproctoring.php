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
$string['csv_attempt_end'] = 'Attempt End';
$string['csv_attempt_num'] = 'Attempt #';
$string['csv_attempt_start'] = 'Attempt Start';
$string['csv_detail'] = 'Detail';
$string['csv_email'] = 'Email';
$string['csv_event_time'] = 'Event Time';
$string['csv_event_type'] = 'Event Type';
$string['csv_student_name'] = 'Student Name';
$string['deletescan_cancel'] = 'Cancel';
$string['deletescan_confirm'] = 'Yes, delete scan data';
$string['deletescan_description'] = 'This will permanently delete the room scan video, selfie photo, and ID photo for this student on this quiz. The quiz attempt, screenshots, and event log are not affected.';
$string['deletescan_heading'] = 'Delete scan data for {$a->user} (Quiz: {$a->quiz})?';
$string['deletescan_success'] = 'Scan data deleted.';
$string['deletescan_title'] = 'Delete Scan Data';
$string['duration'] = 'Room scan max duration (seconds)';
$string['email_attempt_body'] = 'A student has submitted a proctored quiz attempt with recorded proctoring flags.

Student:       {$a->student}
Quiz:          {$a->quiz}
Attempt:       #{$a->attempt}
Risk level:    {$a->risk}
Risk score:    {$a->score}
Screenshots:   {$a->screenshots}

Recorded flags:
{$a->events}

View the full proctoring report:
{$a->reporturl}

This email is sent after the quiz attempt is submitted. TAG Proctoring does not send live teacher alerts during the exam.';
$string['email_attempt_subject'] = 'TAG Proctoring Summary: {$a->risk} — {$a->student} — {$a->quiz}';
$string['email_highrisk_body'] = 'A student has completed a proctored quiz attempt with a HIGH RISK score.

Student:       {$a->student}
Quiz:          {$a->quiz}
Attempt:       #{$a->attempt}
Risk score:    {$a->score}

View the full proctoring report:
{$a->reporturl}

This is an automated message from TAG Proctoring.';
$string['email_highrisk_subject'] = 'Proctoring Alert: High Risk — {$a->student} — {$a->quiz}';
$string['email_no_events'] = 'No behavioural events recorded. The attempt was flagged by scan or identity checks.';
$string['email_recipient_lastname'] = 'Alerts';
$string['enableproctor'] = 'Enable TAG proctoring';
$string['enableroomscan'] = 'Require room scan for this exam';
$string['error_general'] = 'An error occurred. Please try again.';
$string['error_media_unavailable'] = 'Media devices are not available in this browser.';
$string['error_no_idphoto'] = 'No ID photo file received';
$string['error_no_screenshot'] = 'No screenshot received';
$string['error_no_selfie'] = 'No selfie file received';
$string['error_no_video'] = 'No video file received';
$string['event_cameradisabled'] = 'Camera disabled';
$string['event_capabilitywarning'] = 'Browser capability warning';
$string['event_contextmenu'] = 'Right-click detected';
$string['event_copypaste'] = 'Copy/paste detected';
$string['event_devtools'] = 'DevTools opened';
$string['event_faceaway'] = 'Face not visible';
$string['event_faceturn'] = 'Face turned away';
$string['event_fullscreenexit'] = 'Exited full screen';
$string['event_multiplefaces'] = 'Multiple faces detected';
$string['event_multiplescreen'] = 'Multiple screens detected';
$string['event_nocamera'] = 'Camera unavailable';
$string['event_noface'] = 'No face detected';
$string['event_screenshare'] = 'Screen share detected';
$string['event_screensharefailed'] = 'Screen share restart failed';
$string['event_screensharestop'] = 'Screen share stopped';
$string['event_sessionstart'] = 'Session started';
$string['event_tabswitch'] = 'Left exam view';
$string['event_visibilityhidden'] = 'Window hidden';
$string['event_windowblur'] = 'Switched away from exam';
$string['evidence_default'] = 'Evidence';
$string['evidence_screen'] = 'Screen evidence';
$string['evidence_screen_contextmenu'] = 'Screen evidence: right-click attempt';
$string['evidence_screen_tabswitch'] = 'Screen evidence: exam page hidden';
$string['evidence_screen_windowblur'] = 'Screen evidence: exam window lost focus';
$string['evidence_screencapture'] = 'Screen capture';
$string['evidence_webcam'] = 'Webcam evidence';
$string['evidence_webcam_faceaway'] = 'Webcam evidence: face not visible';
$string['evidence_webcam_faceturn'] = 'Webcam evidence: face movement';
$string['evidence_webcamcapture'] = 'Webcam capture';
$string['grades_col_proctoring'] = 'Proctoring';
$string['grades_status_high'] = 'High Risk';
$string['grades_status_low'] = 'Clean';
$string['grades_status_medium'] = 'Medium';
$string['idphotorequired'] = 'You must photograph your ID before starting the attempt.';
$string['license_activated'] = 'License activated successfully.';
$string['license_domain'] = 'Domain';
$string['license_expires'] = 'Expires';
$string['license_invalid_key'] = 'Invalid or revoked license key. Please check your key and try again.';
$string['license_notice'] = 'A free licence key is required to activate TAG Proctoring. Request your key at tandreig.com — keys are issued instantly and emailed to you.';
$string['license_plan'] = 'Plan';
$string['license_privacy_link'] = 'Privacy Policy';
$string['license_request_link'] = 'Get your free licence key';
$string['license_required_notice'] = 'TAG Proctoring requires a valid licence. Proctoring is currently inactive on this site. Enter a licence key under Site administration > Plugins > Quiz access rules > TAG Proctoring.';
$string['license_server_unreachable'] = 'License server unreachable. Please try again later.';
$string['license_status_expired'] = 'License Expired';
$string['license_status_grace'] = 'Grace Period — Renew Soon';
$string['license_status_unlicensed'] = 'Not Activated';
$string['license_status_valid'] = 'License Active';
$string['license_terms_link'] = 'Terms and Conditions';
$string['overlay_sharestop_body1'] = 'You stopped screen sharing. This has been recorded as a violation.';
$string['overlay_sharestop_body2'] = 'You must restart screen sharing to continue your exam.';
$string['overlay_sharestop_btn'] = 'Restart Screen Sharing';
$string['overlay_sharestop_title'] = 'Screen Sharing Stopped';
$string['pluginname'] = 'TAG Proctoring';
$string['privacy:metadata:external:licenseserver'] = 'This plugin contacts the TAG Proctoring licence server to activate and validate the site licence key. Only the site domain and licence key are transmitted. No personal user data is sent to this service.';
$string['privacy:metadata:external:modelcdn'] = 'During the room scan and in-exam monitoring, the student\'s browser downloads AI model files (BlazeFace and COCO-SSD) from Google\'s content delivery network. This is a standard file download; no personal data, webcam images, or exam content are sent to Google. All AI analysis runs locally in the student\'s browser.';
$string['privacy:metadata:quizaccess_tagproctoring_events'] = 'Stores suspicious behavioural events (tab switches, copy/paste, DevTools, etc.) logged during a quiz attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_events:attemptid'] = 'The quiz attempt this event belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_events:detail'] = 'Additional detail about the event.';
$string['privacy:metadata:quizaccess_tagproctoring_events:eventtype'] = 'The type of suspicious event detected.';
$string['privacy:metadata:quizaccess_tagproctoring_events:quizid'] = 'The quiz this event belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_events:timecreated'] = 'The time the event was logged.';
$string['privacy:metadata:quizaccess_tagproctoring_events:userid'] = 'The ID of the student who triggered the event.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides'] = 'Stores teacher review decisions and manual valid overrides for proctored quiz attempts.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:attemptid'] = 'The quiz attempt this review decision belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:overriddenby'] = 'The ID of the user who manually marked the attempt as valid.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:reviewed'] = 'Whether the proctoring attempt has been marked as reviewed.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:reviewedby'] = 'The ID of the user who marked the proctoring attempt as reviewed.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:timecreated'] = 'The time the manual override record was created.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:timereviewed'] = 'The time the proctoring attempt was marked as reviewed.';
$string['privacy:metadata:quizaccess_tagproctoring_overrides:valid_override'] = 'Whether the attempt was manually marked as valid.';
$string['privacy:metadata:quizaccess_tagproctoring_scan'] = 'Stores room scan video filenames, selfie and ID photo filenames, and AI scan results for each student attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:attemptid'] = 'The quiz attempt this scan belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:face_match_distance'] = 'Reserved field; not currently populated.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:face_match_verified'] = 'Whether an instructor has manually marked the student\'s identity as verified or failed after reviewing the selfie and ID photo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:filename'] = 'The filename of the room scan video.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:idphoto_filename'] = 'The filename of the student ID photo.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:needs_rescan'] = 'Whether a teacher has requested a new room scan for the student.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:quizid'] = 'The quiz this scan belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_objects'] = 'JSON list of objects detected in the room scan by the AI.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:scan_suspicious'] = 'Whether the AI flagged this scan as suspicious.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:selfie_filename'] = 'The filename of the student selfie image.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:timecreated'] = 'The time the scan record was created.';
$string['privacy:metadata:quizaccess_tagproctoring_scan:userid'] = 'The ID of the student who performed the room scan.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots'] = 'Stores periodic webcam screenshots captured during a quiz attempt.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:attemptid'] = 'The quiz attempt this screenshot belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:filename'] = 'The filename of the screenshot image.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:quizid'] = 'The quiz this screenshot belongs to.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:source'] = 'The source or trigger for the screenshot, such as webcam, screen, tab switch, or window blur.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:timecreated'] = 'The time the screenshot was captured.';
$string['privacy:metadata:quizaccess_tagproctoring_screenshots:userid'] = 'The ID of the student the screenshot belongs to.';
$string['privacy:path:events'] = 'Event Log';
$string['privacy:path:scans'] = 'Room Scans';
$string['privacy:path:screenshots'] = 'Screenshots';
$string['report_action_clear_override'] = 'Valid (Clear Override)';
$string['report_action_delete_events'] = 'Delete Events';
$string['report_action_delete_evidence'] = 'Delete Evidence';
$string['report_action_delete_scan'] = 'Delete Scan Data';
$string['report_action_mark_reviewed'] = 'Mark as Reviewed';
$string['report_action_mark_valid'] = 'Mark as Valid';
$string['report_action_quiz_results'] = 'Quiz Results';
$string['report_action_reviewed_undo'] = 'Reviewed (undo)';
$string['report_action_view_flags'] = 'View Flags';
$string['report_action_view_sessions'] = 'View Sessions';
$string['report_carousel_empty_attempt'] = 'No screenshot evidence captured for this attempt.';
$string['report_carousel_empty_default'] = 'No tab or window-switch screenshots captured for this attempt';
$string['report_carousel_evidence_title'] = 'Screen and Webcam Evidence';
$string['report_close'] = 'Close';
$string['report_col_attempts'] = 'Attempts';
$string['report_col_email'] = 'Email';
$string['report_col_last_attempt'] = 'Last Attempt';
$string['report_col_status'] = 'Status';
$string['report_col_student'] = 'Student';
$string['report_confirm_clear_override'] = 'Clear the Valid override? Automated risk assessment will apply again.';
$string['report_confirm_delete_events'] = 'Permanently delete all logged events for this attempt? This will also clear the risk flags.';
$string['report_confirm_delete_evidence'] = 'Permanently delete all screenshot evidence for this attempt?';
$string['report_confirm_mark_reviewed'] = 'Mark this attempt as reviewed?';
$string['report_confirm_mark_valid'] = 'Mark this attempt as Valid? This overrides the automated risk assessment.';
$string['report_confirm_unmark_reviewed'] = 'Unmark reviewed? Teachers will be able to mark it reviewed again.';
$string['report_contextmenu_note'] = 'Right-click screenshots are captured for new attempts after this update.';
$string['report_empty'] = 'No proctoring records found for this quiz.';
$string['report_export_csv'] = 'Export CSV';
$string['report_facematch_failed'] = 'Identity Failed';
$string['report_facematch_unverified'] = 'Pending Review';
$string['report_facematch_verified'] = 'Identity Verified';
$string['report_flags_no_violations'] = 'No violations detected.';
$string['report_flags_source_event'] = 'During Exam';
$string['report_flags_source_scan'] = 'Room Scan';
$string['report_grant_rescan'] = 'Grant Rescan';
$string['report_identity_mark_failed'] = 'Mark Failed';
$string['report_identity_mark_verified'] = 'Mark Verified';
$string['report_inforow_duration'] = 'Duration';
$string['report_inforow_end'] = 'End';
$string['report_inforow_events'] = 'Events';
$string['report_inforow_evidence'] = 'Evidence shots';
$string['report_inforow_start'] = 'Start';
$string['report_label_attempt_num'] = 'Attempt #';
$string['report_label_idphoto'] = 'ID Photo';
$string['report_label_selfie'] = 'Selfie';
$string['report_link_caption'] = 'Proctoring Report';
$string['report_link_label'] = 'Proctoring Report';
$string['report_mark_reviewed'] = 'Mark as Reviewed';
$string['report_nav_all_students'] = 'All Students';
$string['report_no_attempts'] = 'No completed attempts found for this quiz.';
$string['report_no_match'] = 'No students match your search.';
$string['report_potential_violations'] = 'Potential Violation of Rules';
$string['report_rescan_pending'] = 'Rescan Pending (cancel)';
$string['report_reviewed'] = 'Reviewed';
$string['report_risk_factors'] = 'Risk Factors';
$string['report_risk_high'] = 'High';
$string['report_risk_low'] = 'Low';
$string['report_risk_medium'] = 'Medium';
$string['report_risk_score'] = 'Risk Score: {$a}';
$string['report_risk_suspicious_scan'] = 'Suspicious objects in room scan';
$string['report_room_scan_video'] = 'Room Scan Video';
$string['report_rule_categories'] = 'Rule Categories';
$string['report_scan_evidence_in_video'] = 'Evidence in room scan video';
$string['report_scan_no_video'] = 'No room scan video available';
$string['report_search_placeholder'] = 'Search by name or email…';
$string['report_stat_high_risk'] = 'High Risk';
$string['report_stat_needs_review'] = 'Needs Review';
$string['report_stat_total_attempts'] = 'Total Attempts';
$string['report_stat_valid'] = 'Valid';
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
$string['report_unmark_reviewed'] = 'Reviewed (click to unmark)';
$string['rescanperattempt'] = 'Require new room scan for each attempt';
$string['risklabel_invalid'] = 'Invalid';
$string['risklabel_review'] = 'Needs Review';
$string['risklabel_valid'] = 'Valid';
$string['scan_detected_objects'] = 'Detected Objects';
$string['scan_flagged'] = 'Flagged — Suspicious Objects';
$string['scan_no_objects'] = 'No suspicious objects detected';
$string['scan_object_book'] = 'Book';
$string['scan_object_cellphone'] = 'Cell phone';
$string['scan_object_extraperson'] = 'Extra person in room';
$string['scan_object_laptop'] = 'Laptop (extra)';
$string['scan_object_remote'] = 'Remote control';
$string['scan_object_tablet'] = 'Tablet';
$string['scan_violation_book'] = 'Book / Notes Detected';
$string['scan_violation_cellphone'] = 'Phone Detected';
$string['scan_violation_extraperson'] = 'Extra Person in Room';
$string['scan_violation_faceturned'] = 'Looking Away from Screen';
$string['scan_violation_multiplefaces'] = 'Multiple Faces Detected';
$string['scan_violation_noface'] = 'No Face Detected';
$string['scan_violation_remote'] = 'Remote Control Detected';
$string['scan_violation_tablet'] = 'Tablet Detected';
$string['scanrequired'] = 'You must complete the room scan before starting the attempt.';
$string['selfierequired'] = 'You must complete the selfie verification step before starting the attempt.';
$string['settings_ai_confidence_threshold'] = 'Object detection confidence threshold';
$string['settings_ai_confidence_threshold_default'] = '0.35';
$string['settings_ai_confidence_threshold_desc'] = 'Minimum confidence score (0.0–1.0) for the AI to count a detected object. Lower values detect more objects but increase false positives. Default: 0.35.';
$string['settings_ai_flagged_objects'] = 'Flagged object types';
$string['settings_ai_flagged_objects_default'] = "cell phone\nbook\ntablet\nremote\nextra_person\nlaptop";
$string['settings_ai_flagged_objects_desc'] = 'One object class per line. Objects in this list trigger a suspicious scan flag when detected above the minimum threshold. Default classes: cell phone, book, tablet, remote, extra_person, laptop.';
$string['settings_ai_min_detections'] = 'Minimum detections before flagging';
$string['settings_ai_min_detections_desc'] = 'How many times an object must appear (each interval is ~1.5 s) before it is marked suspicious. Higher values reduce false positives. Default: 3.';
$string['settings_detection'] = 'AI Detection';
$string['settings_emailalerts'] = 'Email teachers after flagged attempts';
$string['settings_emailalerts_desc'] = 'After a student submits the quiz, send one summary email when the attempt contains proctoring flags.';
$string['settings_emailrecipients'] = 'Alert recipient email addresses';
$string['settings_emailrecipients_desc'] = 'Enter the email addresses that should receive TAG Proctoring summaries after flagged quiz attempts. Separate multiple addresses with commas or new lines. Leave empty to send to quiz teachers who can view the proctoring report.';
$string['settings_enableevents'] = 'Enable suspicious event tracking';
$string['settings_enableevents_desc'] = 'Log tab switches, copy/paste, DevTools, and other suspicious events.';
$string['settings_enableroomscan'] = 'Enable room scan step';
$string['settings_enableroomscan_desc'] = 'When enabled, students must record a short room scan video before starting the exam.';
$string['settings_enablescreenshots'] = 'Enable webcam screenshots';
$string['settings_enablescreenshots_desc'] = 'Capture periodic webcam screenshots during quiz attempts.';
$string['settings_faceturn_sensitivity'] = 'Face-turn sensitivity';
$string['settings_faceturn_sensitivity_default'] = '0.30';
$string['settings_faceturn_sensitivity_desc'] = 'Nose-to-eye ratio threshold for detecting a deliberate look-away (0.0–1.0). Lower values are more sensitive. Default: 0.30 (~17° head turn).';
$string['settings_general'] = 'TAG Proctoring Settings';
$string['settings_license'] = 'TAG Proctoring Licence';
$string['settings_license_desc'] = 'Enter the licence key issued for this site. The key is validated by the TAG Proctoring licence service and bound to your site\'s domain.';
$string['settings_license_key'] = 'Licence key';
$string['settings_license_key_desc'] = 'Paste your licence key here and save. Activation is automatic.';
$string['settings_maxevents'] = 'Max event logs per attempt';
$string['settings_maxevents_desc'] = 'Maximum number of suspicious event rows to store for one quiz attempt. This protects the database during large concurrent exams. Default: 200.';
$string['settings_maxscreenshots'] = 'Max screenshots per attempt';
$string['settings_maxscreenshots_desc'] = 'Maximum number of webcam screenshots to store per quiz attempt.';
$string['settings_retentiondays'] = 'Data retention period (days)';
$string['settings_retentiondays_desc'] = 'Number of days to keep room scan videos and screenshots before automatic deletion.';
$string['settings_risk'] = 'Risk Scoring';
$string['settings_risk_desc'] = 'Points awarded for each suspicious event type. The total score determines the risk level shown in the proctoring report.';
$string['settings_risk_score_copypaste'] = 'Score: copy/paste detected';
$string['settings_risk_score_devtools'] = 'Score: DevTools opened';
$string['settings_risk_score_fullscreenexit'] = 'Score: exited full screen';
$string['settings_risk_score_multiplescreen'] = 'Score: multiple screens detected';
$string['settings_risk_score_screenshare'] = 'Score: screen share detected';
$string['settings_risk_score_suspicious_scan'] = 'Score: suspicious room scan';
$string['settings_risk_score_tabswitch'] = 'Score: tab switched';
$string['settings_risk_score_visibilityhidden'] = 'Score: window hidden';
$string['settings_risk_threshold_high'] = 'High risk threshold';
$string['settings_risk_threshold_high_desc'] = 'Minimum score to classify an attempt as High risk. Default: 50.';
$string['settings_risk_threshold_medium'] = 'Medium risk threshold';
$string['settings_risk_threshold_medium_desc'] = 'Minimum score to classify an attempt as Medium risk. Default: 20.';
$string['settings_scanduration'] = 'Room scan maximum duration (seconds)';
$string['settings_scanduration_desc'] = 'Maximum length of the room scan recording (default 45 seconds). Minimum enforced is 30 seconds.';
$string['settings_screenshotinterval'] = 'Screenshot interval (seconds)';
$string['settings_screenshotinterval_desc'] = 'How often (in seconds) a webcam screenshot is captured during an attempt.';
$string['summary_label_capabilitywarning'] = 'browser capability warning';
$string['summary_label_contextmenu'] = 'right-click attempt';
$string['summary_label_copypaste'] = 'copy/paste';
$string['summary_label_devtools'] = 'devtools open';
$string['summary_label_faceaway'] = 'face not visible';
$string['summary_label_fullscreenexit'] = 'fullscreen exit';
$string['summary_label_multiplefaces'] = 'multiple faces';
$string['summary_label_multiplescreen'] = 'multiple screen';
$string['summary_label_noface'] = 'no face detected';
$string['summary_label_screenshare'] = 'screen share event';
$string['summary_label_screensharestop'] = 'screen share stop';
$string['summary_label_tabswitch'] = 'exam page hidden';
$string['summary_label_visibilityhidden'] = 'tab hidden';
$string['summary_label_windowblur'] = 'exam window focus loss';
$string['summary_noviolations'] = 'No violations detected during this exam session.';
$string['summary_session'] = 'Session Summary';
$string['summary_violations_prefix'] = 'Student completed exam with:';
$string['tagproctoring:bypassproctoring'] = 'Bypass TAG proctoring checks';
$string['tagproctoring:manage'] = 'Manage TAG proctoring data';
$string['tagproctoring:viewreport'] = 'View TAG proctoring reports';
$string['task_cleanup_files'] = 'TAG Proctoring: delete expired scan videos and screenshots';
$string['task_renew_license'] = 'TAG Proctoring: weekly license token renewal';
$string['violation_capabilitywarning'] = 'Browser Capability Warning';
$string['violation_contextmenu'] = 'Right-Click Menu';
$string['violation_copypaste'] = 'Copy / Paste Activity';
$string['violation_devtools'] = 'Developer Tools Open';
$string['violation_faceaway'] = 'Looking Away from Screen';
$string['violation_faceturn'] = 'Face Turned Away';
$string['violation_fullscreenexit'] = 'Fullscreen Exited';
$string['violation_multiplefaces'] = 'Multiple Faces During Exam';
$string['violation_multiplescreen'] = 'Multiple Screens Detected';
$string['violation_nocamera'] = 'Camera Unavailable';
$string['violation_noface'] = 'No Face Detected';
$string['violation_screenshare'] = 'Screen Sharing Initiated';
$string['violation_screensharestop'] = 'Screen Share Stopped';
$string['violation_tabswitch'] = 'Left Exam View';
$string['violation_visibilityhidden'] = 'Tab Hidden';
$string['violation_windowblur'] = 'Exam Window Lost Focus';
$string['wizard_btn_next'] = 'Next';
$string['wizard_btn_next_step'] = 'Next';
$string['wizard_btn_recheck'] = 'Re-check setup';
$string['wizard_btn_retake'] = 'Retake';
$string['wizard_btn_share_screen'] = 'Share screen';
$string['wizard_btn_start_exam'] = 'Start Secure Exam';
$string['wizard_btn_start_scan'] = 'Start room scan';
$string['wizard_btn_take_id_photo'] = 'Take ID photo';
$string['wizard_btn_take_photo'] = 'Take photo';
$string['wizard_captured'] = 'Captured';
$string['wizard_checklist_title'] = 'You are ready to begin.';
$string['wizard_chk_fullscreen'] = 'Fullscreen starts with the exam';
$string['wizard_chk_idphoto'] = 'Photo ID captured';
$string['wizard_chk_rules'] = 'Rules accepted';
$string['wizard_chk_scan'] = 'Room scan completed';
$string['wizard_chk_screenshare'] = 'Screen-share instructions reviewed';
$string['wizard_chk_selfie'] = 'Identity selfie saved';
$string['wizard_consent_warn'] = 'Violations will be flagged and reviewed. Repeated violations may result in your exam being disqualified.';
$string['wizard_hero_sub'] = 'A short guided setup before your quiz starts. Keep your camera ready and follow each step.';
$string['wizard_hero_title'] = 'Exam Readiness Check';
$string['wizard_id_checklist_1'] = 'Keep your face visible above or beside the ID.';
$string['wizard_id_checklist_2'] = 'The name, photo, and ID number must be readable.';
$string['wizard_id_checklist_3'] = 'Hold still until the capture completes.';
$string['wizard_id_checklist_title'] = 'ID photo checklist';
$string['wizard_id_guide_label'] = 'Place your ID inside the green box';
$string['wizard_id_tip'] = 'Use the green border guide to align your ID card.';
$string['wizard_roomscan_intro'] = 'Slowly show your desk and surroundings. Remove phones, notes, books, and other unauthorised items before recording.';
$string['wizard_scan_checklist_1'] = 'Allow camera access when prompted.';
$string['wizard_scan_checklist_2'] = 'Slowly pan 360 degrees around your room.';
$string['wizard_scan_checklist_3'] = 'Show your desk, keyboard, floor, and surrounding area.';
$string['wizard_scan_checklist_4'] = 'Show there are no phones, notes, books, or other people.';
$string['wizard_scan_checklist_5'] = 'Recording stops automatically after the configured duration.';
$string['wizard_scan_checklist_title'] = 'Scan checklist';
$string['wizard_scan_success'] = 'Room scan uploaded successfully.';
$string['wizard_selfie_checklist_1'] = 'Look directly at the camera.';
$string['wizard_selfie_checklist_2'] = 'Keep your face well-lit and centered in the oval.';
$string['wizard_selfie_checklist_3'] = 'Remove hats or anything that hides your face.';
$string['wizard_selfie_checklist_4'] = 'The photo can capture automatically after a stable face is detected.';
$string['wizard_selfie_checklist_title'] = 'Selfie checklist';
$string['wizard_slide0_heading'] = 'This quiz uses proctoring checks.';
$string['wizard_slide0_label'] = 'Secure Exam';
$string['wizard_slide0_sub'] = 'You will complete a room scan, selfie, ID photo, and screen-share prompt so your attempt can be reviewed fairly.';
$string['wizard_slide1_heading'] = 'Keep your face visible to the webcam at all times.';
$string['wizard_slide1_label'] = 'Rule 1 — Webcam';
$string['wizard_slide1_sub'] = 'Do not leave camera view, cover your face, or obstruct the lens during the exam.';
$string['wizard_slide2_heading'] = 'You must be alone. No other people may be present in the room.';
$string['wizard_slide2_label'] = 'Rule 2 — Alone';
$string['wizard_slide2_sub'] = 'Family members, colleagues, or anyone else must leave the room before and during the exam.';
$string['wizard_slide3_heading'] = 'Do not use messaging apps during the exam.';
$string['wizard_slide3_label'] = 'Rule 3 — Messaging';
$string['wizard_slide3_sub'] = 'Zoom, Slack, Discord, Teams, chat tools, or any communication software is strictly prohibited.';
$string['wizard_slide4_heading'] = 'Do not talk to another person or receive any help.';
$string['wizard_slide4_label'] = 'Rule 4 — No Help';
$string['wizard_slide4_sub'] = 'You must complete the exam entirely on your own without speaking to or receiving assistance from anyone.';
$string['wizard_slide5_heading'] = 'Do not use AI tools, remote access, or automation software.';
$string['wizard_slide5_label'] = 'Rule 5 — AI & Tools';
$string['wizard_slide5_sub'] = 'ChatGPT, Copilot, remote desktop software, or any tool that could assist with answers is not permitted.';
$string['wizard_slide6_heading'] = 'No phones, tablets, headphones, books, notes, or external materials.';
$string['wizard_slide6_label'] = 'Rule 6 — Devices';
$string['wizard_slide6_sub'] = 'All unauthorised devices and materials must be removed from your desk and workspace before starting.';
$string['wizard_slide7_heading'] = 'You must complete a room and desk scan before starting.';
$string['wizard_slide7_label'] = 'Rule 7 — Room Scan';
$string['wizard_slide7_sub'] = 'Pan your webcam around the entire room to show your environment is free of prohibited items.';
$string['wizard_slide8_heading'] = 'Use one monitor, stay in fullscreen, and keep screen sharing active.';
$string['wizard_slide8_label'] = 'Rule 8 — Screen';
$string['wizard_slide8_sub'] = 'Disconnecting your screen share, exiting fullscreen, or using an extra display will be recorded as a violation.';
$string['wizard_slide9_heading'] = 'You are ready for the check-in steps.';
$string['wizard_slide9_label'] = 'Confirmation';
$string['wizard_slide9_sub'] = 'By clicking I Agree & Continue, you confirm you understand the rules and will complete the required checks honestly.';
$string['wizard_ss_before_title'] = 'One more browser prompt will appear after you press Start Secure Exam.';
$string['wizard_ss_guide_step1'] = 'The quiz page opens first';
$string['wizard_ss_guide_step1_warn'] = 'Stay on the quiz page while it loads';
$string['wizard_ss_guide_step2'] = 'When asked, choose Entire Screen';
$string['wizard_ss_guide_step3'] = 'Keep screen sharing active until you submit';
$string['wizard_ss_multimon_desc'] = 'Disconnect secondary monitors or set displays to Mirror / Duplicate mode before continuing.';
$string['wizard_ss_multimon_header'] = 'Additional monitor detected';
$string['wizard_step0_sub'] = 'Review the essentials. Each card is short, and you stay in control of the next step.';
$string['wizard_step0_title'] = 'Before You Begin';
$string['wizard_step1_sub'] = 'Show your space clearly before the quiz opens.';
$string['wizard_step1_title'] = 'Room and Desk Scan';
$string['wizard_step2_sub'] = 'Take a clear, well-lit photo so your identity can be reviewed.';
$string['wizard_step2_title'] = 'Identity Selfie';
$string['wizard_step3_sub'] = 'Hold your photo ID steady and keep your face visible.';
$string['wizard_step3_title'] = 'Photo ID Verification';
$string['wizard_step4_sub'] = 'Your setup checks are complete. The screen-share prompt will appear once the quiz attempt opens.';
$string['wizard_step4_title'] = 'Ready to Start';
$string['wizard_step_idphoto'] = 'ID Photo';
$string['wizard_step_roomscan'] = 'Room Scan';
$string['wizard_step_rules'] = 'Rules';
$string['wizard_step_screen'] = 'Screen';
$string['wizard_step_selfie'] = 'Selfie';
