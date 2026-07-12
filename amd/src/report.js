// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TAG Proctoring report UI — student list, session cards, violation flags, screenshot viewer.
 *
 * @module     quizaccess_tagproctoring/report
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("quizaccess_tagproctoring/report", ['core/str'], function(Str) {
    'use strict';

    // Populated by init().
    var DATA = null;
    var CFG2 = {};
    // Loaded from Moodle lang strings — populated before first render.
    var S = {};

    var VIOLATIONS = {
        tabswitch:        {label: '', icon: '🔄', sev: 'medium'},
        windowblur:       {label: '', icon: '🔄', sev: 'medium'},
        visibilityhidden: {label: '', icon: '👁',  sev: 'medium'},
        fullscreenexit:   {label: '', icon: '⛶',        sev: 'medium'},
        screenshare:      {label: '', icon: '📺',  sev: 'critical'},
        screensharestop:  {label: '', icon: '🖥',  sev: 'high'},
        multiplescreen:   {label: '', icon: '🖥',  sev: 'critical'},
        copypaste:        {label: '', icon: '📋',  sev: 'medium'},
        contextmenu:      {label: '', icon: '🖱',  sev: 'low'},
        devtools:         {label: '', icon: '🔧',  sev: 'high'},
        capabilitywarning:{label: '', icon: '⚠',        sev: 'medium'},
        nocamera:         {label: '', icon: '📷',  sev: 'high'},
        noface:           {label: '', icon: '🚫',  sev: 'high'},
        faceaway:         {label: '', icon: '👤',  sev: 'low'},
        faceturn:         {label: '', icon: '👤',  sev: 'low'},
        multiplefaces:    {label: '', icon: '👥',  sev: 'critical'},
    };

    var SCAN_VIOLATIONS = {
        'cell phone':    {label: '', icon: '📱', sev: 'critical'},
        'book':          {label: '', icon: '📚', sev: 'critical'},
        'tablet':        {label: '', icon: '📱', sev: 'critical'},
        'remote':        {label: '', icon: '🎮', sev: 'high'},
        'extra_person':  {label: '', icon: '👥', sev: 'critical'},
        'face_turned':   {label: '', icon: '👤', sev: 'high'},
        'no_face':       {label: '', icon: '🚫', sev: 'high'},
        'multiple_faces':{label: '', icon: '👥', sev: 'critical'},
    };

    var SEV_COLOR  = {critical: '#dc2626', high: '#d97706', medium: '#2563eb', low: '#6b7280'};
    var RISK_COLOR = {invalid: '#dc2626', review: '#d97706', valid: '#16a34a'};
    var RISK_LABEL = {invalid: '', review: '', valid: ''};
    var SEV_ORDER  = {critical: 0, high: 1, medium: 2, low: 3};

    // State.
    var currentStudent = null;
    var currentAttempt = null;
    var currentCats    = [];

    /**
     * Return the element with the given ID.
     *
     * @param {string} id Element ID.
     * @returns {HTMLElement|null} The element.
     */
    function el(id) {
        return document.getElementById(id);
    }

    /**
     * Escape HTML special characters.
     *
     * @param {*} s Value to escape.
     * @returns {string} HTML-safe string.
     */
    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Return a readable evidence source label.
     *
     * @param {string}  source   Stored screenshot source token.
     * @param {boolean} longform Whether to return a lightbox-style label.
     * @returns {string} Human-readable label.
     */
    function evidenceSourceLabel(source, longform) {
        if (longform && source === 'screen') {
            return S.evidence_screencapture || 'Screen capture';
        }
        if (longform && source === 'webcam') {
            return S.evidence_webcamcapture || 'Webcam capture';
        }
        var labels = {
            screen:      S.evidence_screen,
            tabswitch:   S.evidence_screen_tabswitch,
            windowblur:  S.evidence_screen_windowblur,
            contextmenu: S.evidence_screen_contextmenu,
            webcam:      S.evidence_webcam,
            faceaway:    S.evidence_webcam_faceaway,
            faceturn:    S.evidence_webcam_faceturn,
        };
        return labels[source] || (source ? source.replace(/_/g, ' ') : (S.evidence_default || 'Evidence'));
    }

    /**
     * Format a Unix timestamp as short date and time.
     *
     * @param {number} ts Unix timestamp (seconds).
     * @returns {string} Formatted date-time string, or em-dash for falsy input.
     */
    function fmtDateTime(ts) {
        if (!ts) {
            return '—';
        }
        var d = new Date(ts * 1000);
        return d.toLocaleDateString(undefined, {day: '2-digit', month: 'short'}) +
            ' ' + d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    }

    /**
     * Format the duration between two Unix timestamps as a human-readable string.
     *
     * @param {number} start Unix start timestamp (seconds).
     * @param {number} end   Unix end timestamp (seconds).
     * @returns {string} Duration string (e.g. "42 min" or "1h 5m"), or em-dash.
     */
    function fmtDuration(start, end) {
        if (!start || !end) {
            return '—';
        }
        var m = Math.round((end - start) / 60);
        return m >= 60 ? Math.floor(m / 60) + 'h ' + (m % 60) + 'm' : m + ' min';
    }

    /**
     * Return the effective risk level for an attempt, accounting for overrides.
     *
     * @param {Object} a Attempt data object.
     * @returns {string} Risk level: 'valid', 'review', or 'invalid'.
     */
    function effectiveRisk(a) {
        return a.override_valid ? 'valid' : a.risk_level;
    }

    /**
     * Return the worst risk level across all attempts for a student.
     *
     * @param {Object} s Student data object with an attempts array.
     * @returns {string} Worst risk level.
     */
    function overallRisk(s) {
        var worst = 'valid';
        s.attempts.forEach(function(a) {
            var rl = effectiveRisk(a);
            if (rl === 'invalid') {
                worst = 'invalid';
            } else if (rl === 'review' && worst !== 'invalid') {
                worst = 'review';
            }
        });
        return worst;
    }

    /**
     * Build a coloured risk badge HTML string.
     *
     * @param {string} level Risk level key ('valid', 'review', 'invalid').
     * @param {string} extra Optional suffix text appended to the label.
     * @returns {string} HTML badge element string.
     */
    function riskBadge(level, extra) {
        var label = extra ? (RISK_LABEL[level] + ' ' + extra) : RISK_LABEL[level];
        return '<span class="tagrep-badge" style="background:' + RISK_COLOR[level] + ';">' + label + '</span>';
    }

    /**
     * Build a plain-English AI summary block for a single attempt.
     *
     * @param {Object} attempt Attempt data object.
     * @returns {string} HTML string for the summary panel.
     */
    function buildSummary(attempt) {
        var counts = {};
        attempt.events.forEach(function(e) {
            if (e.type !== 'sessionstart') {
                counts[e.type] = (counts[e.type] || 0) + 1;
            }
        });

        var parts = [];
        var LABELS = {
            tabswitch:         S.summary_label_tabswitch,
            visibilityhidden:  S.summary_label_visibilityhidden,
            windowblur:        S.summary_label_windowblur,
            fullscreenexit:    S.summary_label_fullscreenexit,
            screenshare:       S.summary_label_screenshare,
            screensharestop:   S.summary_label_screensharestop,
            multiplescreen:    S.summary_label_multiplescreen,
            copypaste:         S.summary_label_copypaste,
            devtools:          S.summary_label_devtools,
            faceaway:          S.summary_label_faceaway,
            noface:            S.summary_label_noface,
            multiplefaces:     S.summary_label_multiplefaces,
            contextmenu:       S.summary_label_contextmenu,
            capabilitywarning: S.summary_label_capabilitywarning,
        };

        // High-risk first, then medium/low.
        var order = ['screenshare', 'multiplescreen', 'screensharestop', 'multiplefaces',
                     'fullscreenexit', 'tabswitch', 'visibilityhidden',
                     'faceaway', 'noface', 'capabilitywarning', 'copypaste', 'devtools', 'contextmenu'];
        order.forEach(function(t) {
            if (counts[t]) {
                var n = counts[t];
                var lbl = LABELS[t] || t;
                parts.push(n + ' ' + lbl + (n > 1 && lbl.slice(-1) !== 's' ? 's' : ''));
            }
        });

        var scan = attempt.scan;
        var suspChips = '';
        if (scan && scan.objects && Object.keys(scan.objects).length) {
            Object.keys(scan.objects).forEach(function(cls) {
                var def = SCAN_VIOLATIONS[cls] || {label: cls, sev: 'high'};
                suspChips += '<span class="tagrep-sum-chip" style="background:' +
                    SEV_COLOR[def.sev] + ';">' + esc(def.label) + '</span>';
            });
        }

        var dispRisk = effectiveRisk(attempt);
        var riskColor = RISK_COLOR[dispRisk];
        var riskIcon  = dispRisk === 'invalid' ? '❌' : (dispRisk === 'review' ? '⚠️' : '✅');
        var riskTxt   = riskIcon + ' ' + RISK_LABEL[dispRisk];
        if (attempt.risk_score) {
            riskTxt += ' (' + attempt.risk_score + '/100)';
        }

        var body = parts.length
            ? (S.summary_violations_prefix || 'Student completed exam with:') + ' ' + parts.join(', ') + '.'
            : (S.summary_noviolations || 'No violations detected during this exam session.');

        return '<div class="tagrep-ai-summary">' +
            '<div class="tagrep-ai-summary-hd">' + esc(S.summary_session || 'Session Summary') + '</div>' +
            '<p class="tagrep-ai-summary-body">' + esc(body) + '</p>' +
            (suspChips ? '<div class="tagrep-ai-summary-chips">' + suspChips + '</div>' : '') +
            '<div class="tagrep-ai-summary-risk" style="color:' + riskColor + ';">' +
            riskTxt + '</div>' +
            '</div>';
    }

    /**
     * Build a label/value info-row HTML string.
     *
     * @param {string} label Row label text.
     * @param {string} value Row value text.
     * @returns {string} HTML div string.
     */
    function infoRow(label, value) {
        return '<div class="tagrep-info-row">' +
            '<span class="tagrep-info-lbl">' + esc(label) + '</span>' +
            '<span class="tagrep-info-val">' + esc(value) + '</span>' +
            '</div>';
    }

    /**
     * Switch the visible panel and update breadcrumb state.
     *
     * @param {string} panel Panel name: 'students', 'sessions', or 'flags'.
     */
    function tagRepNav(panel) {
        el('tagrep-panel-students').style.display = panel === 'students' ? '' : 'none';
        el('tagrep-panel-sessions').style.display = panel === 'sessions' ? '' : 'none';
        el('tagrep-panel-flags').style.display    = panel === 'flags'    ? '' : 'none';

        el('tagrep-bc-students').className = 'tagrep-nav-item' +
            (panel === 'students' ? ' tagrep-nav-item--active' : '');

        var showS = panel !== 'students' && currentStudent;
        el('tagrep-bc-sep1').style.display     = showS ? '' : 'none';
        el('tagrep-bc-sessions').style.display = showS ? '' : 'none';
        el('tagrep-bc-sessions').className     = 'tagrep-nav-item' +
            (panel === 'sessions' ? ' tagrep-nav-item--active' : '');
        if (currentStudent) {
            el('tagrep-bc-sessions').textContent = currentStudent.name;
        }

        var showF = panel === 'flags';
        el('tagrep-bc-sep2').style.display  = showF ? '' : 'none';
        el('tagrep-bc-flags').style.display = showF ? '' : 'none';
        if (showF) {
            el('tagrep-bc-flags').textContent =
                (S.report_potential_violations || 'Potential Violation of Rules') +
                ' — ' + (S.report_label_attempt_num || 'Attempt #') +
                (currentAttempt ? currentAttempt.attempt_num : '');
        }
    }

    /**
     * Render the student list table rows from the given data array.
     *
     * @param {Array} data Array of student data objects to display.
     */
    function renderStudentList(data) {
        var tbody = el('tagrep-tbody');
        var empty = el('tagrep-empty');
        if (!data.length) {
            tbody.innerHTML = '';
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';
        var rows = '';
        data.forEach(function(s) {
            var last = s.attempts[s.attempts.length - 1];
            rows += '<tr>' +
                '<td><strong>' + esc(s.name) + '</strong></td>' +
                '<td class="tagrep-muted">' + esc(s.email) + '</td>' +
                '<td class="tagrep-center">' + s.attempts.length + '</td>' +
                '<td class="tagrep-muted">' + fmtDateTime(last ? last.timestart : 0) + '</td>' +
                '<td>' + riskBadge(overallRisk(s)) + '</td>' +
                '<td class="tagrep-td-action">' +
                '<button class="tagrep-btn tagrep-btn--primary" onclick="tagRepOpenStudent(' + s.userid + ')">' +
                esc(S.report_action_view_sessions || 'View Sessions') + ' →' +
                '</button>' +
                '</td>' +
                '</tr>';
        });
        tbody.innerHTML = rows;
    }

    /**
     * Filter and re-render the student list based on a search query.
     *
     * @param {string} q Search string to match against name or email.
     */
    function tagRepSearch(q) {
        q = q.toLowerCase();
        renderStudentList(DATA.filter(function(s) {
            return s.name.toLowerCase().indexOf(q) !== -1 ||
                s.email.toLowerCase().indexOf(q) !== -1;
        }));
    }

    /**
     * Open the sessions panel for the student with the given user ID.
     *
     * @param {number} uid User ID of the student to view.
     */
    function tagRepOpenStudent(uid) {
        currentStudent = DATA.find(function(s) {
            return s.userid === uid;
        });
        if (!currentStudent) {
            return;
        }
        renderSessions(currentStudent);
        tagRepNav('sessions');
    }

    /**
     * Render the session cards for a student into the sessions panel.
     *
     * @param {Object} student Student data object.
     */
    function renderSessions(student) {
        var html = '<div class="tagrep-card">' +
            '<div class="tagrep-card-hd">' +
            '<div><h4 class="tagrep-title">' + esc(student.name) + '</h4>' +
            '<p class="tagrep-subtitle">' + esc(student.email) +
            ' &nbsp;·&nbsp; ' + student.attempts.length + ' attempt(s)</p></div>' +
            '</div>' +
            '<div class="tagrep-sessions-grid">';

        student.attempts.forEach(function(a) {
            var scan = a.scan;

            html += '<div class="tagrep-session-card">';

            var dispRisk = effectiveRisk(a);
            html += '<div class="tagrep-session-hd">' +
                '<span class="tagrep-session-num">' +
                esc(S.report_label_attempt_num || 'Attempt #') + a.attempt_num +
                '</span>' +
                riskBadge(dispRisk, a.override_valid ? '✓ Overridden' : '') +
                '</div>';

            html += buildSummary(a);

            html += '<div class="tagrep-session-body">';

            // Selfie + ID photo side by side with face-match badge.
            html += '<div class="tagrep-session-photo">';
            html += '<div style="display:flex;gap:8px;align-items:flex-start;">';
            if (scan && scan.selfie_url) {
                html += '<div style="text-align:center;">' +
                    '<div style="font-size:10px;color:#6b7280;margin-bottom:2px;">' +
                    esc(S.report_label_selfie || 'Selfie') + '</div>' +
                    '<img src="' + esc(scan.selfie_url) + '" class="tagrep-selfie-img"' +
                    ' alt="' + esc(S.report_label_selfie || 'Selfie') + '"' +
                    ' onclick="window.open(\'' + esc(scan.selfie_url) + '\',\'_blank\')"' +
                    ' style="cursor:zoom-in;">' +
                    '</div>';
            } else {
                html += '<div class="tagrep-selfie-ph">No<br>' +
                    esc(S.report_label_selfie || 'Selfie') + '</div>';
            }
            if (scan && scan.idphoto_url) {
                html += '<div style="text-align:center;">' +
                    '<div style="font-size:10px;color:#6b7280;margin-bottom:2px;">' +
                    esc(S.report_label_idphoto || 'ID Photo') + '</div>' +
                    '<img src="' + esc(scan.idphoto_url) + '" class="tagrep-selfie-img"' +
                    ' alt="' + esc(S.report_label_idphoto || 'ID Photo') + '"' +
                    ' onclick="window.open(\'' + esc(scan.idphoto_url) + '\',\'_blank\')"' +
                    ' style="cursor:zoom-in;">' +
                    '</div>';
            }
            html += '</div>';

            // Face-match badge.
            if (scan) {
                var fmv = scan.face_match_verified;
                var fmd = scan.face_match_distance;
                var fmBadge, fmColor;
                if (fmv === 1) {
                    fmBadge = '✅ ' + esc(S.report_facematch_verified || 'Face Match') +
                        ' (' + (fmd !== null ? (fmd * 100).toFixed(0) + '%' : '') + ')';
                    fmColor = '#16a34a';
                } else if (fmv === 0) {
                    fmBadge = '❌ ' + esc(S.report_facematch_failed || 'Face Mismatch') +
                        ' (' + (fmd !== null ? (fmd * 100).toFixed(0) + '%' : '') + ')';
                    fmColor = '#dc2626';
                } else {
                    fmBadge = '❓ ' + esc(S.report_facematch_unverified || 'ID Not Verified');
                    fmColor = '#6b7280';
                }
                html += '<div style="margin-top:6px;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700;' +
                    'color:#fff;background:' + fmColor + ';display:inline-block;">' + fmBadge + '</div>';
            }

            html += '</div>';

            html += '<div class="tagrep-session-info">';
            html += infoRow(S.report_inforow_start    || 'Start',           fmtDateTime(a.timestart));
            html += infoRow(S.report_inforow_end      || 'End',             fmtDateTime(a.timefinish));
            html += infoRow(S.report_inforow_duration || 'Duration',        fmtDuration(a.timestart, a.timefinish));
            html += infoRow(S.report_inforow_events   || 'Events',          a.events.length + ' logged');
            html += infoRow(S.report_inforow_evidence || 'Evidence shots',  a.screenshots.length + ' stored');
            html += '</div>';
            html += '</div>';

            if (scan && scan.objects && Object.keys(scan.objects).length) {
                html += '<div class="tagrep-scan-chips">';
                Object.keys(scan.objects).forEach(function(cls) {
                    var def = SCAN_VIOLATIONS[cls] || {label: cls, sev: 'medium'};
                    html += '<span class="tagrep-scan-chip" style="background:' + SEV_COLOR[def.sev] + ';">' +
                        esc(def.label) + ' &times;' + scan.objects[cls] + '</span>';
                });
                html += '</div>';
            }

            html += '<div class="tagrep-session-actions">';
            if (a.events.length > 0 || (scan && scan.suspicious)) {
                html += '<button class="tagrep-btn tagrep-btn--danger"' +
                    ' onclick="tagRepOpenFlags(' + student.userid + ',' + a.id + ')">' +
                    '⚑ ' + esc(S.report_action_view_flags || 'View Flags') + '</button> ';
            }
            if (a.override_valid) {
                html += '<button class="tagrep-btn" style="background:#16a34a;color:#fff;"' +
                    ' onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',0)">' +
                    '✓ ' + esc(S.report_action_clear_override || 'Valid (Clear Override)') + '</button> ';
            } else {
                html += '<button class="tagrep-btn tagrep-btn--outline"' +
                    ' onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',1)">' +
                    '✓ ' + esc(S.report_action_mark_valid || 'Mark as Valid') + '</button> ';
            }
            if (a.reviewed) {
                html += '<button class="tagrep-btn" style="background:#6b7280;color:#fff;"' +
                    ' onclick="tagRepSetReviewed(' + student.userid + ',' + a.id + ',0)">' +
                    '👁 ' + esc(S.report_action_reviewed_undo || 'Reviewed (undo)') + '</button> ';
            } else {
                html += '<button class="tagrep-btn tagrep-btn--outline"' +
                    ' onclick="tagRepSetReviewed(' + student.userid + ',' + a.id + ',1)">' +
                    '👁 ' + esc(S.report_action_mark_reviewed || 'Mark as Reviewed') + '</button> ';
            }
            html += '<a href="' + esc(a.review_url) +
                '" target="_blank" class="tagrep-btn tagrep-btn--outline">' +
                esc(S.report_action_quiz_results || 'Quiz Results') + ' ↗</a>';

            html += '</div>';

            if (a.events.length > 0 || a.screenshots.length > 0 ||
                    (scan && (scan.video_url || scan.selfie_url))) {
                html += '<div class="tagrep-session-deletes">';
                if (a.events.length > 0) {
                    html += '<button class="tagrep-btn tagrep-btn--delete"' +
                        ' onclick="tagRepDeleteEvents(' + student.userid + ',' + a.id + ')">' +
                        '🗑 ' + esc(S.report_action_delete_events || 'Delete Events') +
                        ' (' + a.events.length + ')</button>';
                }
                if (a.screenshots.length > 0) {
                    html += '<button class="tagrep-btn tagrep-btn--delete"' +
                        ' onclick="tagRepDeleteScreenshots(' + student.userid + ',' + a.id + ')">' +
                        '🗑 ' + esc(S.report_action_delete_evidence || 'Delete Evidence') +
                        ' (' + a.screenshots.length + ')</button>';
                }
                if (scan && (scan.video_url || scan.selfie_url)) {
                    var durl = CFG2.deleteScanBase + '&userid=' + student.userid;
                    html += '<a href="' + esc(durl) + '" class="tagrep-btn tagrep-btn--delete">' +
                        '🗑 ' + esc(S.report_action_delete_scan || 'Delete Scan Data') + '</a>';
                }
                html += '</div>';
            }

            html += '</div>';
        });

        html += '</div></div>';
        el('tagrep-sessions-content').innerHTML = html;
    }

    /**
     * Open the flags panel for the given student's attempt.
     *
     * @param {number} uid       User ID of the student.
     * @param {number} attemptId Attempt ID to show flags for.
     */
    function tagRepOpenFlags(uid, attemptId) {
        currentStudent = DATA.find(function(s) {
            return s.userid === uid;
        });
        currentAttempt = currentStudent ?
            currentStudent.attempts.find(function(a) {
                return a.id === attemptId;
            }) :
            null;
        if (!currentAttempt) {
            return;
        }

        el('tagrep-flags-title').textContent =
            currentStudent.name + ' — ' +
            (S.report_potential_violations || 'Potential Violation of Rules');

        // Show session-start browser/OS info in the subtitle if available.
        var sessionStartEvent = currentAttempt.events.find(function(e) {
            return e.type === 'sessionstart';
        });
        var subText = fmtDateTime(currentAttempt.timestart) +
            '  →  ' + fmtDateTime(currentAttempt.timefinish);
        if (sessionStartEvent && sessionStartEvent.detail) {
            subText += '  ·  💻 ' + sessionStartEvent.detail;
        }
        el('tagrep-flags-sub').textContent = subText;

        currentCats = [];

        var eCounts = {};
        currentAttempt.events.forEach(function(e) {
            if (e.type === 'sessionstart' || e.type === 'faceturn') {
                return; // informational only — exclude from violation categories
            }
            eCounts[e.type] = (eCounts[e.type] || 0) + 1;
        });
        Object.keys(eCounts).forEach(function(type) {
            var def = VIOLATIONS[type] || {label: type, icon: '⚠', sev: 'medium'};
            currentCats.push({
                id: 'ev_' + type,
                label: def.label,
                icon: def.icon,
                sev: def.sev,
                count: eCounts[type],
                source: 'event',
                event_type: type,
            });
        });

        var scan = currentAttempt.scan;
        if (scan && scan.objects) {
            Object.keys(scan.objects).forEach(function(cls) {
                var def = SCAN_VIOLATIONS[cls] || {label: cls, icon: '⚠', sev: 'medium'};
                currentCats.push({
                    id: 'sc_' + cls.replace(/\s/g, '_'),
                    label: def.label,
                    icon: def.icon,
                    sev: def.sev,
                    count: scan.objects[cls],
                    source: 'scan',
                    scan_class: cls,
                });
            });
        }

        currentCats.sort(function(a, b) {
            return (SEV_ORDER[a.sev] || 9) - (SEV_ORDER[b.sev] || 9);
        });

        var listHtml = '';
        if (!currentCats.length) {
            listHtml = '<p class="tagrep-no-flags">' +
                esc(S.report_flags_no_violations || 'No violations detected.') + '</p>';
        } else {
            currentCats.forEach(function(cat, i) {
                listHtml += '<div class="tagrep-cat-item" id="tagrep-cat-' + i +
                    '" onclick="tagRepSelectCat(' + i + ')">' +
                    '<span class="tagrep-cat-icon">' + cat.icon + '</span>' +
                    '<div class="tagrep-cat-text">' +
                    '<span class="tagrep-cat-label">' + esc(cat.label) + '</span>' +
                    '<span class="tagrep-cat-src">&middot; ' +
                    esc(cat.source === 'scan'
                        ? (S.report_flags_source_scan  || 'Room Scan')
                        : (S.report_flags_source_event || 'During Exam')) +
                    '</span>' +
                    '</div>' +
                    '<span class="tagrep-cat-badge" style="background:' + SEV_COLOR[cat.sev] + ';">' +
                    cat.count + '</span>' +
                    '</div>';
            });
        }
        el('tagrep-cat-list').innerHTML = listHtml;

        var scanSec = el('tagrep-scan-section');
        var scanVid = el('tagrep-scan-video');
        if (scan && scan.video_url) {
            scanVid.src = scan.video_url;
            scanSec.style.display = '';
        } else {
            scanSec.style.display = 'none';
            scanVid.src = '';
        }

        resetViewer();
        tagRepNav('flags');
    }

    /**
     * Reset the carousel to its default (all screenshots, no highlight) state.
     * Requires currentAttempt to be set before calling.
     */
    function resetViewer() {
        el('tagrep-carousel-title').textContent =
            S.report_carousel_evidence_title || 'Screen and Webcam Evidence';
        el('tagrep-carousel-count').textContent = '';
        if (currentAttempt) {
            renderCarousel(null, '');
        } else {
            el('tagrep-carousel').style.display = 'none';
            el('tagrep-carousel-empty').style.display = '';
        }
    }

    /**
     * Render the screenshot carousel for the current attempt.
     * Thumbnails within 90 s of any time in highlightTimes receive a highlight border.
     * Thumbnails within 5 s of any violation event receive a red dot.
     * Clicking a thumbnail opens the lightbox.
     *
     * @param {Array|null} highlightTimes Unix timestamps to highlight around, or null for no filter.
     * @param {string}     note           Optional override text shown in the count area.
     */
    function renderCarousel(highlightTimes, note) {
        var shots = currentAttempt ? currentAttempt.screenshots : [];
        var studentName = currentStudent ? currentStudent.name : '';

        if (!shots.length) {
            el('tagrep-carousel').style.display = 'none';
            el('tagrep-carousel-empty').style.display = '';
            el('tagrep-carousel-empty').innerHTML =
                note || (S.report_carousel_empty_attempt || 'No screenshot evidence captured for this attempt.');
            el('tagrep-carousel-count').textContent = typeof note === 'string' ? note : '';
            return;
        }

        var allEventTimes = currentAttempt.events
            .filter(function(e) {
                return e.type === 'tabswitch' || e.type === 'windowblur' || e.type === 'contextmenu';
            })
            .map(function(e) {return e.time;});

        var firstHighlightIdx = -1;
        var highlightedCount  = 0;
        var html = '';

        shots.forEach(function(shot, idx) {
            var d = new Date(shot.time * 1000);
            var timeStr = d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            var srcLabel = evidenceSourceLabel(shot.source, false);

            var isHighlighted = !!(highlightTimes && highlightTimes.some(function(t) {
                return Math.abs(shot.time - t) <= 90;
            }));
            var isSuspicious = allEventTimes.some(function(t) {
                return Math.abs(shot.time - t) <= 5;
            });

            if (isHighlighted) {
                highlightedCount++;
                if (firstHighlightIdx === -1) {
                    firstHighlightIdx = idx;
                }
            }

            var cls = 'tagrep-carousel-thumb' + (isHighlighted ? ' tagrep-carousel-thumb--highlight' : '');

            html += '<div class="' + cls + '" id="tagrep-thumb-' + idx + '"' +
                ' onclick="tagRepOpenLightbox(' + idx + ')" title="Click to enlarge">' +
                (isSuspicious ? '<span class="tagrep-suspicious-dot"></span>' : '') +
                '<img src="' + esc(shot.url) + '" alt="Screenshot" loading="lazy">' +
                '<div class="tagrep-carousel-caption">' +
                '<span class="tagrep-carousel-time">' + esc(timeStr) + '</span>' +
                '<span class="tagrep-carousel-source">' + esc(srcLabel) + '</span>' +
                '<span class="tagrep-carousel-name">' + esc(studentName) + '</span>' +
                '</div>' +
                '</div>';
        });

        el('tagrep-carousel').innerHTML = html;
        el('tagrep-carousel').style.display = '';
        el('tagrep-carousel-empty').style.display = 'none';

        if (typeof note === 'string' && note) {
            el('tagrep-carousel-count').textContent = note;
        } else if (highlightTimes && highlightedCount > 0) {
            el('tagrep-carousel-count').textContent =
                highlightedCount + ' flagged · ' + shots.length + ' total';
        } else {
            el('tagrep-carousel-count').textContent =
                shots.length + ' screenshot' + (shots.length !== 1 ? 's' : '');
        }

        if (firstHighlightIdx !== -1) {
            var firstEl = el('tagrep-thumb-' + firstHighlightIdx);
            if (firstEl) {
                firstEl.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }
        }
    }

    /**
     * Open the lightbox with the screenshot at the given index.
     *
     * @param {number} idx Index into the current attempt's screenshots array.
     */
    function tagRepOpenLightbox(idx) {
        var shots = currentAttempt ? currentAttempt.screenshots : [];
        var shot = shots[idx];
        if (!shot) {
            return;
        }
        var d = new Date(shot.time * 1000);
        var timeStr = d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
        var srcLabel = evidenceSourceLabel(shot.source, true);

        el('tagrep-lightbox-img').src = shot.url;
        el('tagrep-lightbox-caption').textContent =
            srcLabel + ' · ' + timeStr +
            (currentStudent ? ' · ' + currentStudent.name : '');
        el('tagrep-lightbox').style.display = 'flex';
    }

    /**
     * Close the lightbox.
     */
    function tagRepCloseLightbox() {
        el('tagrep-lightbox').style.display = 'none';
        el('tagrep-lightbox-img').src = '';
    }

    /**
     * Select and activate a violation category, highlighting its screenshots in the carousel.
     *
     * @param {number} idx Index into the currentCats array.
     */
    function tagRepSelectCat(idx) {
        currentCats.forEach(function(_, i) {
            var el2 = el('tagrep-cat-' + i);
            if (el2) {
                el2.className = 'tagrep-cat-item' + (i === idx ? ' tagrep-cat-item--active' : '');
            }
        });

        var cat = currentCats[idx];
        if (!cat || !currentAttempt) {
            return;
        }
        el('tagrep-carousel-title').textContent = cat.icon + '  ' + cat.label;

        var highlightTimes = null;
        var note = '';

        if (cat.source === 'event') {
            highlightTimes = currentAttempt.events
                .filter(function(e) {return e.type === cat.event_type;})
                .map(function(e) {return e.time;});
            if (cat.event_type === 'contextmenu') {
                note = S.report_contextmenu_note || '';
            }
        } else if (cat.source === 'scan') {
            var hasVid = currentAttempt.scan && currentAttempt.scan.video_url;
            note = hasVid
                ? (S.report_scan_evidence_in_video || 'Evidence in room scan video')
                : (S.report_scan_no_video || 'No room scan video available');
        }

        renderCarousel(highlightTimes, note);
    }

    /**
     * Set or clear the "valid override" flag for an attempt via AJAX.
     *
     * @param {number} uid       User ID.
     * @param {number} attemptId Attempt ID.
     * @param {number} override  1 to mark valid, 0 to clear override.
     */
    function tagRepSetOverride(uid, attemptId, override) {
        var msg = override
            ? (S.report_confirm_mark_valid || 'Mark this attempt as Valid? This overrides the automated risk assessment.')
            : (S.report_confirm_clear_override || 'Clear the Valid override? Automated risk assessment will apply again.');
        if (!confirm(msg)) {
            return;
        }

        var fd = new FormData();
        fd.append('attemptid', attemptId);
        fd.append('override', override ? 1 : 0);
        fd.append('sesskey', CFG2.sesskey);

        fetch(CFG2.setOverrideUrl, {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function(r) {return r.json();})
            .then(function(data) {
                if (data.status === 'ok') {
                    DATA.forEach(function(s) {
                        if (s.userid === uid) {
                            s.attempts.forEach(function(a) {
                                if (a.id === attemptId) {
                                    a.override_valid = !!override;
                                }
                            });
                        }
                    });
                    renderStudentList(DATA);
                    if (currentStudent && currentStudent.userid === uid) {
                        renderSessions(currentStudent);
                    }
                } else {
                    alert('Error: ' + (data.message || 'unknown'));
                }
            })
            .catch(function() {alert('Network error saving override.');});
    }

    /**
     * Delete all screenshots for an attempt via AJAX and update local state.
     *
     * @param {number} uid       User ID.
     * @param {number} attemptId Attempt ID.
     */
    function tagRepDeleteScreenshots(uid, attemptId) {
        if (!confirm(S.report_confirm_delete_evidence ||
                'Permanently delete all screenshot evidence for this attempt?')) {
            return;
        }

        var fd = new FormData();
        fd.append('attemptid', attemptId);
        fd.append('sesskey', CFG2.sesskey);

        fetch(CFG2.deleteShotsUrl, {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function(r) {return r.json();})
            .then(function(data) {
                if (data.status === 'ok') {
                    DATA.forEach(function(s) {
                        if (s.userid === uid) {
                            s.attempts.forEach(function(a) {
                                if (a.id === attemptId) {
                                    a.screenshots = [];
                                }
                            });
                        }
                    });
                    if (currentStudent && currentStudent.userid === uid) {
                        renderSessions(currentStudent);
                    }
                } else {
                    alert('Error: ' + (data.message || 'unknown'));
                }
            })
            .catch(function() {alert('Network error deleting screenshots.');});
    }

    /**
     * Delete all logged events for an attempt via AJAX and reset its risk state.
     *
     * @param {number} uid       User ID.
     * @param {number} attemptId Attempt ID.
     */
    function tagRepDeleteEvents(uid, attemptId) {
        if (!confirm(S.report_confirm_delete_events ||
                'Permanently delete all logged events for this attempt? This will also clear the risk flags.')) {
            return;
        }

        var fd = new FormData();
        fd.append('attemptid', attemptId);
        fd.append('sesskey', CFG2.sesskey);

        fetch(CFG2.deleteEventsUrl, {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function(r) {return r.json();})
            .then(function(data) {
                if (data.status === 'ok') {
                    DATA.forEach(function(s) {
                        if (s.userid === uid) {
                            s.attempts.forEach(function(a) {
                                if (a.id === attemptId) {
                                    a.events = [];
                                    a.risk_score = 0;
                                    a.risk_level = 'valid';
                                }
                            });
                        }
                    });
                    renderStudentList(DATA);
                    if (currentStudent && currentStudent.userid === uid) {
                        renderSessions(currentStudent);
                    }
                } else {
                    alert('Error: ' + (data.message || 'unknown'));
                }
            })
            .catch(function() {alert('Network error deleting events.');});
    }

    /**
     * Toggle the "reviewed" flag for an attempt.
     *
     * @param {number} uid       User ID.
     * @param {number} attemptId Attempt ID.
     * @param {number} reviewed  1 to mark reviewed, 0 to clear.
     */
    function tagRepSetReviewed(uid, attemptId, reviewed) {
        var fd = new FormData();
        fd.append('attemptid', attemptId);
        fd.append('reviewed', reviewed ? 1 : 0);
        fd.append('sesskey', CFG2.sesskey);

        fetch(CFG2.setReviewedUrl, {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function(r) {return r.json();})
            .then(function(data) {
                if (data.status === 'ok') {
                    DATA.forEach(function(s) {
                        if (s.userid === uid) {
                            s.attempts.forEach(function(a) {
                                if (a.id === attemptId) {
                                    a.reviewed = !!reviewed;
                                }
                            });
                        }
                    });
                    if (currentStudent && currentStudent.userid === uid) {
                        renderSessions(currentStudent);
                    }
                } else {
                    alert('Error: ' + (data.message || 'unknown'));
                }
            })
            .catch(function() {alert('Network error saving reviewed status.');});
    }

    return {
        /**
         * Initialise the report UI with server-provided student data and action URLs.
         *
         * @param {Array}  data Student/attempt data array.
         * @param {Object} cfg  Action URLs and sesskey: setOverrideUrl, deleteShotsUrl,
         *                      deleteEventsUrl, deleteScanBase, setReviewedUrl,
         *                      grantRescanUrl, sesskey, quizid.
         */
        init: function(data, cfg) {
            // Student data may be passed inline or embedded in a <script id="tagrep-data"> tag
            // (the latter avoids Moodle's 1024-char js_call_amd argument limit).
            if (data === null || data === undefined) {
                var elData = document.getElementById('tagrep-data');
                data = elData ? JSON.parse(elData.textContent) : [];
            }
            DATA = data;
            CFG2 = cfg;

            // Expose functions needed by inline onclick handlers in the HTML.
            window.tagRepNav              = tagRepNav;
            window.tagRepSearch           = tagRepSearch;
            window.tagRepOpenStudent      = tagRepOpenStudent;
            window.tagRepOpenFlags        = tagRepOpenFlags;
            window.tagRepSelectCat        = tagRepSelectCat;
            window.tagRepOpenLightbox     = tagRepOpenLightbox;
            window.tagRepCloseLightbox    = tagRepCloseLightbox;
            window.tagRepSetOverride      = tagRepSetOverride;
            window.tagRepDeleteScreenshots= tagRepDeleteScreenshots;
            window.tagRepDeleteEvents     = tagRepDeleteEvents;
            window.tagRepSetReviewed      = tagRepSetReviewed;

            var comp = 'quizaccess_tagproctoring';
            Str.get_strings([
                // VIOLATIONS labels (indices 0–15)
                {key: 'violation_tabswitch',         component: comp},
                {key: 'violation_windowblur',        component: comp},
                {key: 'violation_visibilityhidden',  component: comp},
                {key: 'violation_fullscreenexit',    component: comp},
                {key: 'violation_screenshare',       component: comp},
                {key: 'violation_screensharestop',   component: comp},
                {key: 'violation_multiplescreen',    component: comp},
                {key: 'violation_copypaste',         component: comp},
                {key: 'violation_contextmenu',       component: comp},
                {key: 'violation_devtools',          component: comp},
                {key: 'violation_capabilitywarning', component: comp},
                {key: 'violation_nocamera',          component: comp},
                {key: 'violation_noface',            component: comp},
                {key: 'violation_faceaway',          component: comp},
                {key: 'violation_faceturn',          component: comp},
                {key: 'violation_multiplefaces',     component: comp},
                // SCAN_VIOLATIONS labels (indices 16–23)
                {key: 'scan_violation_cellphone',    component: comp},
                {key: 'scan_violation_book',         component: comp},
                {key: 'scan_violation_tablet',       component: comp},
                {key: 'scan_violation_remote',       component: comp},
                {key: 'scan_violation_extraperson',  component: comp},
                {key: 'scan_violation_faceturned',   component: comp},
                {key: 'scan_violation_noface',       component: comp},
                {key: 'scan_violation_multiplefaces',component: comp},
                // Risk labels (indices 24–26)
                {key: 'risklabel_invalid',           component: comp},
                {key: 'risklabel_review',            component: comp},
                {key: 'risklabel_valid',             component: comp},
                // Evidence source labels (indices 27–36)
                {key: 'evidence_screen',             component: comp},
                {key: 'evidence_screen_tabswitch',   component: comp},
                {key: 'evidence_screen_windowblur',  component: comp},
                {key: 'evidence_screen_contextmenu', component: comp},
                {key: 'evidence_webcam',             component: comp},
                {key: 'evidence_webcam_faceaway',    component: comp},
                {key: 'evidence_webcam_faceturn',    component: comp},
                {key: 'evidence_screencapture',      component: comp},
                {key: 'evidence_webcamcapture',      component: comp},
                {key: 'evidence_default',            component: comp},
                // Session summary (indices 37–53)
                {key: 'summary_session',             component: comp},
                {key: 'summary_noviolations',        component: comp},
                {key: 'summary_violations_prefix',   component: comp},
                {key: 'summary_label_tabswitch',     component: comp},
                {key: 'summary_label_visibilityhidden', component: comp},
                {key: 'summary_label_windowblur',    component: comp},
                {key: 'summary_label_fullscreenexit',component: comp},
                {key: 'summary_label_screenshare',   component: comp},
                {key: 'summary_label_screensharestop', component: comp},
                {key: 'summary_label_multiplescreen',component: comp},
                {key: 'summary_label_copypaste',     component: comp},
                {key: 'summary_label_devtools',      component: comp},
                {key: 'summary_label_faceaway',      component: comp},
                {key: 'summary_label_noface',        component: comp},
                {key: 'summary_label_multiplefaces', component: comp},
                {key: 'summary_label_contextmenu',   component: comp},
                {key: 'summary_label_capabilitywarning', component: comp},
                // Report info rows (indices 54–58)
                {key: 'report_inforow_start',        component: comp},
                {key: 'report_inforow_end',          component: comp},
                {key: 'report_inforow_duration',     component: comp},
                {key: 'report_inforow_events',       component: comp},
                {key: 'report_inforow_evidence',     component: comp},
                // Labels (indices 59–65)
                {key: 'report_action_view_sessions', component: comp},
                {key: 'report_label_selfie',         component: comp},
                {key: 'report_label_idphoto',        component: comp},
                {key: 'report_label_attempt_num',    component: comp},
                {key: 'report_facematch_verified',   component: comp},
                {key: 'report_facematch_failed',     component: comp},
                {key: 'report_facematch_unverified', component: comp},
                // Action buttons (indices 66–74)
                {key: 'report_action_view_flags',    component: comp},
                {key: 'report_action_mark_valid',    component: comp},
                {key: 'report_action_clear_override',component: comp},
                {key: 'report_action_mark_reviewed', component: comp},
                {key: 'report_action_reviewed_undo', component: comp},
                {key: 'report_action_quiz_results',  component: comp},
                {key: 'report_action_delete_events', component: comp},
                {key: 'report_action_delete_evidence', component: comp},
                {key: 'report_action_delete_scan',   component: comp},
                // Flags panel (indices 75–78)
                {key: 'report_flags_no_violations',  component: comp},
                {key: 'report_flags_source_scan',    component: comp},
                {key: 'report_flags_source_event',   component: comp},
                {key: 'report_potential_violations', component: comp},
                // Carousel (indices 79–80)
                {key: 'report_carousel_evidence_title', component: comp},
                {key: 'report_carousel_empty_attempt',  component: comp},
                // Scan/contextmenu notes (indices 81–83)
                {key: 'report_scan_evidence_in_video', component: comp},
                {key: 'report_scan_no_video',        component: comp},
                {key: 'report_contextmenu_note',     component: comp},
                // Confirm dialogs (indices 84–87)
                {key: 'report_confirm_mark_valid',   component: comp},
                {key: 'report_confirm_clear_override', component: comp},
                {key: 'report_confirm_delete_evidence', component: comp},
                {key: 'report_confirm_delete_events', component: comp},
            ]).then(function(strs) {
                var i = 0;
                // VIOLATIONS labels.
                VIOLATIONS.tabswitch.label         = strs[i++];
                VIOLATIONS.windowblur.label        = strs[i++];
                VIOLATIONS.visibilityhidden.label  = strs[i++];
                VIOLATIONS.fullscreenexit.label    = strs[i++];
                VIOLATIONS.screenshare.label       = strs[i++];
                VIOLATIONS.screensharestop.label   = strs[i++];
                VIOLATIONS.multiplescreen.label    = strs[i++];
                VIOLATIONS.copypaste.label         = strs[i++];
                VIOLATIONS.contextmenu.label       = strs[i++];
                VIOLATIONS.devtools.label          = strs[i++];
                VIOLATIONS.capabilitywarning.label = strs[i++];
                VIOLATIONS.nocamera.label          = strs[i++];
                VIOLATIONS.noface.label            = strs[i++];
                VIOLATIONS.faceaway.label          = strs[i++];
                VIOLATIONS.faceturn.label          = strs[i++];
                VIOLATIONS.multiplefaces.label     = strs[i++];
                // SCAN_VIOLATIONS labels.
                SCAN_VIOLATIONS['cell phone'].label    = strs[i++];
                SCAN_VIOLATIONS['book'].label          = strs[i++];
                SCAN_VIOLATIONS['tablet'].label        = strs[i++];
                SCAN_VIOLATIONS['remote'].label        = strs[i++];
                SCAN_VIOLATIONS['extra_person'].label  = strs[i++];
                SCAN_VIOLATIONS['face_turned'].label   = strs[i++];
                SCAN_VIOLATIONS['no_face'].label       = strs[i++];
                SCAN_VIOLATIONS['multiple_faces'].label= strs[i++];
                // Risk labels.
                RISK_LABEL.invalid                 = strs[i++];
                RISK_LABEL.review                  = strs[i++];
                RISK_LABEL.valid                   = strs[i++];
                // Evidence source labels.
                S.evidence_screen                  = strs[i++];
                S.evidence_screen_tabswitch        = strs[i++];
                S.evidence_screen_windowblur       = strs[i++];
                S.evidence_screen_contextmenu      = strs[i++];
                S.evidence_webcam                  = strs[i++];
                S.evidence_webcam_faceaway         = strs[i++];
                S.evidence_webcam_faceturn         = strs[i++];
                S.evidence_screencapture           = strs[i++];
                S.evidence_webcamcapture           = strs[i++];
                S.evidence_default                 = strs[i++];
                // Session summary.
                S.summary_session                  = strs[i++];
                S.summary_noviolations             = strs[i++];
                S.summary_violations_prefix        = strs[i++];
                S.summary_label_tabswitch          = strs[i++];
                S.summary_label_visibilityhidden   = strs[i++];
                S.summary_label_windowblur         = strs[i++];
                S.summary_label_fullscreenexit     = strs[i++];
                S.summary_label_screenshare        = strs[i++];
                S.summary_label_screensharestop    = strs[i++];
                S.summary_label_multiplescreen     = strs[i++];
                S.summary_label_copypaste          = strs[i++];
                S.summary_label_devtools           = strs[i++];
                S.summary_label_faceaway           = strs[i++];
                S.summary_label_noface             = strs[i++];
                S.summary_label_multiplefaces      = strs[i++];
                S.summary_label_contextmenu        = strs[i++];
                S.summary_label_capabilitywarning  = strs[i++];
                // Report info rows.
                S.report_inforow_start             = strs[i++];
                S.report_inforow_end               = strs[i++];
                S.report_inforow_duration          = strs[i++];
                S.report_inforow_events            = strs[i++];
                S.report_inforow_evidence          = strs[i++];
                // Labels.
                S.report_action_view_sessions      = strs[i++];
                S.report_label_selfie              = strs[i++];
                S.report_label_idphoto             = strs[i++];
                S.report_label_attempt_num         = strs[i++];
                S.report_facematch_verified        = strs[i++];
                S.report_facematch_failed          = strs[i++];
                S.report_facematch_unverified      = strs[i++];
                // Action buttons.
                S.report_action_view_flags         = strs[i++];
                S.report_action_mark_valid         = strs[i++];
                S.report_action_clear_override     = strs[i++];
                S.report_action_mark_reviewed      = strs[i++];
                S.report_action_reviewed_undo      = strs[i++];
                S.report_action_quiz_results       = strs[i++];
                S.report_action_delete_events      = strs[i++];
                S.report_action_delete_evidence    = strs[i++];
                S.report_action_delete_scan        = strs[i++];
                // Flags panel.
                S.report_flags_no_violations       = strs[i++];
                S.report_flags_source_scan         = strs[i++];
                S.report_flags_source_event        = strs[i++];
                S.report_potential_violations      = strs[i++];
                // Carousel.
                S.report_carousel_evidence_title   = strs[i++];
                S.report_carousel_empty_attempt    = strs[i++];
                // Scan/contextmenu notes.
                S.report_scan_evidence_in_video    = strs[i++];
                S.report_scan_no_video             = strs[i++];
                S.report_contextmenu_note          = strs[i++];
                // Confirm dialogs.
                S.report_confirm_mark_valid        = strs[i++];
                S.report_confirm_clear_override    = strs[i++];
                S.report_confirm_delete_evidence   = strs[i++];
                S.report_confirm_delete_events     = strs[i++];

                renderStudentList(DATA);
            });
        }
    };
});
