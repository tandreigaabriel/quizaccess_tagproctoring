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
 * Shared translation cache for proctoring UI event handlers.
 * @module quizaccess_tagproctoring/strings
 * @copyright 2026 Andrei Toma
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(Str) {
    'use strict';
    var cache = {};
    var pending;
    var keys = [
        "mmr_overridden",
        "monitor_begin",
        "monitor_critical",
        "monitor_missing",
        "monitor_multiple_title",
        "monitor_repeated",
        "monitor_select_screen",
        "monitor_violation_count",
        "overlay_sharestop_body1",
        "overlay_sharestop_body2",
        "overlay_sharestop_btn",
        "overlay_sharestop_title",
        "report_error",
        "report_flagged_count",
        "report_hours_minutes",
        "report_minutes",
        "report_screenshot_count",
        "report_unknown_error",
        "ui_a_second_screen_has_been_detected_you_must_disconnect_it_before_continuing",
        "ui_after_you_start_choose_entire_screen_when_your_browser_asks",
        "ui_camera_access_denied_please_allow_camera_access_in_your_browser_and_reload",
        "ui_camera_unavailable",
        "ui_centre_your_face_above_the_box_and_hold_your_id_inside_it",
        "ui_centre_your_face_in_the_oval",
        "ui_centre_your_face_in_the_oval_then_click_take_photo",
        "ui_clean",
        "ui_copy_event",
        "ui_critical_multiple_violations",
        "ui_cut_event",
        "ui_desktop_or_laptop_required",
        "ui_empty_upload_response",
        "ui_enter_full_screen",
        "ui_exam_monitoring_alert",
        "ui_exam_page_was_hidden_count",
        "ui_face_and_id_detected_hold_steady",
        "ui_face_detected_hold_still",
        "ui_face_not_visible_for_9_seconds",
        "ui_full_screen_required",
        "ui_further_violations_will_result_in_your_exam_being_flagged_for_review",
        "ui_high_risk",
        "ui_hold_still",
        "ui_id_photo_saved_your_invigilator_will_verify_your_identity",
        "ui_image_too_blurry_hold_the_camera_steady",
        "ui_improve_room_lighting",
        "ui_lighting_too_dark",
        "ui_lighting_too_dark_improve_your_room_lighting",
        "ui_lighting_too_dark_move_to_a_brighter_spot",
        "ui_loading_face_detection",
        "ui_media_devices_are_not_available_in_this_browser",
        "ui_medium",
        "ui_move_closer_your_face_is_too_small",
        "ui_move_your_face_higher_in_the_frame",
        "ui_move_your_face_to_the_upper_part_of_the_frame_and_hold_the_id_below",
        "ui_multiple_faces_detected_ensure_only_you_are_visible",
        "ui_multiple_monitor_detection_unavailable_in",
        "ui_network_error_deleting_events",
        "ui_network_error_deleting_screenshots",
        "ui_network_error_saving_identity_status",
        "ui_network_error_saving_override",
        "ui_network_error_saving_reviewed_status",
        "ui_no_face_detected_centre_your_face_in_the_oval",
        "ui_no_face_detected_show_your_face_alongside_your_id",
        "ui_once_disconnected_reload_this_page_to_continue_your_exam",
        "ui_paste_event",
        "ui_place_your_id_inside_the_green_box",
        "ui_please_hold_the_id_steady",
        "ui_please_retry",
        "ui_please_return_to_fullscreen_immediately_to_continue_your_exam",
        "ui_please_use_a_desktop_or_laptop_computer_with_a_current_browser",
        "ui_possible_devtools_open_w",
        "ui_proctoring",
        "ui_prohibited_item",
        "ui_recording_slowly_pan_360_around_the_room_and_desk",
        "ui_redo_room_scan",
        "ui_repeated_violation_recorded",
        "ui_retry_recording",
        "ui_return_to_full_screen",
        "ui_return_to_full_screen_now",
        "ui_return_to_fullscreen_immediately_to_continue_your_exam",
        "ui_right_click_detected",
        "ui_room_scan_complete",
        "ui_screen_share_stopped",
        "ui_screen_share_stream_ended_during_exam",
        "ui_screen_shared",
        "ui_screen_sharing",
        "ui_secure_exams_cannot_be_taken_on_a_mobile_phone_or_tablet",
        "ui_secure_https_context",
        "ui_selfie_saved_you_can_continue",
        "ui_show_your_face_above_the_id_box",
        "ui_starting",
        "ui_stop_recording",
        "ui_student_exited_fullscreen_count",
        "ui_student_failed_to_restart_screen_sharing",
        "ui_student_initiated_screen_share",
        "ui_student_switched_away_from_the_exam_window",
        "ui_this_exam_must_be_taken_in_full_screen_with_screen_sharing_active",
        "ui_this_secure_exam_requires_camera_screen_sharing_fullscreen_and_https_support",
        "ui_unknown_browser",
        "ui_unknown_error",
        "ui_unknown_os",
        "ui_unsupported_browser",
        "ui_upload_error",
        "ui_upload_error_please_retake",
        "ui_upload_failed_please_retake",
        "ui_uploading",
        "ui_uploading_id_photo",
        "ui_uploading_room_scan",
        "ui_uploading_selfie",
        "ui_video_recording_could_not_start_in_this_browser_please_use_a_current_desktop_brows",
        "ui_video_recording_is_not_supported_in_this_browser_please_use_a_current_desktop_brow",
        "ui_webcam_access",
        "ui_webcam_active",
        "ui_webcam_inactive",
        "ui_webcam_unavailable_during_attempt",
        "ui_you_exited_fullscreen_this_has_been_recorded_as_a_violation",
        "wizard_detected",
        "wizard_id_unreadable",
        "wizard_photo_rejected",
        "wizard_rule_agree",
        "wizard_rule_counter",
        "wizard_rule_next",
        "wizard_scan_progress",
        "wizard_seconds",
        "wizard_start_countdown",
        "wizard_upload_failed"
    ];
    return {
        /** @returns {Promise} Resolves when all UI translations are ready. */
        load: function() {
            if (!pending) {
                pending = Str.get_strings(keys.map(function(key) {
                    return {key: key, component: 'quizaccess_tagproctoring'};
                })).then(function(values) {
                    keys.forEach(function(key, i) { cache[key] = values[i]; });
                });
            }
            return pending;
        },
        /**
         * Read a preloaded string and substitute Moodle scalar or object placeholders.
         * @param {string} key Language identifier.
         * @param {*} value Placeholder value.
         * @returns {string} Localised text; callers insert it using textContent or Mustache escaping.
         */
        get: function(key, value) {
            var text = cache[key];
            if (value !== undefined) {
                if (value !== null && typeof value === 'object') {
                    Object.keys(value).forEach(function(name) {
                        text = text.split('{$a->' + name + '}').join(String(value[name]));
                    });
                } else {
                    text = text.split('{$a}').join(String(value));
                }
            }
            return text;
        }
    };
});
