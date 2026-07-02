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

define("quizaccess_tagproctoring/report", [], function() {
    'use strict';

    // Populated by init().
    var DATA = null;
    var CFG2 = {};

    var VIOLATIONS = {
        tabswitch: {label: 'Exam Page Hidden', icon: '\ud83d\udd04', sev: 'medium'},
        windowblur: {label: 'Exam Window Lost Focus', icon: '\ud83d\udd04', sev: 'medium'},
        visibilityhidden: {label: 'Tab Hidden', icon: '\ud83d\udc41', sev: 'medium'},
        fullscreenexit: {label: 'Fullscreen Exited', icon: '\u26f6', sev: 'medium'},
        screenshare: {label: 'Screen Sharing Initiated', icon: '\ud83d\udcfa', sev: 'critical'},
        screensharestop: {label: 'Screen Share Stopped', icon: '\ud83d\udda5', sev: 'high'},
        multiplescreen: {label: 'Multiple Screens Detected', icon: '\ud83d\udda5', sev: 'critical'},
        copypaste: {label: 'Copy / Paste Activity', icon: '\ud83d\udccb', sev: 'medium'},
        contextmenu: {label: 'Right-Click Attempt', icon: '\ud83d\uddb1', sev: 'low'},
        devtools: {label: 'Developer Tools Open', icon: '\ud83d\udd27', sev: 'high'},
        capabilitywarning: {label: 'Browser Capability Warning', icon: '\u26a0', sev: 'medium'},
        nocamera: {label: 'Camera Unavailable', icon: '\ud83d\udcf7', sev: 'high'},
        noface: {label: 'No Face Detected', icon: '\ud83d\udeab', sev: 'high'},
        faceaway: {label: 'Face Not Visible', icon: '\ud83d\udc64', sev: 'low'},
        faceturn: {label: 'Face Movement', icon: '\ud83d\udc64', sev: 'low'},
        multiplefaces: {label: 'Multiple Faces During Exam', icon: '\ud83d\udc65', sev: 'critical'},
    };

    var SCAN_VIOLATIONS = {
        'cell phone': {label: 'Phone Detected', icon: '\ud83d\udcf1', sev: 'critical'},
        'book': {label: 'Book / Notes Detected', icon: '\ud83d\udcda', sev: 'critical'},
        'tablet': {label: 'Tablet Detected', icon: '\ud83d\udcf1', sev: 'critical'},
        'remote': {label: 'Remote Control Detected', icon: '\ud83c\udfae', sev: 'high'},
        'extra_person': {label: 'Extra Person in Room', icon: '\ud83d\udc65', sev: 'critical'},
        'face_turned': {label: 'Looking Away from Screen', icon: '\ud83d\udc64', sev: 'high'},
        'no_face': {label: 'No Face Detected', icon: '\ud83d\udeab', sev: 'high'},
        'multiple_faces': {label: 'Multiple Faces Detected', icon: '\ud83d\udc65', sev: 'critical'},
    };

    var SEV_COLOR = {critical: '#dc2626', high: '#d97706', medium: '#2563eb', low: '#6b7280'};
    var RISK_COLOR = {invalid: '#dc2626', review: '#d97706', valid: '#16a34a'};
    var RISK_LABEL = {invalid: 'Invalid', review: 'Needs Review', valid: 'Valid'};
    var SEV_ORDER = {critical: 0, high: 1, medium: 2, low: 3};

    // State.
    var currentStudent = null;
    var currentAttempt = null;
    var currentCats = [];

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
     * @param {string} source Stored screenshot source token.
     * @param {boolean} longform Whether to return a lightbox-style label.
     * @returns {string} Human-readable label.
     */
    function evidenceSourceLabel(source, longform) {
        var labels = {
            screen: 'Screen evidence',
            tabswitch: 'Screen evidence: exam page hidden',
            windowblur: 'Screen evidence: exam window lost focus',
            contextmenu: 'Screen evidence: right-click attempt',
            webcam: 'Webcam evidence',
            faceaway: 'Webcam evidence: face not visible',
            faceturn: 'Webcam evidence: face movement'
        };
        if (longform && source === 'screen') {
            return 'Screen capture';
        }
        if (longform && source === 'webcam') {
            return 'Webcam capture';
        }
        return labels[source] || (source ? source.replace(/_/g, ' ') : 'Evidence');
    }

    /**
     * Format a Unix timestamp as short date and time.
     *
     * @param {number} ts Unix timestamp (seconds).
     * @returns {string} Formatted date-time string, or em-dash for falsy input.
     */
    function fmtDateTime(ts) {
        if (!ts) {
            return '\u2014';
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
            return '\u2014';
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
            tabswitch: 'exam page hidden', visibilityhidden: 'tab hidden',
            windowblur: 'exam window focus loss',
            fullscreenexit: 'fullscreen exit', screenshare: 'screen share event',
            screensharestop: 'screen share stop', multiplescreen: 'multiple screen',
            copypaste: 'copy/paste', devtools: 'devtools open',
            faceaway: 'face not visible',
            noface: 'no face detected', multiplefaces: 'multiple faces',
            contextmenu: 'right-click attempt',
            capabilitywarning: 'browser capability warning'
        };

        // High-risk first, then medium/low.
        var order = ['screenshare','multiplescreen','screensharestop','multiplefaces',
                     'fullscreenexit','tabswitch','visibilityhidden',
                     'faceaway','noface','capabilitywarning','copypaste','devtools','contextmenu'];
        order.forEach(function(t) {
            if (counts[t]) {
                var n = counts[t];
                var lbl = LABELS[t] || t;
                parts.push(n + ' ' + lbl + (n > 1 && lbl.slice(-1) !== 's' ? 's' : ''));
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
        var riskTxt   = riskIcon + ' ' + RISK_LABEL[dispRisk];
        if (attempt.risk_score) { riskTxt += ' (' + attempt.risk_score + '/100)'; }

        var body = parts.length
            ? 'Student completed exam with: ' + parts.join(', ') + '.'
            : 'No violations detected during this exam session.';

        return '<div class="tagrep-ai-summary">' +
            '<div class="tagrep-ai-summary-hd">Session Summary</div>' +
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
        el('tagrep-panel-flags').style.display = panel === 'flags' ? '' : 'none';

        el('tagrep-bc-students').className = 'tagrep-nav-item' +
            (panel === 'students' ? ' tagrep-nav-item--active' : '');

        var showS = panel !== 'students' && currentStudent;
        el('tagrep-bc-sep1').style.display = showS ? '' : 'none';
        el('tagrep-bc-sessions').style.display = showS ? '' : 'none';
        el('tagrep-bc-sessions').className = 'tagrep-nav-item' +
            (panel === 'sessions' ? ' tagrep-nav-item--active' : '');
        if (currentStudent) {
            el('tagrep-bc-sessions').textContent = currentStudent.name;
        }

        var showF = panel === 'flags';
        el('tagrep-bc-sep2').style.display = showF ? '' : 'none';
        el('tagrep-bc-flags').style.display = showF ? '' : 'none';
        if (showF) {
            el('tagrep-bc-flags').textContent =
                'Flags \u2014 Attempt #' + (currentAttempt ? currentAttempt.attempt_num : '');
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
                '<button class="tagrep-btn tagrep-btn--primary" onclick="tagRepOpenStudent(' + s.userid +
                ')">View Sessions \u2192</button>' +
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
            ' &nbsp;\u00b7&nbsp; ' + student.attempts.length + ' attempt(s)</p></div>' +
            '</div>' +
            '<div class="tagrep-sessions-grid">';

        student.attempts.forEach(function(a) {
            var scan = a.scan;

            html += '<div class="tagrep-session-card">';

            var dispRisk = effectiveRisk(a);
            html += '<div class="tagrep-session-hd">' +
                '<span class="tagrep-session-num">Attempt #' + a.attempt_num + '</span>' +
                riskBadge(dispRisk, a.override_valid ? '\u2713 Overridden' : '') +
                '</div>';

            html += buildSummary(a);

            html += '<div class="tagrep-session-body">';

            // Selfie + ID photo side by side with face-match badge.
            html += '<div class="tagrep-session-photo">';
            html += '<div style="display:flex;gap:8px;align-items:flex-start;">';
            if (scan && scan.selfie_url) {
                html += '<div style="text-align:center;">' +
                    '<div style="font-size:10px;color:#6b7280;margin-bottom:2px;">Selfie</div>' +
                    '<img src="' + esc(scan.selfie_url) + '" class="tagrep-selfie-img" alt="Selfie" ' +
                    'onclick="window.open(\'' + esc(scan.selfie_url) + '\',\'_blank\')"' +
                    ' style="cursor:zoom-in;">' +
                    '</div>';
            } else {
                html += '<div class="tagrep-selfie-ph">No<br>Selfie</div>';
            }
            if (scan && scan.idphoto_url) {
                html += '<div style="text-align:center;">' +
                    '<div style="font-size:10px;color:#6b7280;margin-bottom:2px;">ID Photo</div>' +
                    '<img src="' + esc(scan.idphoto_url) + '" class="tagrep-selfie-img" alt="ID Photo" ' +
                    'onclick="window.open(\'' + esc(scan.idphoto_url) + '\',\'_blank\')"' +
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
                    fmBadge = '\u2705 Face Match (' + (fmd !== null ? (fmd * 100).toFixed(0) + '%' : '') + ')';
                    fmColor = '#16a34a';
                } else if (fmv === 0) {
                    fmBadge = '\u274c Face Mismatch (' + (fmd !== null ? (fmd * 100).toFixed(0) + '%' : '') + ')';
                    fmColor = '#dc2626';
                } else {
                    fmBadge = '\u2753 ID Not Verified';
                    fmColor = '#6b7280';
                }
                html += '<div style="margin-top:6px;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700;' +
                    'color:#fff;background:' + fmColor + ';display:inline-block;">' + fmBadge + '</div>';
            }

            html += '</div>';

            html += '<div class="tagrep-session-info">';
            html += infoRow('Start', fmtDateTime(a.timestart));
            html += infoRow('End', fmtDateTime(a.timefinish));
            html += infoRow('Duration', fmtDuration(a.timestart, a.timefinish));
            html += infoRow('Events', a.events.length + ' logged');
            html += infoRow('Evidence shots', a.screenshots.length + ' stored');
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
                html += '<button class="tagrep-btn tagrep-btn--danger" ' +
                    'onclick="tagRepOpenFlags(' + student.userid + ',' + a.id + ')">' +
                    '\u2691 View Flags</button> ';
            }
            if (a.override_valid) {
                html += '<button class="tagrep-btn" style="background:#16a34a;color:#fff;" ' +
                    'onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',0)">' +
                    '\u2713 Valid (Clear Override)</button> ';
            } else {
                html += '<button class="tagrep-btn tagrep-btn--outline" ' +
                    'onclick="tagRepSetOverride(' + student.userid + ',' + a.id + ',1)">' +
                    '\u2713 Mark as Valid</button> ';
            }
            html += '<a href="' + esc(a.review_url) +
                '" target="_blank" class="tagrep-btn tagrep-btn--outline">Quiz Results \u2197</a>';

            html += '</div>';
            if (a.events.length > 0 || a.screenshots.length > 0 ||
                    (scan && (scan.video_url || scan.selfie_url))) {
                html += '<div class="tagrep-session-deletes">';
                if (a.events.length > 0) {
                    html += '<button class="tagrep-btn tagrep-btn--delete"' +
                        ' onclick="tagRepDeleteEvents(' + student.userid + ',' + a.id + ')">' +
                        '\ud83d\uddd1 Delete Events (' + a.events.length + ')</button>';
                }
                if (a.screenshots.length > 0) {
                    html += '<button class="tagrep-btn tagrep-btn--delete"' +
                        ' onclick="tagRepDeleteScreenshots(' + student.userid + ',' + a.id + ')">' +
                    '\ud83d\uddd1 Delete Evidence (' + a.screenshots.length + ')</button>';
                }
                if (scan && (scan.video_url || scan.selfie_url)) {
                    var durl = CFG2.deleteScanBase + '&userid=' + student.userid;
                    html += '<a href="' + esc(durl) + '" class="tagrep-btn tagrep-btn--delete">' +
                        '\ud83d\uddd1 Delete Scan Data</a>';
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
            currentStudent.name + ' \u2014 Potential Violation of Rules';

        // Show session-start browser/OS info in the subtitle if available.
        var sessionStartEvent = currentAttempt.events.find(function(e) {
            return e.type === 'sessionstart';
        });
        var subText = fmtDateTime(currentAttempt.timestart) +
            '  \u2192  ' + fmtDateTime(currentAttempt.timefinish);
        if (sessionStartEvent && sessionStartEvent.detail) {
            subText += '  \u00b7  \ud83d\udcbb ' + sessionStartEvent.detail;
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
            var def = VIOLATIONS[type] || {label: type, icon: '\u26a0', sev: 'medium'};
            currentCats.push({
                id: 'ev_' + type,
                label: def.label,
                icon: def.icon,
                sev: def.sev,
                count: eCounts[type],
                source: 'event',
                event_type: type
            });
        });

        var scan = currentAttempt.scan;
        if (scan && scan.objects) {
            Object.keys(scan.objects).forEach(function(cls) {
                var def = SCAN_VIOLATIONS[cls] || {label: cls, icon: '\u26a0', sev: 'medium'};
                currentCats.push({
                    id: 'sc_' + cls.replace(/\s/g, '_'),
                    label: def.label,
                    icon: def.icon,
                    sev: def.sev,
                    count: scan.objects[cls],
                    source: 'scan',
                    scan_class: cls
                });
            });
        }

        currentCats.sort(function(a, b) {
            return (SEV_ORDER[a.sev] || 9) - (SEV_ORDER[b.sev] || 9);
        });

        var listHtml = '';
        if (!currentCats.length) {
            listHtml = '<p class="tagrep-no-flags">No violations detected.</p>';
        } else {
            currentCats.forEach(function(cat, i) {
                listHtml += '<div class="tagrep-cat-item" id="tagrep-cat-' + i +
                    '" onclick="tagRepSelectCat(' + i + ')">' +
                    '<span class="tagrep-cat-icon">' + cat.icon + '</span>' +
                    '<div class="tagrep-cat-text">' +
                    '<span class="tagrep-cat-label">' + esc(cat.label) + '</span>' +
                    '<span class="tagrep-cat-src">' +
                    '&middot; ' + (cat.source === 'scan' ? 'Room Scan' : 'During Exam') +
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
        el('tagrep-carousel-title').textContent = 'Screen and Webcam Evidence';
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
                note || 'No screenshot evidence captured for this attempt.';
            el('tagrep-carousel-count').textContent = typeof note === 'string' ? note : '';
            return;
        }

        var allEventTimes = currentAttempt.events
            .filter(function(e) {
                return e.type === 'tabswitch' || e.type === 'windowblur' || e.type === 'contextmenu';
            })
            .map(function(e) {return e.time;});

        var firstHighlightIdx = -1;
        var highlightedCount = 0;
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
                note = 'Right-click screenshots are captured for new attempts after this update.';
            }
        } else if (cat.source === 'scan') {
            var hasVid = currentAttempt.scan && currentAttempt.scan.video_url;
            note = hasVid ? 'Evidence in room scan video ↑' : 'No room scan video available';
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
        var msg = override ?
            'Mark this attempt as Valid? This overrides the automated risk assessment.' :
            'Clear the Valid override? Automated risk assessment will apply again.';
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
        if (!confirm('Permanently delete all screenshot evidence for this attempt?')) {
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
        if (!confirm(
            'Permanently delete all logged events for this attempt? This will also clear the risk flags.'
        )) {
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

    return {
        /**
         * Initialise the report UI with server-provided student data and action URLs.
         *
         * @param {Array}  data Student/attempt data array.
         * @param {Object} cfg  Action URLs and sesskey: setOverrideUrl, deleteShotsUrl,
         *                      deleteEventsUrl, deleteScanBase, sesskey.
         */
        init: function(data, cfg) {
            // Student data may be passed inline or embedded in a <script id="tagrep-data"> tag
            // (the latter avoids Moodle's 1024-char js_call_amd argument limit).
            if (data === null || data === undefined) {
                var el = document.getElementById('tagrep-data');
                data = el ? JSON.parse(el.textContent) : [];
            }
            DATA = data;
            CFG2 = cfg;

            // Expose functions needed by inline onclick handlers in the HTML.
            window.tagRepNav = tagRepNav;
            window.tagRepSearch = tagRepSearch;
            window.tagRepOpenStudent = tagRepOpenStudent;
            window.tagRepOpenFlags = tagRepOpenFlags;
            window.tagRepSelectCat = tagRepSelectCat;
            window.tagRepOpenLightbox = tagRepOpenLightbox;
            window.tagRepCloseLightbox = tagRepCloseLightbox;
            window.tagRepSetOverride = tagRepSetOverride;
            window.tagRepDeleteScreenshots = tagRepDeleteScreenshots;
            window.tagRepDeleteEvents = tagRepDeleteEvents;

            renderStudentList(DATA);
        }
    };
});
