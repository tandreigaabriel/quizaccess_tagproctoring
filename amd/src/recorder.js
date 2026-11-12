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
 * Pre-exam wizard: room scan, selfie, ID photo, screen-share verification.
 *
 * @module     quizaccess_tagproctoring/recorder
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* global cocoSsd, blazeface */

define("quizaccess_tagproctoring/recorder", ['core/str', 'quizaccess_tagproctoring/strings'], function(Str, I18n) {
    'use strict';

    var cfg = {};
    // Error strings loaded from Moodle lang on init.
    var ES = {};

    // Step completion flags: 0=rules, 1=roomscan, 2=selfie, 3=idphoto, 4=screenshare.
    var done = [false, false, false, false, false];

    // Shared webcam stream (reused across steps 1–3).
    var webcamStream = null;
    var mediaRecorder = null;
    var recordedChunks = [];

    // AI detection state.
    var tfModel = null;
    var faceModel = null;
    var detectedObjects = {};
    var detectionInterval = null;

    // Vendor base URL — resolved from cfg.vendorBase at runtime (set in rule.php).
    // Falls back to jsDelivr CDN if local files are not yet deployed.

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Shorthand for getElementById.
     *
     * @param {string} id Element ID.
     * @returns {HTMLElement|null} The element, or null.
     */
    function el(id) {
        return document.getElementById(id);
    }

    /**
     * Show the wizard step at index idx and update indicator states.
     *
     * @param {number} idx Step index to activate (0–4).
     */
    function showStep(idx) {
        for (var i = 0; i < 5; i++) {
            var panel = el('tagwiz-step-' + i);
            if (panel) {
                panel.style.display = (i === idx) ? 'block' : 'none';
            }
            var ind = el('tagwiz-ind-' + i);
            if (ind) {
                ind.className = 'tagwiz-ind';
                if (i < idx || done[i]) {
                    ind.classList.add('tagwiz-ind--done');
                } else if (i === idx) {
                    ind.classList.add('tagwiz-ind--active');
                }
            }
            var conn = el('tagwiz-conn-' + i);
            if (conn) {
                conn.className = 'tagwiz-connector' + (i < idx ? ' tagwiz-connector--done' : '');
            }
        }
    }

    /**
     * Mark a wizard step as complete and update its indicator.
     *
     * @param {number} idx Step index to mark done.
     */
    function markDone(idx) {
        done[idx] = true;
        var ind = el('tagwiz-ind-' + idx);
        if (ind) {
            ind.className = 'tagwiz-ind tagwiz-ind--done tagwiz-ind--pop';
            setTimeout(function() {
                ind.classList.remove('tagwiz-ind--pop');
            }, 500);
        }
    }

    /**
     * Mark a wizard step as failed and update its indicator.
     *
     * @param {number} idx Step index to mark failed.
     */
    function markFailed(idx) {
        var ind = el('tagwiz-ind-' + idx);
        if (ind) {
            ind.className = 'tagwiz-ind tagwiz-ind--failed';
        }
    }

    /**
     * Set a status message element's text and Bootstrap alert class.
     *
     * @param {string} elId Element ID of the status container.
     * @param {string} msg  Message text to display.
     * @param {string} type Bootstrap alert type (e.g. 'danger', 'success'), or '' for plain.
     */
    function setStatus(elId, msg, type) {
        var s = el(elId);
        if (!s) {
            return;
        }
        s.textContent = msg;
        if (s.classList.contains('tagwiz-overlay-status') || elId === 'tagwiz-rs-status') {
            s.className = 'tagwiz-overlay-status' + (type ? ' tagwiz-overlay-status--' + type : '');
            return;
        }
        s.className = 'tagwiz-status-msg' + (type ? ' alert alert-' + type + ' mt-2 py-1 px-2' : '');
    }

    // ── Webcam bootstrap ────────────────────────────────────────────────────

    /**
     * Ensure the shared webcam stream is active, then invoke callback.
     *
     * @param {Function} callback Called with (stream) on success or (null, err) on failure.
     */
    function ensureWebcam(callback) {
        if (webcamStream && webcamStream.active) {
            callback(webcamStream);
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            callback(null, new Error(ES.media_unavailable || I18n.get('ui_media_devices_are_not_available_in_this_browser')));
            return;
        }

        navigator.mediaDevices.getUserMedia({video: true, audio: true})
            .then(function(stream) {
                webcamStream = stream;
                callback(stream);
            })
            .catch(function(err) {
                navigator.mediaDevices.getUserMedia({video: true, audio: false})
                    .then(function(stream) {
                        webcamStream = stream;
                        callback(stream);
                    })
                    .catch(function(videoErr) {
                        callback(null, videoErr || err);
                    });
            });
    }

    /**
     * Attach a media stream to a video element and begin playback.
     *
     * @param {HTMLVideoElement} videoEl Target video element.
     * @param {MediaStream}      stream  Stream to attach.
     */
    function attachStream(videoEl, stream) {
        if (!videoEl || !stream) {
            return;
        }
        videoEl.srcObject = stream;
        videoEl.play().catch(function() {});
    }

    /**
     * Pick the best room-scan recording format supported by the current browser.
     *
     * @returns {{mimeType: string, extension: string}|null} Supported format info.
     */
    function getRecordingFormat() {
        var types = [
            {mimeType: 'video/webm;codecs=vp9,opus', extension: 'webm'},
            {mimeType: 'video/webm;codecs=vp8,opus', extension: 'webm'},
            {mimeType: 'video/webm', extension: 'webm'},
            {mimeType: 'video/mp4;codecs=h264,aac', extension: 'mp4'},
            {mimeType: 'video/mp4', extension: 'mp4'}
        ];

        if (!window.MediaRecorder) {
            return null;
        }

        if (!MediaRecorder.isTypeSupported) {
            return {mimeType: '', extension: 'webm'};
        }

        for (var i = 0; i < types.length; i++) {
            if (MediaRecorder.isTypeSupported(types[i].mimeType)) {
                return types[i];
            }
        }

        return null;
    }

    // ── AI Detection ─────────────────────────────────────────────────────────

    /**
     * Dynamically load an external script while temporarily hiding RequireJS define
     * to prevent "Mismatched anonymous define()" errors from TF.js / COCO-SSD / BlazeFace.
     *
     * @param {string}   src      Script URL to load.
     * @param {Function} callback Called with no args on success, or ("error") on failure.
     */
    function loadScript(src, callback) {
        if (document.querySelector('script[src="' + src + '"]')) {
            callback();
            return;
        }

        var savedDefine = window.define;
        window.define = undefined;

        var s = document.createElement("script");
        s.src = src;
        s.async = true;

        s.onload = function() {
            window.define = savedDefine;
            callback();
        };

        s.onerror = function() {
            window.define = savedDefine;
            callback("error");
        };

        document.head.appendChild(s);
    }

    /**
     * Load TF.js, COCO-SSD, and BlazeFace, then start a detection interval
     * that populates detectedObjects from the room-scan video feed.
     *
     * @param {HTMLVideoElement} videoEl Live webcam video element to analyse.
     */
    function startAI(videoEl) {
        var jsBase = (cfg && cfg.vendorBase)
            ? cfg.vendorBase + '/js'
            : 'https://cdn.jsdelivr.net/npm';
        var TFJS = (cfg && cfg.vendorBase)
            ? jsBase + '/tf.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js';
        var COCO = (cfg && cfg.vendorBase)
            ? jsBase + '/coco-ssd.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js';
        var FACE = (cfg && cfg.vendorBase)
            ? jsBase + '/blazeface.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';

        loadScript(TFJS, function() {
            if (!window.tf) {
                return;
            }
            loadScript(COCO, function() {
                loadScript(FACE, function() {
                    cocoSsd.load().then(function(model) {
                        tfModel = model;
                        blazeface.load().then(function(face) {
                            faceModel = face;
                            detectionInterval = setInterval(function() {
                                if (!videoEl || videoEl.readyState < 2) {
                                    return;
                                }
                                tfModel.detect(videoEl).then(function(objects) {
                                    var persons = 0;
                                    objects.forEach(function(o) {
                                        if (o.score < (cfg.aiConfidence || 0.35)) {
                                            return;
                                        }
                                        var cls = o.class;
                                        var flagged = cfg.flaggedObjects || ["cell phone", "book", "laptop", "tablet", "remote"];
                                        if (flagged.includes(cls)) {
                                            detectedObjects[cls] = (detectedObjects[cls] || 0) + 1;
                                        }
                                        if (cls === "person") {
                                            persons++;
                                        }
                                    });
                                    if (persons > 1) {
                                        detectedObjects["extra_person"] =
                                            (detectedObjects["extra_person"] || 0) + 1;
                                    }
                                });

                                if (faceModel) {
                                    faceModel.estimateFaces(videoEl, false).then(function(faces) {
                                        // Only flag multiple faces — extra person present in the room.
                                        // no_face and face_turned are expected during room panning and
                                        // must NOT be flagged as suspicious in the room scan context.
                                        if (faces.length > 1) {
                                            detectedObjects["multiple_faces"] =
                                                (detectedObjects["multiple_faces"] || 0) + 1;
                                        }
                                    });
                                }
                            }, 1500);
                        });
                    });
                });
            });
        });
    }

    /**
     * Stop the AI detection interval.
     */
    function stopAI() {
        if (detectionInterval) {
            clearInterval(detectionInterval);
            detectionInterval = null;
        }
    }

    /**
     * Ensure BlazeFace is loaded and faceModel is set, then call callback.
     * If room scan already loaded the model, the callback fires immediately.
     *
     * @param {Function} callback Called when model is ready (or unavailable).
     */
    function ensureFaceModel(callback) {
        if (faceModel) {
            callback();
            return;
        }
        var jsBase = (cfg && cfg.vendorBase) ? cfg.vendorBase + '/js' : '';
        var TFJS = jsBase
            ? jsBase + '/tf.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js';
        var FACE = jsBase
            ? jsBase + '/blazeface.min.js'
            : 'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js';
        loadScript(TFJS, function() {
            if (!window.tf) {
                callback();
                return;
            }
            loadScript(FACE, function() {
                if (!window.blazeface) {
                    callback();
                    return;
                }
                blazeface.load().then(function(model) {
                    faceModel = model;
                    callback();
                }).catch(function() {
                    callback();
                });
            });
        });
    }

    // ── Step 0 — Rules ──────────────────────────────────────────────────────

    /**
     * Initialise the rules step as a slide-by-slide walkthrough.
     * Each slide shows one rule; the student presses Next to advance.
     * On the final (consent) slide the button becomes "I Agree & Continue".
     */
    function initRules() {
        var nextBtn = el('tagwiz-rules-next');
        var slides  = document.querySelectorAll('#tagwiz-rule-slides .tagwiz-rule-slide');
        var counter = el('tagwiz-rule-counter');
        var fill    = el('tagwiz-rule-progress-fill');
        if (!nextBtn || !slides.length) {
            return;
        }
        var total   = slides.length;
        var current = 0;

        /** @returns {void} */
        function updateSlide() {
            for (var i = 0; i < slides.length; i++) {
                slides[i].style.display = (i === current) ? 'flex' : 'none';
            }
            if (counter) {
                counter.textContent = I18n.get('wizard_rule_counter', {current: current + 1, total: total});
            }
            if (fill) {
                fill.style.width = Math.round((current + 1) / total * 100) + '%';
            }
            nextBtn.textContent = (current === total - 1) ? I18n.get('wizard_rule_agree') : I18n.get('wizard_rule_next');
        }

        updateSlide();

        nextBtn.addEventListener('click', function() {
            if (current < total - 1) {
                current++;
                updateSlide();
                return;
            }
            var fsEl  = document.documentElement;
            var fsReq = fsEl.requestFullscreen || fsEl.webkitRequestFullscreen || fsEl.mozRequestFullScreen;
            if (fsReq) { fsReq.call(fsEl).catch(function() {}); }
            markDone(0);
            if (cfg && cfg.enableRoomScan === 0) {
                markDone(1);
                showStep(2);
                initSelfie();
            } else {
                showStep(1);
                initRoomScan();
            }
        });
    }

    // ── Step 1 — Room Scan ──────────────────────────────────────────────────

    /**
     * Initialise the room-scan recording step.
     */
    function initRoomScan() {
        var video     = el('tagwiz-rs-video');
        var startBtn  = el('tagwiz-rs-start');
        var dot       = el('tagwiz-rs-dot');
        var statusEl  = el('tagwiz-rs-status');
        var timerEl   = el('tagwiz-rs-timer');
        var successEl = el('tagwiz-rs-success');
        var nextBtn   = el('tagwiz-rs-next');

        var MAX_SEC = (cfg && cfg.scanDuration && parseInt(cfg.scanDuration, 10) > 0)
            ? parseInt(cfg.scanDuration, 10)
            : 45;
        var MIN_SEC = Math.min(10, MAX_SEC);
        var elapsed = 0;
        var tickInterval = null;
        var started = false;
        var recordingFormat = getRecordingFormat();

        if (!recordingFormat) {
            setStatus('tagwiz-rs-status',
                I18n.get('ui_video_recording_is_not_supported_in_this_browser_please_use_a_current_desktop_brow'),
                'danger');
            startBtn.disabled = true;
            return;
        }

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-rs-status',
                    I18n.get('ui_camera_access_denied_please_allow_camera_access_in_your_browser_and_reload'), 'danger');
                startBtn.disabled = true;
                return;
            }
            attachStream(video, stream);
            startAI(video);

            try {
                mediaRecorder = recordingFormat.mimeType
                    ? new MediaRecorder(stream, {mimeType: recordingFormat.mimeType})
                    : new MediaRecorder(stream);
            } catch (e) {
                setStatus('tagwiz-rs-status',
                    I18n.get('ui_video_recording_could_not_start_in_this_browser_please_use_a_current_desktop_brows'),
                    'danger');
                startBtn.disabled = true;
                return;
            }

            mediaRecorder.ondataavailable = function(e) {
                if (e.data && e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = function() {
                var blob = new Blob(recordedChunks, {type: recordingFormat.mimeType || mediaRecorder.mimeType});
                recordedChunks = [];
                uploadRoomScan(blob);
                stopAI();
            };

            startBtn.addEventListener('click', function() {
                if (mediaRecorder.state === 'inactive' && !started) {
                    // 5-second get-ready countdown before recording begins.
                    startBtn.disabled = true;
                    var countdown = 5;
                    statusEl.textContent = '';
                    timerEl.textContent = I18n.get('wizard_start_countdown', countdown);

                    var cdInterval = setInterval(function() {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(cdInterval);
                            // Begin actual recording.
                            started = true;
                            elapsed = 0;
                            recordedChunks = [];
                            mediaRecorder.start();
                            dot.classList.add('is-recording');
                            startBtn.disabled = false;
                            startBtn.textContent = I18n.get('ui_stop_recording');
                            startBtn.classList.remove('tagwiz-btn--record');
                            startBtn.classList.add('tagwiz-btn--danger');
                            statusEl.textContent =
                                I18n.get('ui_recording_slowly_pan_360_around_the_room_and_desk');
                            timerEl.textContent = I18n.get('wizard_seconds', {elapsed: 0, total: MAX_SEC});

                            tickInterval = setInterval(function() {
                                elapsed++;
                                timerEl.textContent = I18n.get('wizard_seconds', {elapsed: elapsed, total: MAX_SEC});
                                if (elapsed >= MAX_SEC) {
                                    clearInterval(tickInterval);
                                    stopRecording();
                                }
                            }, 1000);
                        } else {
                            timerEl.textContent = I18n.get('wizard_start_countdown', countdown);
                        }
                    }, 1000);

                } else if (mediaRecorder.state === 'recording') {
                    if (elapsed < MIN_SEC) {
                        setStatus('tagwiz-rs-status',
                            I18n.get('wizard_scan_progress', {elapsed: elapsed, minimum: MIN_SEC}),
                            '');
                        return;
                    }
                    clearInterval(tickInterval);
                    stopRecording();
                }
            });
        });

        /**
         * Stop the active room-scan recording and trigger upload.
         */
        function stopRecording() {
            dot.classList.remove('is-recording');
            startBtn.disabled = true;
            startBtn.textContent = I18n.get('ui_uploading');
            timerEl.textContent = '';
            mediaRecorder.stop();
        }

        /**
         * Upload the recorded room-scan video blob.
         *
         * @param {Blob} blob Recorded video blob.
         */
        function uploadRoomScan(blob) {
            statusEl.textContent = I18n.get('ui_uploading_room_scan');
            var fd = new FormData();
            fd.append('video', blob, 'roomscan.' + recordingFormat.extension);
            fd.append('video_mime', recordingFormat.mimeType || blob.type || '');
            fd.append('scan_objects', JSON.stringify(detectedObjects));
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.uploadurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) {
                    return r.text().then(function(text) {
                        var data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            throw new Error(text ? text.slice(0, 180) : I18n.get('ui_empty_upload_response'));
                        }
                        if (!r.ok) {
                            throw new Error(data.message || ('HTTP ' + r.status));
                        }
                        return data;
                    });
                })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        if (data.suspicious) {
                            var flagged = Array.isArray(data.flagged) && data.flagged.length
                                ? data.flagged.join(', ')
                                : I18n.get('ui_prohibited_item');
                            setStatus('tagwiz-rs-status',
                                I18n.get('wizard_detected', flagged),
                                'danger');
                            startBtn.disabled = false;
                            startBtn.textContent = I18n.get('ui_redo_room_scan');
                            startBtn.classList.remove('tagwiz-btn--danger');
                            startBtn.classList.add('tagwiz-btn--record');
                            started = false;
                        } else {
                            successEl.style.display = 'block';
                            statusEl.textContent = '';
                            startBtn.disabled = true;
                            startBtn.textContent = I18n.get('ui_room_scan_complete');
                            startBtn.classList.remove('tagwiz-btn--danger');
                            startBtn.classList.add('tagwiz-btn--record');
                            nextBtn.disabled = false;
                            markDone(1);
                        }
                    } else {
                        setStatus('tagwiz-rs-status',
                            I18n.get('wizard_upload_failed', data.message || I18n.get('ui_unknown_error')), 'danger');
                        markFailed(1);
                        startBtn.disabled = false;
                        startBtn.textContent = I18n.get('ui_retry_recording');
                        startBtn.classList.remove('tagwiz-btn--danger');
                        startBtn.classList.add('tagwiz-btn--record');
                        started = false;
                    }
                })
                .catch(function(err) {
                    setStatus('tagwiz-rs-status',
                        I18n.get('ui_upload_error') + (err && err.message ? err.message : I18n.get('ui_please_retry')),
                        'danger');
                    markFailed(1);
                    startBtn.disabled = false;
                    startBtn.textContent = I18n.get('ui_retry_recording');
                    startBtn.classList.remove('tagwiz-btn--danger');
                    startBtn.classList.add('tagwiz-btn--record');
                    started = false;
                });
        }

        nextBtn.addEventListener('click', function() {
            if (!done[1]) {
                return;
            }
            showStep(2);
            initSelfie();
        });
    }

    // ── Step 2 — Selfie ───────────────────────────────────────────────

    /**
     * Initialise the selfie capture step with real-time face detection and auto-capture.
     */
    function initSelfie() {
        var video       = el('tagwiz-selfie-video');
        var captureBtn  = el('tagwiz-selfie-capture');
        var retakeBtn   = el('tagwiz-selfie-retake');
        var previewWrap = el('tagwiz-selfie-preview-wrap');
        var previewImg  = el('tagwiz-selfie-preview');
        var nextBtn     = el('tagwiz-selfie-next');
        var countdownEl = el('tagwiz-selfie-countdown');

        var selfieCanvas = el('tagwiz-selfie-canvas');
        var selfieCtx    = selfieCanvas ? selfieCanvas.getContext('2d') : null;
        var blurAnimId   = null;

        // Oval guide appearance -- updated by face detection loop.
        var ovalColor = 'rgba(156,163,175,0.85)';
        var ovalDash  = [10, 6];

        // Auto-capture state.
        var stableFrames     = 0;
        var captureTimer     = null;
        var captureActive    = false;
        var faceScanInterval = null;
        var faceScanActive   = false;

        /**
         * Render one frame: blurred background, dark tint, clear oval, colour-coded border.
         */
        function drawBlurFrame() {
            if (!selfieCtx || !video || video.readyState < 2) {
                blurAnimId = requestAnimationFrame(drawBlurFrame);
                return;
            }
            var W  = selfieCanvas.width;
            var H  = selfieCanvas.height;
            var cx = W / 2;
            var cy = H / 2;
            var rx = W * 0.38;
            var ry = H * 0.45;

            selfieCtx.filter = 'blur(14px)';
            selfieCtx.drawImage(video, 0, 0, W, H);

            selfieCtx.filter = 'none';
            selfieCtx.fillStyle = 'rgba(0,0,0,0.35)';
            selfieCtx.fillRect(0, 0, W, H);

            selfieCtx.save();
            selfieCtx.beginPath();
            selfieCtx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
            selfieCtx.clip();
            selfieCtx.filter = 'none';
            selfieCtx.drawImage(video, 0, 0, W, H);
            selfieCtx.restore();

            selfieCtx.save();
            selfieCtx.beginPath();
            selfieCtx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
            selfieCtx.strokeStyle = ovalColor;
            selfieCtx.lineWidth = 3;
            selfieCtx.setLineDash(ovalDash);
            selfieCtx.stroke();
            selfieCtx.restore();

            blurAnimId = requestAnimationFrame(drawBlurFrame);
        }

        if (selfieCanvas) {
            selfieCanvas.width  = 640;
            selfieCanvas.height = 480;
        }

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-selfie-status', I18n.get('ui_camera_unavailable'), 'danger');
                return;
            }
            attachStream(video, stream);
            if (selfieCtx) {
                drawBlurFrame();
            }
            setStatus('tagwiz-selfie-status', I18n.get('ui_loading_face_detection'), '');
            ensureFaceModel(function() {
                if (!faceModel) {
                    setStatus('tagwiz-selfie-status', I18n.get('ui_centre_your_face_in_the_oval_then_click_take_photo'), '');
                    return;
                }
                setStatus('tagwiz-selfie-status', I18n.get('ui_centre_your_face_in_the_oval'), '');
                startFaceScan();
            });
        });

        /**
         * Start periodic face detection and update the oval guide.
         */
        function startFaceScan() {
            faceScanActive = true;
            faceScanInterval = setInterval(function() {
                if (!faceScanActive || !faceModel || !video || video.readyState < 2) {
                    return;
                }
                faceModel.estimateFaces(video, false).then(function(faces) {
                    if (!faceScanActive) {
                        return;
                    }
                    updateFaceGuide(faces);
                });
            }, 500);
        }

        /**
         * Stop the face detection interval.
         */
        function stopFaceScan() {
            faceScanActive = false;
            if (faceScanInterval) {
                clearInterval(faceScanInterval);
                faceScanInterval = null;
            }
        }

        /**
         * Update the oval border and status message based on detected faces.
         *
         * @param {Array} faces BlazeFace detection results array.
         */
        function updateFaceGuide(faces) {
            if (captureActive) {
                return;
            }
            if (faces.length === 0) {
                ovalColor    = 'rgba(239,68,68,0.85)';
                ovalDash     = [];
                stableFrames = 0;
                setStatus('tagwiz-selfie-status', I18n.get('ui_no_face_detected_centre_your_face_in_the_oval'), '');
                return;
            }
            if (faces.length > 1) {
                ovalColor    = 'rgba(239,68,68,0.85)';
                ovalDash     = [];
                stableFrames = 0;
                setStatus('tagwiz-selfie-status', I18n.get('ui_multiple_faces_detected_ensure_only_you_are_visible'), '');
                return;
            }
            var face     = faces[0];
            var tl       = face.topLeft;
            var br       = face.bottomRight;
            var faceArea = (br[0] - tl[0]) * (br[1] - tl[1]);
            if (faceArea / (640 * 480) < 0.05) {
                ovalColor    = 'rgba(249,115,22,0.85)';
                ovalDash     = [8, 4];
                stableFrames = 0;
                setStatus('tagwiz-selfie-status', I18n.get('ui_move_closer_your_face_is_too_small'), '');
                return;
            }
            ovalColor = 'rgba(34,197,94,0.90)';
            ovalDash  = [10, 6];
            stableFrames++;
            setStatus('tagwiz-selfie-status', I18n.get('ui_face_detected_hold_still'), '');
            if (stableFrames >= 4) {
                startCountdown();
            }
        }

        /**
         * Cancel an in-progress auto-capture countdown.
         */
        function cancelCountdown() {
            captureActive = false;
            stableFrames  = 0;
            if (captureTimer) {
                clearInterval(captureTimer);
                captureTimer = null;
            }
            if (countdownEl) {
                countdownEl.style.display = 'none';
            }
        }

        /**
         * Begin the 3–2–1 auto-capture countdown.
         */
        function startCountdown() {
            if (captureActive) {
                return;
            }
            captureActive = true;
            var count = 3;
            if (countdownEl) {
                countdownEl.textContent = count;
                countdownEl.style.display = 'flex';
            }
            captureTimer = setInterval(function() {
                count--;
                if (count <= 0) {
                    clearInterval(captureTimer);
                    captureTimer = null;
                    if (countdownEl) {
                        countdownEl.style.display = 'none';
                    }
                    captureActive = false;
                    doCapture();
                } else {
                    if (countdownEl) {
                        countdownEl.textContent = count;
                    }
                }
            }, 1000);
        }

        /**
         * Validate a selfie canvas for brightness and sharpness.
         *
         * @param {CanvasRenderingContext2D} ctx Canvas context to sample.
         * @param {number} W Canvas width in pixels.
         * @param {number} H Canvas height in pixels.
         * @returns {{ok: boolean, reason: string}} Validation result.
         */
        function validateSelfieImage(ctx, W, H) {
            var imageData = ctx.getImageData(0, 0, W, H);
            var pixels    = imageData.data;
            var bi, lumaSum, lumaCount, avgLuma;
            var ly, lx, idx, g, gU, gD, gL, gR, lapSum, lapCount;

            lumaSum   = 0;
            lumaCount = 0;
            for (bi = 0; bi < pixels.length; bi += 40) {
                lumaSum += 0.299 * pixels[bi] + 0.587 * pixels[bi + 1] + 0.114 * pixels[bi + 2];
                lumaCount++;
            }
            avgLuma = lumaCount > 0 ? lumaSum / lumaCount : 128;
            if (avgLuma < 30) {
                return {ok: false, reason: I18n.get('ui_lighting_too_dark_move_to_a_brighter_spot')};
            }

            lapSum   = 0;
            lapCount = 0;
            for (ly = 1; ly < H - 1; ly += 4) {
                for (lx = 1; lx < W - 1; lx += 4) {
                    idx = (ly * W + lx) * 4;
                    g   = 0.299 * pixels[idx]           + 0.587 * pixels[idx + 1]           + 0.114 * pixels[idx + 2];
                    gU  = 0.299 * pixels[idx - W * 4]   + 0.587 * pixels[idx - W * 4 + 1]   + 0.114 * pixels[idx - W * 4 + 2];
                    gD  = 0.299 * pixels[idx + W * 4]   + 0.587 * pixels[idx + W * 4 + 1]   + 0.114 * pixels[idx + W * 4 + 2];
                    gL  = 0.299 * pixels[idx - 4]       + 0.587 * pixels[idx - 3]            + 0.114 * pixels[idx - 2];
                    gR  = 0.299 * pixels[idx + 4]       + 0.587 * pixels[idx + 5]            + 0.114 * pixels[idx + 6];
                    lapSum += Math.abs(4 * g - gU - gD - gL - gR);
                    lapCount++;
                }
            }
            if (lapCount > 0 && lapSum / lapCount < 2.0) {
                return {ok: false, reason: I18n.get('ui_image_too_blurry_hold_the_camera_steady')};
            }

            return {ok: true, reason: ''};
        }

        /**
         * Capture the selfie, validate it, and show preview or rejection message.
         */
        function doCapture() {
            stopFaceScan();
            if (blurAnimId) {
                cancelAnimationFrame(blurAnimId);
                blurAnimId = null;
            }
            var snapCanvas = document.createElement('canvas');
            snapCanvas.width  = 640;
            snapCanvas.height = 480;
            var snapCtx = snapCanvas.getContext('2d');
            snapCtx.drawImage(video, 0, 0, 640, 480);

            var validation = validateSelfieImage(snapCtx, 640, 480);
            if (!validation.ok) {
                setStatus('tagwiz-selfie-status',
                    I18n.get('wizard_photo_rejected', validation.reason), 'danger');
                ovalColor    = 'rgba(156,163,175,0.85)';
                ovalDash     = [10, 6];
                stableFrames = 0;
                if (selfieCtx && !blurAnimId) {
                    drawBlurFrame();
                }
                if (faceModel) {
                    startFaceScan();
                }
                return;
            }

            snapCanvas.toBlob(function(blob) {
                if (!blob) {
                    return;
                }
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                if (selfieCanvas) {
                    selfieCanvas.style.display = 'none';
                }
                captureBtn.style.display = 'none';
                retakeBtn.style.display  = 'inline-block';
                uploadSelfie(blob);
            }, 'image/jpeg', 0.88);
        }

        captureBtn.addEventListener('click', function() {
            cancelCountdown();
            doCapture();
        });

        retakeBtn.addEventListener('click', function() {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            if (selfieCanvas) {
                selfieCanvas.style.display = 'block';
            }
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display  = 'none';
            nextBtn.disabled  = true;
            done[2]           = false;
            ovalColor         = 'rgba(156,163,175,0.85)';
            ovalDash          = [10, 6];
            stableFrames      = 0;
            setStatus('tagwiz-selfie-status', I18n.get('ui_centre_your_face_in_the_oval'), '');
            if (selfieCtx && !blurAnimId) {
                drawBlurFrame();
            }
            if (faceModel) {
                startFaceScan();
            }
        });

        /**
         * Upload a selfie blob.
         *
         * @param {Blob} blob JPEG image blob.
         */
        function uploadSelfie(blob) {
            setStatus('tagwiz-selfie-status', I18n.get('ui_uploading_selfie'), '');
            var fd = new FormData();
            fd.append('selfie', blob, 'selfie.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.selfieurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-selfie-status', I18n.get('ui_selfie_saved_you_can_continue'), 'success');
                        nextBtn.disabled = false;
                        markDone(2);
                    } else {
                        setStatus('tagwiz-selfie-status', I18n.get('ui_upload_failed_please_retake'), 'danger');
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-selfie-status', I18n.get('ui_upload_error_please_retake'), 'danger');
                });
        }

        nextBtn.addEventListener('click', function() {
            if (!done[2]) {
                return;
            }
            stopFaceScan();
            showStep(3);
            initIdPhoto();
        });
    }

    // ── Step 3 — ID Photo ───────────────────────────────────────────────────

    /**
     * Initialise the ID-photo capture step.
     */
    function initIdPhoto() {
        var video        = el('tagwiz-id-video');
        var captureBtn   = el('tagwiz-id-capture');
        var retakeBtn    = el('tagwiz-id-retake');
        var previewWrap  = el('tagwiz-id-preview-wrap');
        var previewImg   = el('tagwiz-id-preview');
        var nextBtn      = el('tagwiz-id-next');
        var countdownEl  = el('tagwiz-id-countdown');
        var guideEl      = el('tagwiz-id-guide');
        var guideLabelEl = el('tagwiz-id-guide-label');

        var stableFrames  = 0;
        var captureTimer  = null;
        var captureActive = false;
        var scanInterval  = null;
        var scanActive    = false;

        /* Small canvas for quick per-frame brightness sampling */
        var sampleCanvas = document.createElement('canvas');
        sampleCanvas.width = 64;
        sampleCanvas.height = 48;
        var sampleCtx = sampleCanvas.getContext('2d');

        /* Hoisted loop variables for validateImage - avoids no-inner-declarations lint error */
        var bi, lumaSum, lumaCount, avgLuma, ly, lx, idx, g, gU, gD, gL, gR, lapSum, lapCount;

        /**
         * Update the guide border colour (via CSS custom property) and label text.
         *
         * @param {string} color Full CSS colour string, e.g. 'rgba(34,197,94,.85)'.
         * @param {string} text  Label text to display inside the guide box.
         */
        function setGuide(color, text) {
            if (guideEl)      { guideEl.style.setProperty('--id-guide-color', color); }
            if (guideLabelEl) { guideLabelEl.textContent = text; }
        }

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-id-status', I18n.get('ui_camera_unavailable'), 'danger');
                return;
            }
            attachStream(video, stream);
            setGuide('rgba(156,163,175,.80)', I18n.get('ui_place_your_id_inside_the_green_box'));
            setStatus('tagwiz-id-status', I18n.get('ui_centre_your_face_above_the_box_and_hold_your_id_inside_it'), '');
            ensureFaceModel(startScan);
        });

        /**
         * Start the periodic face-and-quality scan (every 600 ms).
         */
        function startScan() {
            scanActive = true;
            scanInterval = setInterval(function() {
                if (!scanActive || !video || video.readyState < 2) { return; }
                if (!faceModel) {
                    setStatus('tagwiz-id-status',
                        I18n.get('ui_centre_your_face_above_the_box_and_hold_your_id_inside_it'), '');
                    return;
                }
                faceModel.estimateFaces(video, false).then(function(faces) {
                    if (!scanActive) { return; }
                    updateGuide(faces);
                });
            }, 600);
        }

        /**
         * Stop the face scan and clear the interval.
         */
        function stopScan() {
            scanActive = false;
            if (scanInterval) {
                clearInterval(scanInterval);
                scanInterval = null;
            }
        }

        /**
         * Update guide colour and status based on the latest BlazeFace predictions.
         *
         * @param {Array} faces BlazeFace prediction array (returnTensors = false).
         */
        function updateGuide(faces) {
            if (captureActive) { return; }

            var videoH = video.videoHeight || 480;

            /* Quick brightness sample */
            sampleCtx.drawImage(video, 0, 0, 64, 48);
            var sData = sampleCtx.getImageData(0, 0, 64, 48).data;
            lumaSum = 0; lumaCount = 0;
            for (bi = 0; bi < sData.length; bi += 4) {
                lumaSum += 0.299 * sData[bi] + 0.587 * sData[bi + 1] + 0.114 * sData[bi + 2];
                lumaCount++;
            }
            avgLuma = lumaCount > 0 ? lumaSum / lumaCount : 128;

            if (avgLuma < 25) {
                cancelCountdown();
                stableFrames = 0;
                setGuide('rgba(249,115,22,.85)', I18n.get('ui_improve_room_lighting'));
                setStatus('tagwiz-id-status', I18n.get('ui_lighting_too_dark_improve_your_room_lighting'), 'warning');
                return;
            }

            if (!faces || faces.length === 0) {
                cancelCountdown();
                stableFrames = 0;
                setGuide('rgba(239,68,68,.85)', I18n.get('ui_show_your_face_above_the_id_box'));
                setStatus('tagwiz-id-status', I18n.get('ui_no_face_detected_show_your_face_alongside_your_id'), 'warning');
                return;
            }

            /* Face must be in the upper portion of the frame, not over the ID guide */
            var topLeft     = faces[0].topLeft;
            var bottomRight = faces[0].bottomRight;
            if (!topLeft || !bottomRight) { return; }
            var faceCy = ((topLeft[1] + bottomRight[1]) / 2) / videoH;
            if (faceCy > 0.65) {
                cancelCountdown();
                stableFrames = 0;
                setGuide('rgba(249,115,22,.85)', I18n.get('ui_move_your_face_higher_in_the_frame'));
                setStatus('tagwiz-id-status',
                    I18n.get('ui_move_your_face_to_the_upper_part_of_the_frame_and_hold_the_id_below'), 'warning');
                return;
            }

            /* All good */
            stableFrames++;
            setGuide('rgba(34,197,94,.85)', I18n.get('ui_hold_still'));
            setStatus('tagwiz-id-status', I18n.get('ui_face_and_id_detected_hold_steady'), 'success');

            if (stableFrames >= 3 && !captureActive) {
                startCountdown();
            }
        }

        /**
         * Cancel any in-progress countdown and reset the captureActive flag.
         */
        function cancelCountdown() {
            captureActive = false;
            if (captureTimer) { clearTimeout(captureTimer); captureTimer = null; }
            if (countdownEl)  { countdownEl.style.display = 'none'; }
        }

        /**
         * Begin the 3-second countdown then fire doCapture.
         */
        function startCountdown() {
            if (captureActive) { return; }
            captureActive = true;
            var count = 3;

            /**
             * Advance the countdown by one second.
             */
            function tick() {
                if (!captureActive) { return; }
                if (count <= 0) {
                    if (countdownEl) { countdownEl.style.display = 'none'; }
                    doCapture();
                    return;
                }
                if (countdownEl) {
                    countdownEl.textContent = count;
                    countdownEl.style.display = 'flex';
                }
                count--;
                captureTimer = setTimeout(tick, 1000);
            }
            tick();
        }

        /**
         * Validate the captured frame for brightness and sharpness.
         *
         * @param {CanvasRenderingContext2D} ctx Canvas 2D context with the snapshot.
         * @param {number} W Canvas width in pixels.
         * @param {number} H Canvas height in pixels.
         * @returns {{ok: boolean, reason: string}} Validation result.
         */
        function validateImage(ctx, W, H) {
            var pixels = ctx.getImageData(0, 0, W, H).data;
            lumaSum = 0; lumaCount = 0;
            for (bi = 0; bi < pixels.length; bi += 4) {
                lumaSum  += 0.299 * pixels[bi] + 0.587 * pixels[bi + 1] + 0.114 * pixels[bi + 2];
                lumaCount++;
            }
            avgLuma = lumaCount > 0 ? lumaSum / lumaCount : 128;
            if (avgLuma < 30) { return {ok: false, reason: I18n.get('ui_lighting_too_dark')}; }

            lapSum = 0; lapCount = 0;
            for (ly = 1; ly < H - 1; ly += 4) {
                for (lx = 1; lx < W - 1; lx += 4) {
                    idx = (ly * W + lx) * 4;
                    g  = 0.299 * pixels[idx]         + 0.587 * pixels[idx + 1]         + 0.114 * pixels[idx + 2];
                    gU = 0.299 * pixels[idx - W * 4] + 0.587 * pixels[idx - W * 4 + 1] + 0.114 * pixels[idx - W * 4 + 2];
                    gD = 0.299 * pixels[idx + W * 4] + 0.587 * pixels[idx + W * 4 + 1] + 0.114 * pixels[idx + W * 4 + 2];
                    gL = 0.299 * pixels[idx - 4]     + 0.587 * pixels[idx - 3]         + 0.114 * pixels[idx - 2];
                    gR = 0.299 * pixels[idx + 4]     + 0.587 * pixels[idx + 5]         + 0.114 * pixels[idx + 6];
                    lapSum += Math.abs(4 * g - gU - gD - gL - gR);
                    lapCount++;
                }
            }
            var lapMean = lapCount > 0 ? lapSum / lapCount : 0;
            if (lapMean < 2.0) { return {ok: false, reason: I18n.get('ui_please_hold_the_id_steady')}; }

            return {ok: true, reason: ''};
        }

        /**
         * Capture, validate, and upload the ID photo.
         */
        function doCapture() {
            stopScan();
            if (countdownEl) { countdownEl.style.display = 'none'; }

            var snapCanvas = document.createElement('canvas');
            snapCanvas.width = 640;
            snapCanvas.height = 480;
            var snapCtx = snapCanvas.getContext('2d');
            snapCtx.drawImage(video, 0, 0, 640, 480);

            var validation = validateImage(snapCtx, 640, 480);
            if (!validation.ok) {
                captureActive = false;
                stableFrames  = 0;
                setGuide('rgba(156,163,175,.80)', I18n.get('ui_place_your_id_inside_the_green_box'));
                setStatus('tagwiz-id-status',
                    I18n.get('wizard_id_unreadable', validation.reason), 'danger');
                ensureFaceModel(startScan);
                return;
            }

            snapCanvas.toBlob(function(blob) {
                if (!blob) {
                    captureActive = false;
                    stableFrames  = 0;
                    ensureFaceModel(startScan);
                    return;
                }
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                captureBtn.style.display  = 'none';
                retakeBtn.style.display   = 'inline-block';
                if (guideEl) { guideEl.style.display = 'none'; }
                if (countdownEl) { countdownEl.style.display = 'none'; }
                uploadIdPhoto(blob);
            }, 'image/jpeg', 0.88);
        }

        captureBtn.addEventListener('click', function() {
            stopScan();
            cancelCountdown();
            captureActive = false;
            stableFrames  = 0;
            doCapture();
        });

        retakeBtn.addEventListener('click', function() {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            captureBtn.style.display  = 'inline-block';
            retakeBtn.style.display   = 'none';
            nextBtn.disabled = true;
            done[3]       = false;
            captureActive = false;
            stableFrames  = 0;
            if (guideEl) {
                guideEl.style.display = '';
                guideEl.style.removeProperty('--id-guide-color');
            }
            setGuide('rgba(156,163,175,.80)', I18n.get('ui_place_your_id_inside_the_green_box'));
            setStatus('tagwiz-id-status', '', '');
            ensureFaceModel(startScan);
        });

        /**
         * Upload the captured ID-photo blob to the server.
         * Identity verification is done manually by the invigilator.
         *
         * @param {Blob} blob JPEG image blob.
         */
        function uploadIdPhoto(blob) {
            setStatus('tagwiz-id-status', I18n.get('ui_uploading_id_photo'), '');
            var fd = new FormData();
            fd.append('idphoto', blob, 'idphoto.jpg');
            fd.append('quizid',  cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.idphotourl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-id-status',
                            I18n.get('ui_id_photo_saved_your_invigilator_will_verify_your_identity'),
                            'success');
                        nextBtn.disabled = false;
                        markDone(3);
                    } else {
                        setStatus('tagwiz-id-status', I18n.get('ui_upload_failed_please_retake'), 'danger');
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-id-status', I18n.get('ui_upload_error_please_retake'), 'danger');
                });
        }

        nextBtn.addEventListener('click', function() {
            if (!done[3]) { return; }
            stopScan();
            showStep(4);
        });
    }

    // ── Step 4 — Screen Share ────────────────────────────────────────────────

    /**
     * Initialise the final readiness step.
     *
     * Screen sharing is requested on the real attempt page by monitor.js. Asking
     * here would trigger a second browser picker immediately after Moodle opens
     * the quiz attempt, so this step only prepares the student for that prompt.
     */
    function initScreenShare() {
        var shareBtn    = el('tagwiz-ss-share');
        var checklistEl = el('tagwiz-checklist');
        var chkScanEl   = el('tagwiz-chk-scan');
        var startBtn    = el('tagwiz-start-quiz');
        var multimonEl  = el('tagwiz-ss-multimon');
        var beforeEl    = el('tagwiz-ss-before');

        if (shareBtn) {
            shareBtn.style.display = 'none';
        }
        if (multimonEl) {
            multimonEl.style.display = 'none';
        }
        if (beforeEl) {
            beforeEl.style.display = 'block';
        }
        if (chkScanEl && !(cfg && cfg.enableRoomScan !== 0)) {
            chkScanEl.style.display = 'none';
        }
        if (checklistEl) {
            checklistEl.style.display = 'block';
        }
        markDone(4);
        setStatus('tagwiz-ss-status',
            I18n.get('ui_after_you_start_choose_entire_screen_when_your_browser_asks'),
            'success');

        if (startBtn) {
            startBtn.addEventListener('click', function() {
                if (!done[4]) { return; }
                startBtn.disabled = true;
                startBtn.textContent = I18n.get('ui_starting');

                var moodleConsent = el('id_tagproctoring_consent');
                if (moodleConsent) { moodleConsent.checked = true; }

                var completeField = el('id_tagproctoring_complete');
                if (completeField) { completeField.value = '1'; }

                try {
                    sessionStorage.setItem('tagproctoring_reset_timer', cfg.quizid || '1');
                } catch (e) { /* sessionStorage blocked */ }

                var fsTarget = document.documentElement;
                var fsReq = fsTarget.requestFullscreen ||
                    fsTarget.webkitRequestFullscreen ||
                    fsTarget.mozRequestFullScreen;
                var fsPromise = fsReq ? fsReq.call(fsTarget) : Promise.resolve();
                Promise.resolve(fsPromise)
                    .catch(function() { /* fullscreen denied - proceed */ })
                    .then(function() { submitMoodleForm(); });
            });
        }
    }

    /**
     * Submit the Moodle preflight form via the first available submit button or form.submit().
     */
    function submitMoodleForm() {
        var form = document.getElementById('mod_quiz_preflight_form');
        if (!form) {
            form = document.querySelector('form[action*="startattempt.php"]');
        }
        if (form) {
            var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                form.requestSubmit(submitButton);
                return;
            }
            form.submit();
            return;
        }
        // Fallback: click any visible submit button.
        var btn = document.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
            btn.click();
        }
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    var wizardStarted = false;

    /**
     * Initialise the pre-exam wizard.
     *
     * @returns {void}
     */
    function initWizard() {
        if (wizardStarted) {
            return;
        }
        wizardStarted = true;

        var wrap = el('tagwiz-wrap');
        if (wrap) {
            wrap.style.display = '';
        }
        showStep(0);
        initRules();
        initScreenShare(); // attach listener upfront; step panel is hidden until reached

        // Preload TF.js in the background so it is ready when the room-scan step starts.
        if (cfg && cfg.enableRoomScan !== 0) {
            var tfUrl = (cfg && cfg.vendorBase)
                ? cfg.vendorBase + '/js/tf.min.js'
                : 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js';
            loadScript(tfUrl, function() {});
        }
    }

    /**
     * Find Moodle's quiz start-attempt control on the quiz view page.
     *
     * @returns {HTMLElement|null} Attempt trigger element, or null.
     */
    function findAttemptTrigger() {
        var selectors = [
            'form[action*="/mod/quiz/startattempt.php"] button[type="submit"]',
            'form[action*="/mod/quiz/startattempt.php"] input[type="submit"]',
            'form[action*="startattempt.php"] button[type="submit"]',
            'form[action*="startattempt.php"] input[type="submit"]',
            'a[href*="/mod/quiz/startattempt.php"]',
            'a[href*="startattempt.php"]'
        ];
        var trigger = document.querySelector(selectors.join(','));

        if (trigger) {
            return trigger;
        }

        var wrap = el('tagwiz-wrap');
        if (!wrap) {
            return null;
        }

        var form = wrap.closest('form');
        if (form && form.getAttribute('action') &&
                form.getAttribute('action').indexOf('startattempt.php') !== -1) {
            return form.querySelector('button[type="submit"], input[type="submit"]');
        }

        return null;
    }

    return {
        /**
         * Initialise the recorder module with server-provided config.
         *
         * @param {Object} config Configuration: quizid, uploadurl, selfieurl, idphotourl.
         */
        init: function(config) {
            return I18n.load().then(function() {
            cfg = config;
            Str.get_strings([
                {key: 'error_media_unavailable', component: 'quizaccess_tagproctoring'},
            ]).then(function(strs) {
                ES.media_unavailable = strs[0];
            });

            /**
             * @returns {void}
             */
            function setup() {
                var wrap = document.getElementById('tagwiz-wrap');
                var modal = wrap ? wrap.closest('.modal') : null;
                var attemptTrigger = findAttemptTrigger();

                if (modal && !modal.classList.contains('show')) {
                    // Preflight form is inside a hidden Bootstrap modal (pre-rendered on the
                    // quiz view page). Only start the wizard after the student clicks
                    // "Attempt quiz" and the modal is fully open.
                    modal.addEventListener('shown.bs.modal', function onShown() {
                        modal.removeEventListener('shown.bs.modal', onShown);
                        initWizard();
                    });
                } else if (attemptTrigger) {
                    // Preflight markup is on the quiz view page. Keep it hidden until Moodle's
                    // "Attempt quiz" control is used; if Moodle navigates to a separate
                    // preflight page, the wizard will initialise on that page instead.
                    attemptTrigger.addEventListener('click', function onAttemptClick() {
                        attemptTrigger.removeEventListener('click', onAttemptClick);
                        window.setTimeout(initWizard, 0);
                    });
                } else {
                    // Standalone preflight form page, or modal already open.
                    initWizard();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setup);
            } else {
                setup();
            }
            });
        }
    };
});
