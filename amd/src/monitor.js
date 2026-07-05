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
    var liveVideo = null;          // persistent hidden video element for webcam frame capture
    var screenVideo = null;        // persistent hidden video element for screen-share frame capture
    var cfg = {};
    var statusBarWebcamDot = null;
    var statusBarScreenDot = null;

    // Face-turn / face-away detection.
    var faceDetectModel = null;
    var noFaceCount     = 0;      // consecutive 3-s polls with no face in frame
    var lastNoFaceShot  = 0;      // Date.now() of last face-away screenshot

    // Cached last-good frame so screenshots succeed even when live videos are paused.
    var lastFrameCanvas = null;

    // Set to true while we are requesting our own screen share so detectScreenShare()
    // does not log it as a student-initiated share event.
    var ownScreenShareActive = false;

    // Set to true while the getDisplayMedia picker is open.
    // The picker causes fullscreen to exit and window to blur — both of which would
    // otherwise log spurious violations and show the overlay over the picker.
    var awaitingScreenShare = false;

    // Screenshot rate-limiting: enforce a minimum gap between uploads and prevent
    // concurrent requests. With 800+ simultaneous students this avoids server overload.
    var lastScreenshotSent = 0;
    var screenshotInFlight = false;

    /**
     * Detect the current browser name and version from the user-agent string.
     *
     * @returns {string} Browser name and version, e.g. "Chrome 124".
     */
    function detectBrowser() {
        var ua = navigator.userAgent;
        var m;
        if ((m = ua.match(/Edg\/(\d+)/)))    { return 'Edge ' + m[1]; }
        if ((m = ua.match(/OPR\/(\d+)/)))    { return 'Opera ' + m[1]; }
        if ((m = ua.match(/Firefox\/(\d+)/))){ return 'Firefox ' + m[1]; }
        if ((m = ua.match(/Version\/(\d+).+Safari/))) { return 'Safari ' + m[1]; }
        if ((m = ua.match(/Chrome\/(\d+)/))) { return 'Chrome ' + m[1]; }
        return 'Unknown';
    }

    /**
     * Detect the current operating system from the user-agent string.
     *
     * @returns {string} OS name, e.g. "Windows 11" or "macOS".
     */
    function detectOS() {
        var ua = navigator.userAgent;
        if (/Windows NT 10/.test(ua)) { return 'Windows 10/11'; }
        if (/Windows NT 6\.3/.test(ua)) { return 'Windows 8.1'; }
        if (/Windows/.test(ua)) { return 'Windows'; }
        if (/Mac OS X/.test(ua)) { return 'macOS'; }
        if (/Linux/.test(ua)) { return 'Linux'; }
        if (/Android/.test(ua)) { return 'Android'; }
        if (/iPhone|iPad/.test(ua)) { return 'iOS'; }
        return 'Unknown OS';
    }

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
     * Used for face-direction analysis and as fallback screenshot source.
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
     * Show a blocking overlay when the student stops screen sharing mid-exam.
     * Logs the violation and gives them a button to restart sharing.
     */
    function showScreenShareStopOverlay() {
        var el = document.createElement('div');
        el.id = 'tagproctor-ss-stop-overlay';
        el.style.cssText = [
            'position:fixed;top:0;left:0;right:0;bottom:0;z-index:10000;',
            'background:rgba(10,14,23,.97);display:flex;align-items:center;',
            'justify-content:center;font-family:sans-serif;'
        ].join('');
        el.innerHTML = [
            '<div style="text-align:center;color:#fff;max-width:480px;padding:40px;">',
            '<h2 style="font-size:22px;font-weight:700;margin:0 0 12px;color:#fff;">',
            'Screen Sharing Stopped</h2>',
            '<p style="color:#94a3b8;font-size:15px;line-height:1.5;margin:0 0 8px;">',
            'You stopped screen sharing. This has been recorded as a violation.</p>',
            '<p style="color:#94a3b8;font-size:15px;line-height:1.5;margin:0 0 28px;">',
            'You must restart screen sharing to continue your exam.</p>',
            '<button id="tagproctor-ss-restart" style="',
            'background:#334155;color:#fff;border:none;',
            'padding:14px 32px;border-radius:10px;font-size:16px;font-weight:600;',
            'cursor:pointer;box-shadow:0 4px 16px rgba(51,65,85,.4);">',
            'Restart Screen Sharing</button>',
            '</div>'
        ].join('');
        document.body.appendChild(el);

        document.getElementById('tagproctor-ss-restart').addEventListener('click', function() {
            el.style.display = 'none';
            ownScreenShareActive = true;
            awaitingScreenShare  = true;
            navigator.mediaDevices.getDisplayMedia({
                video: {displaySurface: 'monitor'},
                audio: false
            }).then(function(stream) {
                ownScreenShareActive = false;
                awaitingScreenShare  = false;
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
                createScreenVideo(stream);
            }).catch(function() {
                ownScreenShareActive = false;
                awaitingScreenShare  = false;
                logEvent('screensharefailed', 'Student failed to restart screen sharing');
                el.style.display = 'flex'; // show overlay again if they cancelled
            });
        });
    }

    /**
     * Create a persistent 1-pixel hidden video element bound to the screen-share stream.
     * Used as the primary source for event-triggered screenshots.
     *
     * @param {MediaStream} stream Screen-share stream from getDisplayMedia.
     */
    function createScreenVideo(stream) {
        if (screenVideo) {
            return;
        }
        var v = document.createElement('video');
        v.muted = true;
        v.playsInline = true;
        v.setAttribute('playsinline', '');
        v.style.cssText = 'position:fixed;width:1px;height:1px;top:-200px;left:-200px;' +
                          'opacity:0;pointer-events:none';
        v.srcObject = stream;
        document.body.appendChild(v);
        v.play().catch(function() {});
        screenVideo = v;
        updateStatusScreen(true);

        // Detect when the student stops sharing (clicks "Stop sharing" in browser UI).
        stream.getTracks().forEach(function(track) {
            track.addEventListener('ended', function() {
                logEvent('screensharestop', 'Screen share stream ended during exam');
                updateStatusScreen(false);
                if (screenVideo && screenVideo.parentNode) {
                    screenVideo.parentNode.removeChild(screenVideo);
                }
                screenVideo = null;
                showScreenShareStopOverlay();
            });
        });
    }

    /**
     * Update the module-level lastFrameCanvas with the latest decoded video frame.
     * Called every 2 s so captureScreenshot can use a cached frame when the live
     * videos are paused (e.g. the moment the window blurs on a tab switch).
     */
    function cacheLatestFrame() {
        var src = null;
        if (screenVideo && screenVideo.readyState >= 2 && screenVideo.videoWidth > 0) {
            src = screenVideo;
        } else if (liveVideo && liveVideo.readyState >= 2) {
            src = liveVideo;
        }
        if (!src) {
            return;
        }
        if (!lastFrameCanvas) {
            lastFrameCanvas = document.createElement('canvas');
        }
        var w = src.videoWidth || 320;
        var h = src.videoHeight || 240;
        var scale = Math.min(960 / w, 1);
        lastFrameCanvas.width  = Math.round(w * scale);
        lastFrameCanvas.height = Math.round(h * scale);
        lastFrameCanvas.getContext('2d').drawImage(src, 0, 0, lastFrameCanvas.width, lastFrameCanvas.height);
    }

    /**
     * Capture a screenshot and upload it.
     *
     * Prefers the live screen-share stream (shows what the student can see).
     * Falls back to the webcam frame if screen sharing is unavailable.
     * Falls back to the last cached frame if live videos are temporarily paused.
     * Only called on suspicious events — there are no periodic automatic shots.
     *
     * @param {string} [trigger] Source label: 'tabswitch', 'windowblur', 'faceturn', etc.
     */
    function captureScreenshot(trigger) {
        if (!cfg.enablescreenshots) {
            return;
        }
        if (['tabswitch', 'windowblur', 'contextmenu'].indexOf(trigger) === -1) {
            return;
        }
        // Rate limit: skip if an upload is already in-flight or was sent less than 5 s ago.
        // This prevents tab-switch + window-blur firing two concurrent uploads, and stops
        // burst overload when hundreds of students trigger events simultaneously.
        if (screenshotInFlight || (Date.now() - lastScreenshotSent) < 5000) {
            return;
        }
        var canvas = document.createElement('canvas');

        // Face-turn events capture the webcam feed — invigilators need to see the
        // student's face to understand why the alert fired, not the screen content.
        // All other events (tab switch, window blur, etc.) prefer the screen-share
        // stream so the invigilator can see what the student switched to.
        if (screenVideo && screenVideo.readyState >= 2 && screenVideo.videoWidth > 0) {
            var w = screenVideo.videoWidth;
            var h = screenVideo.videoHeight;
            // Scale down to max 960 px wide — smaller than 1280 reduces server storage and I/O.
            var scale = Math.min(960 / w, 1);
            canvas.width  = Math.round(w * scale);
            canvas.height = Math.round(h * scale);
            canvas.getContext('2d').drawImage(screenVideo, 0, 0, canvas.width, canvas.height);
        } else if (liveVideo && liveVideo.readyState >= 2) {
            // Webcam frame — used for face-turn events and as fallback when screen share unavailable.
            canvas.width  = 256;
            canvas.height = 192;
            canvas.getContext('2d').drawImage(liveVideo, 0, 0, 256, 192);
        } else if (lastFrameCanvas && lastFrameCanvas.width > 0) {
            // Both live videos are paused (e.g. tab just switched) — use the last cached frame.
            canvas = lastFrameCanvas;
        } else {
            return;
        }

        screenshotInFlight = true;
        canvas.toBlob(function(blob) {
            if (!blob) {
                screenshotInFlight = false;
                return;
            }
            var fd = new FormData();
            fd.append('screenshot', blob, 'shot.jpg');
            fd.append('cmid',      cfg.cmid);
            fd.append('attemptid', cfg.attemptid);
            fd.append('sesskey',   M.cfg.sesskey);
            fd.append('source',    trigger || 'screen');
            fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tagproctoring/screenshot_upload.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).then(function() {
                lastScreenshotSent = Date.now();
                screenshotInFlight = false;
            }).catch(function() {
                screenshotInFlight = false;
            });
        }, 'image/jpeg', 0.55);
    }

    /**
     * Load TF.js + BlazeFace in the background and start periodic face-direction checks.
     * If the student's face turns significantly, log an event and capture a screenshot.
     */
    function loadFaceDetection() {
        var TFJS = (cfg && cfg.vendorBase)
            ? cfg.vendorBase + '/js/tf.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js';
        var FACE = (cfg && cfg.vendorBase)
            ? cfg.vendorBase + '/js/blazeface.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';

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
                // No face in frame — student may have left the camera.
                // Flag after 3 consecutive no-face polls (≈9 s) to avoid false positives.
                if (!faces || faces.length === 0) {
                    noFaceCount++;
                    if (noFaceCount >= 3) {
                        var nowAway = Date.now();
                        if (nowAway - lastNoFaceShot > 30000) {
                            lastNoFaceShot = nowAway;
                            noFaceCount = 0;
                            logEvent('faceaway', 'Face not visible for ~9 seconds');
                        }
                    }
                    return;
                }

                noFaceCount = 0; // reset when face is visible again

                if (faces.length !== 1) {
                    return;
                }
                var lm = faces[0].landmarks;
                if (!lm || lm.length < 3) {
                    return;
                }
                // Normal left/right face movement is not treated as cheating evidence.
            }).catch(function() {});
        }, 3000);
    }

    /**
     * Zero-pad a number to two digits for the session timer display.
     *
     * @param {number} n Number to pad.
     * @returns {string} Two-digit string.
     */
    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    /**
     * Inject a fixed status bar at the top of the quiz page showing live monitoring status.
     * Creates webcam and screen-share indicators and starts an elapsed-time counter.
     */
    function createStatusBar() {
        var style = document.createElement('style');
        style.textContent = [
            '#tagproctor-statusbar{',
            'position:fixed;top:0;left:0;right:0;height:44px;z-index:9998;',
            'background:rgba(10,14,23,.95);backdrop-filter:blur(12px);',
            'border-bottom:1px solid rgba(255,255,255,.08);',
            'display:flex;align-items:center;justify-content:space-between;',
            'padding:0 20px;font-family:sans-serif;font-size:13px;color:#94a3b8;',
            'box-shadow:0 2px 16px rgba(0,0,0,.5)}',
            '#tagproctor-statusbar .tp-sb-left{display:flex;align-items:center;gap:6px;',
            'font-size:13px;font-weight:700;color:#e2e8f0;letter-spacing:.01em}',
            '#tagproctor-statusbar .tp-sb-indicators{display:flex;align-items:center;gap:20px}',
            '#tagproctor-statusbar .tp-sb-item{display:flex;align-items:center;gap:6px}',
            '#tagproctor-statusbar .tp-sb-dot{width:8px;height:8px;border-radius:50%;',
            'background:#6b7280;flex-shrink:0}',
            '#tagproctor-statusbar .tp-sb-dot.active{background:#22c55e;',
            'animation:tp-sb-pulse 2s ease-in-out infinite}',
            '@keyframes tp-sb-pulse{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}',
            '50%{box-shadow:0 0 0 4px rgba(34,197,94,0)}}',
            '#tagproctor-statusbar .tp-sb-timer{font-size:13px;color:#94a3b8;',
            'font-variant-numeric:tabular-nums}'
        ].join('');
        document.head.appendChild(style);

        var bar = document.createElement('div');
        bar.id = 'tagproctor-statusbar';
        bar.innerHTML = [
            '<div class="tp-sb-left">Secure Exam Active</div>',
            '<div class="tp-sb-indicators">',
            '<div class="tp-sb-item">',
            '<span class="tp-sb-dot" id="tp-sb-dot-cam"></span>',
            '<span id="tp-sb-lbl-cam" style="color:#6b7280">Webcam Initialising</span>',
            '</div>',
            '<div class="tp-sb-item">',
            '<span class="tp-sb-dot" id="tp-sb-dot-scr"></span>',
            '<span id="tp-sb-lbl-scr" style="color:#6b7280">Screen Share Pending</span>',
            '</div>',
            '</div>',
            '<div class="tp-sb-timer" id="tp-sb-timer">00:00</div>'
        ].join('');
        document.body.appendChild(bar);
        document.body.style.paddingTop = '44px';

        statusBarWebcamDot = document.getElementById('tp-sb-dot-cam');
        statusBarScreenDot = document.getElementById('tp-sb-dot-scr');

        var startTime = Date.now();
        var timerEl = document.getElementById('tp-sb-timer');
        setInterval(function() {
            var elapsed = Math.floor((Date.now() - startTime) / 1000);
            var h = Math.floor(elapsed / 3600);
            var m = Math.floor((elapsed % 3600) / 60);
            var s = elapsed % 60;
            var display = (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(s);
            if (timerEl) { timerEl.textContent = display; }
        }, 1000);
    }

    /**
     * Update the webcam status indicator in the top bar.
     *
     * @param {boolean} active True if webcam stream is live.
     */
    function updateStatusWebcam(active) {
        if (!statusBarWebcamDot) {
            return;
        }
        var lbl = document.getElementById('tp-sb-lbl-cam');
        if (active) {
            statusBarWebcamDot.className = 'tp-sb-dot active';
            if (lbl) { lbl.style.color = '#e2e8f0'; lbl.textContent = 'Webcam Active'; }
        } else {
            statusBarWebcamDot.className = 'tp-sb-dot';
            if (lbl) { lbl.style.color = '#6b7280'; lbl.textContent = 'Webcam Inactive'; }
        }
    }

    /**
     * Update the screen-share status indicator in the top bar.
     *
     * @param {boolean} active True if screen-share stream is live.
     */
    function updateStatusScreen(active) {
        if (!statusBarScreenDot) {
            return;
        }
        var lbl = document.getElementById('tp-sb-lbl-scr');
        if (active) {
            statusBarScreenDot.className = 'tp-sb-dot active';
            if (lbl) { lbl.style.color = '#e2e8f0'; lbl.textContent = 'Screen Shared'; }
        } else {
            statusBarScreenDot.className = 'tp-sb-dot';
            if (lbl) { lbl.style.color = '#6b7280'; lbl.textContent = 'Screen Share Stopped'; }
        }
    }

    /**
     * Request webcam access, create the live video element, and load face detection.
     * Screenshots are event-triggered only — no periodic timer is set here.
     */
    function startWebcam() {
        navigator.mediaDevices.getUserMedia({video: {width: 320, height: 240}, audio: false})
            .then(function(stream) {
                webcamStream = stream;
                createLiveVideo();
                updateStatusWebcam(true);
                // Cache the latest video frame every 2 s so screenshots work
                // even if videos are momentarily paused on tab switch / window blur.
                setInterval(cacheLatestFrame, 2000);
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
     * Calls from our own enforceFullscreen handler are excluded via ownScreenShareActive.
     */
    function detectScreenShare() {
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            var original = navigator.mediaDevices.getDisplayMedia.bind(navigator.mediaDevices);
            navigator.mediaDevices.getDisplayMedia = function(constraints) {
                if (!ownScreenShareActive) {
                    logEvent("screenshare", "Student initiated screen share");
                }
                return original(constraints);
            };
        }
    }

    /**
     * Log tab-switch and window-blur events, and capture a screenshot each time.
     */
    function detectTabSwitch() {
        var switchCount = 0;
        var pageUnloading = false;

        // Set flag so blur during navigation (Finish attempt, etc.) is ignored.
        window.addEventListener('beforeunload', function() {
            pageUnloading = true;
        });

        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                if (awaitingScreenShare) {
                    return;
                }
                // Some browser permission dialogs and transient UI can briefly hide the
                // page. Only log this if the page remains hidden.
                setTimeout(function() {
                    if (pageUnloading || awaitingScreenShare || !document.hidden) {
                        return;
                    }
                    switchCount++;
                    logEvent("tabswitch", "Exam page was hidden, count=" + switchCount);
                    captureScreenshot('tabswitch');
                }, 1000);
            }
        });

        window.addEventListener("blur", function() {
            if (awaitingScreenShare || pageUnloading) {
                return;
            }
            // Debounce 500 ms: Moodle confirm dialogs and system dialogs briefly steal
            // focus then return it. Only log if focus stays away (real app switch).
            setTimeout(function() {
                if (pageUnloading || document.hasFocus()) {
                    return;
                }
                logEvent("windowblur", "Student switched away from the exam window");
                captureScreenshot('windowblur');
            }, 500);
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
            captureScreenshot('contextmenu');
        });
    }

    /**
     * Show a blocking overlay whenever the student is not in fullscreen.
     * Overlay severity escalates on repeated exits to deter violations.
     */
    function enforceFullscreen() {
        var enteredFullscreen = false;
        var exitCount = 0;
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

        /**
         * Request screen-share and keep the stream alive for event screenshots.
         * Called on the same user gesture as enterFs() so the browser allows it.
         */
        function requestScreenShare() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                return;
            }
            ownScreenShareActive = true;
            awaitingScreenShare = true;
            navigator.mediaDevices.getDisplayMedia({
                video: {displaySurface: 'monitor'},
                audio: false
            }).then(function(stream) {
                ownScreenShareActive = false;
                awaitingScreenShare = false;
                createScreenVideo(stream);
                // requestFullscreen inside a Promise .then() is not a direct user gesture,
                // so some browsers block it. A short setTimeout works in Chromium and
                // harmlessly falls back to the fullscreen overlay elsewhere.
                setTimeout(function() {
                    var fsReq = document.documentElement.requestFullscreen ||
                                document.documentElement.webkitRequestFullscreen ||
                                document.documentElement.mozRequestFullScreen;
                    if (fsReq) {
                        fsReq.call(document.documentElement).catch(function() {});
                    }
                }, 100);
            }).catch(function() {
                ownScreenShareActive = false;
                awaitingScreenShare = false;
            });
        }

        /** Rebuild the overlay panel content and button for the current exitCount. */
        function renderOverlay() {
            var title, msg, sub, btnText, btnBg, borderCol, panelBg, countLine;
            if (exitCount === 0) {
                title     = 'Full Screen Required';
                msg       = 'This exam must be taken in full screen with screen sharing active.';
                sub       = 'Click the button below, then select <strong>Entire Screen</strong> when prompted.';
                btnText   = 'Enter Full Screen';
                btnBg     = '#334155';
                borderCol = 'rgba(51,65,85,.55)';
                panelBg   = 'rgba(51,65,85,.18)';
            } else if (exitCount === 1) {
                title     = 'Exam Monitoring Alert';
                msg       = 'You exited fullscreen. This has been recorded as a violation.';
                sub       = 'Please return to fullscreen immediately to continue your exam.';
                btnText   = 'Return to Full Screen';
                btnBg     = '#b45309';
                borderCol = 'rgba(234,179,8,.60)';
                panelBg   = 'rgba(234,179,8,.10)';
            } else if (exitCount === 2) {
                title     = 'Repeated Violation Recorded';
                msg       = 'You have exited fullscreen ' + exitCount + ' times. Your session is under review.';
                sub       = 'Return to fullscreen immediately to continue your exam.';
                btnText   = 'Return to Full Screen Now';
                btnBg     = '#c2410c';
                borderCol = 'rgba(249,115,22,.65)';
                panelBg   = 'rgba(249,115,22,.12)';
            } else {
                title     = 'Critical: Multiple Violations';
                msg       = 'You have exited fullscreen ' + exitCount + ' times. Your invigilator has been notified.';
                sub       = 'Further violations will result in your exam being flagged for review.';
                btnText   = 'Return to Full Screen Now';
                btnBg     = '#b91c1c';
                borderCol = 'rgba(239,68,68,.70)';
                panelBg   = 'rgba(239,68,68,.14)';
            }
            countLine = exitCount > 0
                ? '<p style="margin:0 0 20px;font-size:12px;color:rgba(255,255,255,.50)">Violation count: ' + exitCount + '</p>'
                : '<p style="margin:0 0 20px;font-size:14px;color:rgba(255,255,255,.75)">' +
                  'Click the button below to begin your exam.</p>';
            overlay.innerHTML = [
                '<div style="background:' + panelBg + ';border:1.5px solid ' + borderCol + ';',
                'border-radius:16px;padding:32px 40px;max-width:480px;width:100%;text-align:center">',
                '<h2 style="margin:0 0 10px;font-size:22px;font-weight:700;color:#fff">' + title + '</h2>',
                '<p style="margin:0 0 8px;font-size:14px;color:rgba(255,255,255,.80);line-height:1.6">' + msg + '</p>',
                '<p style="margin:0 0 4px;font-size:13px;color:rgba(255,255,255,.65);line-height:1.5">' + sub + '</p>',
                countLine,
                '<button id="tagproctor-fs-reenter"',
                ' style="padding:12px 28px;font-size:15px;font-weight:700;',
                'border:none;border-radius:8px;background:' + btnBg + ';color:#fff;cursor:pointer">',
                btnText,
                '</button>',
                '</div>'
            ].join('');
            document.getElementById('tagproctor-fs-reenter').addEventListener('click', function() {
                if (!screenVideo) {
                    requestScreenShare();
                } else {
                    enterFs();
                }
            });
        }

        /** Handle fullscreen change events, hiding the overlay when in fullscreen. */
        function onFsChange() {
            var isFs = !!(document.fullscreenElement ||
                          document.webkitFullscreenElement ||
                          document.mozFullScreenElement);
            if (isFs) {
                enteredFullscreen = true;
                overlay.style.display = 'none';
            } else {
                if (awaitingScreenShare) {
                    return;
                }
                if (enteredFullscreen) {
                    exitCount++;
                    logEvent('fullscreenexit', 'Student exited fullscreen, count=' + exitCount);
                }
                renderOverlay();
                overlay.style.display = 'flex';
            }
        }

        document.addEventListener('fullscreenchange', onFsChange);
        document.addEventListener('webkitfullscreenchange', onFsChange);
        document.addEventListener('mozfullscreenchange', onFsChange);

        // Show the overlay immediately on load.
        onFsChange();
    }

    /**
     * Return true if the current device is a mobile or tablet.
     *
     * @returns {boolean}
     */
    function isMobileDevice() {
        return /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Return whether core monitoring APIs are available.
     *
     * @returns {Object} Capability status.
     */
    function getCapabilityStatus() {
        var missing = [];
        var fsEl = document.documentElement;
        var fsReq = fsEl.requestFullscreen || fsEl.webkitRequestFullscreen || fsEl.mozRequestFullScreen;

        if (!window.isSecureContext) {
            missing.push('secure HTTPS context');
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            missing.push('webcam access');
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            missing.push('screen sharing');
        }
        if (!fsReq) {
            missing.push('fullscreen');
        }

        return {
            supported: missing.length === 0,
            missing: missing
        };
    }

    /**
     * Return true if the browser exposes the multi-monitor detection signal.
     *
     * @returns {boolean} True when the signal is available.
     */
    function canDetectExtendedScreen() {
        return !!(window.screen && typeof window.screen.isExtended === 'boolean');
    }

    /**
     * Show a blocking overlay when the device or browser is unsupported.
     * Returns true if the check failed (caller should abort init).
     *
     * @returns {boolean} True if environment is unsupported.
     */
    function checkEnvironment() {
        var mobile  = isMobileDevice();
        var caps = getCapabilityStatus();
        if (!mobile && caps.supported) {
            if (!canDetectExtendedScreen()) {
                logEvent('capabilitywarning', 'Multiple monitor detection unavailable in ' + detectBrowser());
            }
            return false;
        }

        var title, msg, sub;
        if (mobile) {
            title = 'Desktop or Laptop Required';
            msg   = 'Secure exams cannot be taken on a mobile phone or tablet.';
            sub   = 'Please use a desktop or laptop computer with a current browser.';
        } else {
            title = 'Unsupported Browser';
            msg   = 'This secure exam requires camera, screen sharing, fullscreen, and HTTPS support.';
            sub   = 'Missing: ' + caps.missing.join(', ') +
                '. Please update your browser or reopen this page in a supported desktop browser.';
        }

        var overlay = document.createElement('div');
        overlay.style.cssText = [
            'position:fixed;inset:0;z-index:1000001',
            'background:rgba(0,0,0,.96)',
            'display:flex;flex-direction:column;align-items:center;justify-content:center',
            'color:#fff;font-family:sans-serif;text-align:center;padding:32px'
        ].join(';');
        overlay.innerHTML = [
            '<div style="background:rgba(239,68,68,.12);border:1.5px solid rgba(239,68,68,.55);',
            'border-radius:16px;padding:36px 44px;max-width:460px;width:100%">',
            '<h2 style="margin:0 0 10px;font-size:22px;font-weight:700;color:#fff">' + title + '</h2>',
            '<p style="margin:0 0 8px;font-size:14px;color:rgba(255,255,255,.80);line-height:1.6">' + msg + '</p>',
            '<p style="font-size:13px;color:rgba(255,255,255,.55);line-height:1.5">' + sub + '</p>',
            '</div>'
        ].join('');
        document.body.appendChild(overlay);
        return true;
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
            if (checkEnvironment()) {
                return;
            }

            // Log browser and OS info once at session start so invigilators can
            // see what environment the student used without reading raw user-agent strings.
            logEvent('sessionstart', detectBrowser() + ' / ' + detectOS());

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

            createStatusBar();
            startWebcam();
            enforceFullscreen();
            detectMultipleScreens();
            detectScreenShare();
            detectTabSwitch();
            detectCopyPaste();
            detectContextMenu();
            detectDevTools();

            // Stop streams and remove hidden video elements when quiz is submitted.
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
                if (screenVideo && screenVideo.parentNode) {
                    screenVideo.parentNode.removeChild(screenVideo);
                }
            });
        }
    };
});
