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
 * Admin settings for TAG Proctoring quiz access rule.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom admin setting for the license key.
 *
 * Extends the standard text field to:
 *  - Trigger license activation whenever the key is saved.
 *  - Display the current license status badge below the input.
 */
if (!class_exists('admin_setting_tagproctoring_licensekey')) {
class admin_setting_tagproctoring_licensekey extends admin_setting_configtext {

    /**
     * Save the key and immediately attempt activation.
     *
     * @param string $data Value submitted by the form.
     * @return string Empty string on success, error string on failure.
     */
    public function write_setting($data) {
        $result = parent::write_setting($data);
        if ($result === '') {
            \quizaccess_tagproctoring\license::activate($data);
        }
        return $result;
    }

    /**
     * Render the setting HTML with a live status badge appended.
     *
     * @param string $data Current saved value.
     * @param string $query Search query (for highlighting).
     * @return string HTML.
     */
    public function output_html($data, $query = '') {
        $html = parent::output_html($data, $query);

        $status = \quizaccess_tagproctoring\license::get_status();
        $info   = \quizaccess_tagproctoring\license::get_info();

        $badges = [
            \quizaccess_tagproctoring\license::STATUS_VALID      => ['success',   get_string('license_status_valid',      'quizaccess_tagproctoring')],
            \quizaccess_tagproctoring\license::STATUS_GRACE      => ['warning',   get_string('license_status_grace',      'quizaccess_tagproctoring')],
            \quizaccess_tagproctoring\license::STATUS_EXPIRED    => ['danger',    get_string('license_status_expired',    'quizaccess_tagproctoring')],
            \quizaccess_tagproctoring\license::STATUS_UNLICENSED => ['secondary', get_string('license_status_unlicensed', 'quizaccess_tagproctoring')],
        ];

        [$cls, $label] = $badges[$status] ?? ['secondary', $status];

        $badge = '<span class="badge badge-' . $cls . ' p-2" style="font-size:0.85em">' . $label . '</span>';

        $detail = '';
        if ($info) {
            $plan = !empty($info->plan) ? ucfirst($info->plan) : '';
            if ($plan) {
                $detail .= ' &nbsp;<strong>' . get_string('license_plan', 'quizaccess_tagproctoring') . ':</strong> ' . htmlspecialchars($plan);
            }
            if (!empty($info->expires_at)) {
                $detail .= ' &nbsp;<strong>' . get_string('license_expires', 'quizaccess_tagproctoring') . ':</strong> ' . userdate($info->expires_at, get_string('strftimedate', 'langconfig'));
            }
            if (!empty($info->domain)) {
                $detail .= ' &nbsp;<strong>' . get_string('license_domain', 'quizaccess_tagproctoring') . ':</strong> ' . htmlspecialchars($info->domain);
            }
            if (!empty($info->local)) {
                $detail .= ' &nbsp;<span class="text-muted small">' . get_string('license_local_note', 'quizaccess_tagproctoring') . '</span>';
            }
        }

        return $html . '<div class="mt-2 mb-1">' . $badge . $detail . '</div>';
    }
}
} // end class_exists guard

if ($ADMIN->fulltree) {

    // -------------------------------------------------------------------------
    // License
    // -------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'quizaccess_tagproctoring/license_heading',
        get_string('settings_license', 'quizaccess_tagproctoring'),
        get_string('settings_license_desc', 'quizaccess_tagproctoring')
    ));

    $settings->add(new admin_setting_tagproctoring_licensekey(
        'quizaccess_tagproctoring/license_key',
        get_string('settings_license_key', 'quizaccess_tagproctoring'),
        get_string('settings_license_key_desc', 'quizaccess_tagproctoring'),
        '',
        PARAM_RAW_TRIMMED
    ));

    // -------------------------------------------------------------------------
    // General
    // -------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'quizaccess_tagproctoring/general',
        get_string('settings_general', 'quizaccess_tagproctoring'),
        ''
    ));

    // Enable room scan step (master switch — overrides per-quiz setting).
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_tagproctoring/enableroomscan',
        get_string('settings_enableroomscan', 'quizaccess_tagproctoring'),
        get_string('settings_enableroomscan_desc', 'quizaccess_tagproctoring'),
        1
    ));

    // Room scan duration global default.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/scanduration',
        get_string('settings_scanduration', 'quizaccess_tagproctoring'),
        get_string('settings_scanduration_desc', 'quizaccess_tagproctoring'),
        45,
        PARAM_INT
    ));

    // Retention days for videos and screenshots.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/retentiondays',
        get_string('settings_retentiondays', 'quizaccess_tagproctoring'),
        get_string('settings_retentiondays_desc', 'quizaccess_tagproctoring'),
        30,
        PARAM_INT
    ));

    // Screenshot interval.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/screenshotinterval',
        get_string('settings_screenshotinterval', 'quizaccess_tagproctoring'),
        get_string('settings_screenshotinterval_desc', 'quizaccess_tagproctoring'),
        30,
        PARAM_INT
    ));

    // Enable screenshots.
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_tagproctoring/enablescreenshots',
        get_string('settings_enablescreenshots', 'quizaccess_tagproctoring'),
        get_string('settings_enablescreenshots_desc', 'quizaccess_tagproctoring'),
        1
    ));

    // Enable event tracking.
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_tagproctoring/enableevents',
        get_string('settings_enableevents', 'quizaccess_tagproctoring'),
        get_string('settings_enableevents_desc', 'quizaccess_tagproctoring'),
        1
    ));

    // Max screenshots per attempt.
    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/maxscreenshots',
        get_string('settings_maxscreenshots', 'quizaccess_tagproctoring'),
        get_string('settings_maxscreenshots_desc', 'quizaccess_tagproctoring'),
        20,
        PARAM_INT
    ));

    // -------------------------------------------------------------------------
    // Detection
    // -------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'quizaccess_tagproctoring/detection_heading',
        get_string('settings_detection', 'quizaccess_tagproctoring'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/ai_confidence_threshold',
        get_string('settings_ai_confidence_threshold', 'quizaccess_tagproctoring'),
        get_string('settings_ai_confidence_threshold_desc', 'quizaccess_tagproctoring'),
        '0.35',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/ai_min_detections',
        get_string('settings_ai_min_detections', 'quizaccess_tagproctoring'),
        get_string('settings_ai_min_detections_desc', 'quizaccess_tagproctoring'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtextarea(
        'quizaccess_tagproctoring/ai_flagged_objects',
        get_string('settings_ai_flagged_objects', 'quizaccess_tagproctoring'),
        get_string('settings_ai_flagged_objects_desc', 'quizaccess_tagproctoring'),
        "cell phone\nbook\ntablet\nremote\nextra_person\nlaptop",
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/faceturn_sensitivity',
        get_string('settings_faceturn_sensitivity', 'quizaccess_tagproctoring'),
        get_string('settings_faceturn_sensitivity_desc', 'quizaccess_tagproctoring'),
        '0.30',
        PARAM_TEXT
    ));

    // -------------------------------------------------------------------------
    // Risk Scoring
    // -------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'quizaccess_tagproctoring/risk_heading',
        get_string('settings_risk', 'quizaccess_tagproctoring'),
        get_string('settings_risk_desc', 'quizaccess_tagproctoring')
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_threshold_high',
        get_string('settings_risk_threshold_high', 'quizaccess_tagproctoring'),
        get_string('settings_risk_threshold_high_desc', 'quizaccess_tagproctoring'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_threshold_medium',
        get_string('settings_risk_threshold_medium', 'quizaccess_tagproctoring'),
        get_string('settings_risk_threshold_medium_desc', 'quizaccess_tagproctoring'),
        20,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_screenshare',
        get_string('settings_risk_score_screenshare', 'quizaccess_tagproctoring'),
        '',
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_multiplescreen',
        get_string('settings_risk_score_multiplescreen', 'quizaccess_tagproctoring'),
        '',
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_fullscreenexit',
        get_string('settings_risk_score_fullscreenexit', 'quizaccess_tagproctoring'),
        '',
        20,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_tabswitch',
        get_string('settings_risk_score_tabswitch', 'quizaccess_tagproctoring'),
        '',
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_visibilityhidden',
        get_string('settings_risk_score_visibilityhidden', 'quizaccess_tagproctoring'),
        '',
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_copypaste',
        get_string('settings_risk_score_copypaste', 'quizaccess_tagproctoring'),
        '',
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_devtools',
        get_string('settings_risk_score_devtools', 'quizaccess_tagproctoring'),
        '',
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_tagproctoring/risk_score_suspicious_scan',
        get_string('settings_risk_score_suspicious_scan', 'quizaccess_tagproctoring'),
        '',
        30,
        PARAM_INT
    ));
}
