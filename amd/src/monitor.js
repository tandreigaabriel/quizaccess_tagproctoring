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
 * Proctoring monitor module — handles webcam screenshots and suspicious event tracking.
 *
 * @module     quizaccess_tagproctoring/monitor
 * @copyright  2024 TAG
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("quizaccess_tagproctoring/monitor", ["core/ajax"], function(Ajax) {

    var screenshotInterval = null;
    var webcamStream = null;
    var liveVideo = null;          // persistent hidden video element for frame capture
    var cfg = {};

    // Face-turn detection.
    var faceDetectModel = null;
    var lastFaceTurnShot = 0;     // Date.now() of last face-turn screenshot (rate-limit)

    /**
     * Log a proctoring event via the Moodle external API.
     *
     * @param {string} eventtype Short event-type token.
     * @param {string} detail    Optional detail string.
     */
    function logEvent(eventtype, detail) {
        Ajax.call([{
            methodname: "quizaccess_tagproctoring_log_event",
            args: {
                cmid:      cfg.cmid,
                attemptid: cfg.attemptid,
                eventtype: eventtype,
                detail:    detail || ""
            }
        }]);
    }

    /**
     * Dynamically load an external script while hiding RequireJS define
     * to prevent "Mismatched anonymous define()" errors from TF.js / BlazeFace.
     *
     * @param {string}   src      Script URL to load.
     * @param {Function} callback Called with no args on success, ("error") on failure.
     */
    function loadScript(src, callback) {
        if (document.querySelector('script[src="' + src + '"]')) {
            callback();
            return;
        }
        var savedDefine = window.define;
        window.define = undefined;
        var s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload  = function() { window.define = savedDefine; callback(); };
        s.onerror = function() { window.define = savedDefine; callback('error'); };
        document.head.appendChild(s);
    }

    /**
     * Create a persistent 1-pixel hidden video element bound to the webcam stream.
     * Used for both screenshot capture and face-direction analysis.
     */
    function createLiveVideo() {
        if (liveVideo) {
            return;
        }
        var v = document.createElement('video');
        v.muted = true;
        v.playsInline = true;
        v.setAttribute('playsinline', '');
        v.style.cssText = 'position:fixed;width:1px;height:1px;top:-200px;left:-200px;' +
                          'opacity:0;pointer-events:none';
        v.srcObject = webcamStream;
        document.body.appendChild(v);
        v.play().catch(function() {});
        liveVideo = v;
    }

    /**
     * Capture the current webcam frame and upload it as a screenshot.
     *
     * @param {string} [trigger] Source label sent to the server:
     *   'webcam' (periodic), 'tabswitch', 'windowblur', or 'faceturn'.
     */
    function captureScreenshot(trigger) {
        if (!webcamStream || !liveVideo) {
            return;
        }
        var canvas = document.createElement('canvas');
        canvas.width  = 320;
        canvas.height = 240;
        canvas.getContext('2d').drawImage(liveVideo, 0, 0, 320, 240);
        canvas.toBlob(function(blob) {
            if (!blob) {
                return;
            }
            var fd = new FormData();
            fd.append('screenshot', blob, 'shot.jpg');
            fd.append('cmid',      cfg.cmid);
            fd.append('attemptid', cfg.attemptid);
            fd.append('sesskey',   M.cfg.sesskey);
            fd.append('source',    trigger || 'webcam');
            fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tagproctoring/screenshot_upload.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).catch(function() {});
        }, 'image/jpeg', 0.7);
    }

    /**
     * Load TF.js + BlazeFace in the background and start periodic face-direction checks.
     * If the student's face turns significantly, log an event and capture a screenshot.
     */
    function loadFaceDetection() {
        var TFJS = 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js';
        var FACE = 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';

        loadScript(TFJS, function(err) {
            if (err || !window.tf) {
                return;
            }
            loadScript(FACE, function(e2) {
                if (e2 || !window.blazeface) {
                    return;
                }
                window.blazeface.load().then(function(model) {
                    faceDetectModel = model;
                    startFaceMonitor();
                }).catch(function() {});
            });
        });
    }

    /**
     * Poll the webcam every 3 s for face direction.
     * Captures a screenshot (max once per 30 s) when the nose-to-eye ratio
     * indicates the student is looking significantly to the side.
     */
    function startFaceMonitor() {
        setInterval(function() {
            if (!faceDetectModel || !liveVideo || liveVideo.readyState < 2) {
                return;
            }
            faceDetectModel.estimateFaces(liveVideo, false).then(function(faces) {
                if (faces.length !== 1) {
                    return;
                }
                var lm = faces[0].landmarks;
                if (!lm || lm.length < 3) {
                    return;
                }
                var rEyeX   = lm[0][0];
                var lEyeX   = lm[1][0];
                var noseX   = lm[2][0];
                var eyeMid  = (rEyeX + lEyeX) / 2;
                var eyeDist = Math.abs(lEyeX - rEyeX);
                var ratio   = eyeDist > 0 ? Math.abs(noseX - eyeMid) / eyeDist : 0;
                // ratio > 0.30 ≈ ~17° head turn — enough to flag a deliberate look-away.
                if (ratio > 0.30) {
                    var now = Date.now();
                    if (now - lastFaceTurnShot > 30000) { // max 1 screenshot per 30 s
                        lastFaceTurnShot = now;
                        logEvent('faceturn', 'Face turned away, ratio=' + ratio.toFixed(2));
                        captureScreenshot('faceturn');
                    }
                }
            }).catch(function() {});
        }, 3000);
    }

    /**
     * Request webcam access, create the live video element,
     * schedule periodic screenshots, and load face detection.
     */
    function startWebcam() {
        navigator.mediaDevices.getUserMedia({video: {width: 320, height: 240}, audio: false})
            .then(function(stream) {
                webcamStream = stream;
                createLiveVideo();
                // First screenshot after 5 s, then every cfg.interval seconds.
                setTimeout(function() { captureScreenshot('webcam'); }, 5000);
                screenshotInterval = setInterval(
                    function() { captureScreenshot('webcam'); },
                    (cfg.interval || 30) * 1000
                );
                // Load face detection in the background — non-blocking.
                loadFaceDetection();
            })
            .catch(function() {
                logEvent('nocamera', 'Webcam unavailable during attempt');
            });
    }

    /**
     * Block the attempt and log when the student uses multiple screens.
     */
    function detectMultipleScreens() {
        if (!(window.screen && window.screen.isExtended === true)) {
            return;
        }
        logEvent("multiplescreen", "window.screen.isExtended = true");

        var overlay = document.createElement('div');
        overlay.id = 'tagproctor-ms-overlay';
        overlay.style.cssText = [
            'position:fixed;inset:0;z-index:1000000',
            'background:rgba(0,0,0,.95)',
            'display:flex',
            'flex-direction:column',
            'align-items:center',
            'justify-content:center',
            'color:#fff',
            'font-family:sans-serif',
            'text-align:center',
            'padding:32px'
        ].join(';');
        overlay.innerHTML = [
            '<div style="font-size:48px;margin-bottom:16px">\u26a0\ufe0f</div>',
            '<h2 style="margin:0 0 10px;font-size:24px">Multiple Monitors Detected</h2>',
            '<p style="margin:0 0 8px;font-size:15px;opacity:.85">',
            'A second screen has been detected. You must disconnect it before continuing.',
            '</p>',
            '<p style="font-size:13px;opacity:.6">',
            'Once disconnected, reload this page to continue your exam.',
            '</p>'
        ].join('');
        document.body.appendChild(overlay);
    }

    /**
     * Patch getDisplayMedia to detect if the student initiates screen sharing.
     */
    function detectScreenShare() {
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            var original = navigator.mediaDevices.getDisplayMedia.bind(navigator.mediaDevices);
            navigator.mediaDevices.getDisplayMedia = function(constraints) {
                logEvent("screenshare", "Student initiated screen share");
                return original(constraints);
            };
        }
    }

    /**
     * Log tab-switch and window-blur events, and capture a screenshot each time.
     */
    function detectTabSwitch() {
        var switchCount = 0;
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                switchCount++;
                logEvent("visibilityhidden", "Tab hidden, count=" + switchCount);
                captureScreenshot('tabswitch');
            }
        });
        window.addEventListener("blur", function() {
            logEvent("tabswitch", "Window lost focus");
            captureScreenshot('windowblur');
        });
    }

    /**
     * Log copy, paste, and cut events.
     */
    function detectCopyPaste() {
        document.addEventListener("copy", function() {
            logEvent("copypaste", "Copy event");
        });
        document.addEventListener("paste", function() {
            logEvent("copypaste", "Paste event");
        });
        document.addEventListener("cut", function() {
            logEvent("copypaste", "Cut event");
        });
    }

    /**
     * Prevent and log right-click context-menu events.
     */
    function detectContextMenu() {
        document.addEventListener("contextmenu", function(e) {
            e.preventDefault();
            logEvent("contextmenu", "Right-click detected");
        });
    }

    /**
     * Show a blocking overlay whenever the student is not in fullscreen.
     * Displayed immediately on load and again any time they exit.
     */
    function enforceFullscreen() {
        var enteredFullscreen = false; // only log exits after student has entered once
        var overlay = document.createElement('div');
        overlay.id = 'tagproctor-fs-overlay';
        overlay.style.cssText = [
            'position:fixed;inset:0;z-index:999999',
            'background:rgba(0,0,0,.92)',
            'display:flex',
            'flex-direction:column',
            'align-items:center',
            'justify-content:center',
            'color:#fff',
            'font-family:sans-serif',
            'text-align:center',
            'padding:32px'
        ].join(';');
        overlay.innerHTML = [
            '<div style="font-size:48px;margin-bottom:16px">\ud83d\udd12</div>',
            '<h2 style="margin:0 0 10px;font-size:24px">Full Screen Required</h2>',
            '<p style="margin:0 0 24px;font-size:15px;opacity:.85">',
            'This exam must be taken in full screen. Click the button below to continue.',
            '</p>',
            '<button id="tagproctor-fs-reenter"',
            ' style="padding:12px 28px;font-size:16px;font-weight:700;',
            'border:none;border-radius:8px;background:#7e2a8e;color:#fff;cursor:pointer">',
            '\ud83d\udd12 Enter Full Screen',
            '</button>'
        ].join('');
        document.body.appendChild(overlay);

        /** Request fullscreen on the document element. */
        function enterFs() {
            var fsReq = document.documentElement.requestFullscreen ||
                        document.documentElement.webkitRequestFullscreen ||
                        document.documentElement.mozRequestFullScreen;
            if (fsReq) {
                fsReq.call(document.documentElement).catch(function() {});
            }
        }

        document.getElementById('tagproctor-fs-reenter').addEventListener('click', enterFs);

        /** Handle fullscreen change events, hiding the overlay when in fullscreen. */
        function onFsChange() {
            var isFs = !!(document.fullscreenElement ||
                          document.webkitFullscreenElement ||
                          document.mozFullScreenElement);
            if (isFs) {
                enteredFullscreen = true;
                overlay.style.display = 'none';
            } else {
                overlay.style.display = 'flex';
                // Only log after the student has actually entered fullscreen at least once.
                // Avoids a spurious event on the initial page load.
                if (enteredFullscreen) {
                    logEvent('fullscreenexit', 'Student exited full screen');
                }
            }
        }

        document.addEventListener('fullscreenchange', onFsChange);
        document.addEventListener('webkitfullscreenchange', onFsChange);
        document.addEventListener('mozfullscreenchange', onFsChange);

        // Show overlay immediately — student must click to enter fullscreen.
        // (requestFullscreen requires a user gesture so we cannot call it automatically.)
        onFsChange();
    }

    /**
     * Periodically detect if DevTools may be open via window-size heuristic.
     */
    function detectDevTools() {
        var threshold = 160;
        setInterval(function() {
            var widthDiff  = window.outerWidth - window.innerWidth;
            var heightDiff = window.outerHeight - window.innerHeight;
            if (widthDiff > threshold || heightDiff > threshold) {
                logEvent("devtools", "Possible DevTools open: w=" + widthDiff + " h=" + heightDiff);
            }
        }, 10000);
    }

    return {
        /**
         * Initialise the proctoring monitor for the current quiz attempt.
         *
         * @param {Object} config Configuration object: cmid, attemptid, interval.
         */
        init: function(config) {
            cfg = config;
            if (!cfg.attemptid || !cfg.cmid) {
                return;
            }

            // Reset quiz timer if the student just completed the pre-exam wizard.
            // recorder.js sets this flag in sessionStorage right before form submit.
            try {
                var resetFlag = sessionStorage.getItem('tagproctoring_reset_timer');
                if (resetFlag) {
                    sessionStorage.removeItem('tagproctoring_reset_timer');
                    Ajax.call([{
                        methodname: 'quizaccess_tagproctoring_reset_timer',
                        args: {cmid: cfg.cmid, attemptid: cfg.attemptid},
                        done: function(data) {
                            var added = data && data.added_seconds ? data.added_seconds : 0;
                            if (added > 0 &&
                                    window.M && M.mod_quiz && M.mod_quiz.timer &&
                                    M.mod_quiz.timer.endtime) {
                                // Push the timer end-time forward by the same amount.
                                M.mod_quiz.timer.endtime += added * 1000;
                            }
                        },
                        fail: function() { /* non-critical — quiz timer stays as-is */ }
                    }]);
                }
            } catch (e) { /* sessionStorage blocked — skip timer reset */ }

            startWebcam();
            enforceFullscreen();
            detectMultipleScreens();
            detectScreenShare();
            detectTabSwitch();
            detectCopyPaste();
            detectContextMenu();
            detectDevTools();

            // Stop webcam and remove live video when quiz is submitted.
            window.addEventListener("beforeunload", function() {
                if (webcamStream) {
                    webcamStream.getTracks().forEach(function(t) { t.stop(); });
                }
                if (screenshotInterval) {
                    clearInterval(screenshotInterval);
                }
                if (liveVideo && liveVideo.parentNode) {
                    liveVideo.parentNode.removeChild(liveVideo);
                }
            });
        }
    };
});
