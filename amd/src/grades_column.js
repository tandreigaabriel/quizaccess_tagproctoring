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
 * Injects a proctoring status column into the Moodle quiz overview (grades) report table.
 *
 * Called from quizaccess_tagproctoring\hook_callbacks when the page is /mod/quiz/report.php
 * and proctoring is enabled for that quiz. Fetches risk data for every attempt via AJAX
 * and appends a colour-coded badge cell to each row.
 *
 * @module quizaccess_tagproctoring/grades_column
 * @copyright 2026 Andrei Toma <https://www.tandreig.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['quizaccess_tagproctoring/strings', 'core/templates', 'core/notification'], function(I18n, Templates, Notification) {
    'use strict';

    var cfg = {};

    /**
     * Build and inject the proctoring column.
     */
    function injectColumn() {
        // Locate attempt review links — every data row in the overview table has one.
        var links = document.querySelectorAll('a[href*="/mod/quiz/review.php"]');
        if (!links.length) {
            return;
        }

        var table = links[0].closest('table');
        if (!table) {
            return;
        }

        // --- Header row ---
        var headerRow = table.querySelector('thead tr');
        if (headerRow) {
            var th = document.createElement('th');
            th.scope = 'col';
            th.className = 'tagproctoring-col-header c' + (headerRow.cells.length);
            th.style.whiteSpace = 'nowrap';
            th.textContent = (cfg.strings && cfg.strings.header) ? cfg.strings.header : I18n.get('ui_proctoring');
            headerRow.appendChild(th);
        }

        // --- Data rows: map attemptid → <tr> ---
        var rowmap = {};
        links.forEach(function(link) {
            var m = link.href.match(/[?&]attempt=(\d+)/);
            if (!m) {
                return;
            }
            var attemptid = m[1];
            var row = link.closest('tr');
            if (!row || rowmap[attemptid]) {
                return;
            }
            rowmap[attemptid] = row;

            var td = document.createElement('td');
            td.className = 'tagproctoring-status-cell';
            td.style.verticalAlign = 'middle';
            td.textContent = '…';
            row.appendChild(td);
        });

        // Pad any tbody rows that did not get a cell (e.g. "Overall average").
        var tbodyRows = table.querySelectorAll('tbody tr');
        tbodyRows.forEach(function(row) {
            if (!row.querySelector('.tagproctoring-status-cell')) {
                var td = document.createElement('td');
                td.className = 'tagproctoring-status-cell tagproctoring-status-empty';
                row.appendChild(td);
            }
        });

        if (!Object.keys(rowmap).length) {
            return;
        }

        // --- Fetch risk data from PHP endpoint ---
        fetch(cfg.ajaxurl + '?cmid=' + encodeURIComponent(cfg.cmid)
                          + '&sesskey=' + encodeURIComponent(cfg.sesskey))
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                var colors  = {high: '#dc2626', medium: '#d97706', low: '#16a34a'};
                var icons   = {high: '⚠', medium: '⚠', low: '✓'};
                var strings = cfg.strings || {};
                var labels  = {
                    high:   strings.high   || I18n.get('ui_high_risk'),
                    medium: strings.medium || I18n.get('ui_medium'),
                    low:    strings.low    || I18n.get('ui_clean'),
                };

                Object.keys(rowmap).forEach(function(attemptid) {
                    var cell = rowmap[attemptid].querySelector('.tagproctoring-status-cell');
                    if (!cell) {
                        return;
                    }
                    var info = data[attemptid];
                    if (!info) {
                        cell.textContent = '—';
                        return;
                    }
                    var risk  = info.risk || 'low';
                    var color = colors[risk] || '#6b7280';
                    var icon  = icons[risk]  || '';
                    var label = labels[risk] || risk;

                    Templates.renderForPromise('quizaccess_tagproctoring/grades_badge', {
                        url: cfg.reporturl, color: color, icon: icon, label: label
                    }).then(function(result) {
                        Templates.replaceNodeContents(cell, result.html, result.js);
                    }).catch(Notification.exception);
                });
            })
            .catch(function() {
                // Silently fail — proctoring column is informational only.
                Object.keys(rowmap).forEach(function(attemptid) {
                    var cell = rowmap[attemptid].querySelector('.tagproctoring-status-cell');
                    if (cell) {
                        cell.textContent = '—';
                    }
                });
            });
    }

    return {
        /**
         * @param {Object} config
         * @param {number} config.cmid
         * @param {string} config.ajaxurl
         * @param {string} config.sesskey
         * @param {string} config.reporturl
         * @param {Object} config.strings  Localised labels.
         */
        init: function(config) {
            return I18n.load().then(function() {
            cfg = config;
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', injectColumn);
            } else {
                injectColumn();
            }
            });
        }
    };
});
