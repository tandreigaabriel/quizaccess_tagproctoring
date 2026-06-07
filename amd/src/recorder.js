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

define("quizaccess_tagproctoring/recorder", [], function() {
    'use strict';

    var cfg = {};

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
        navigator.mediaDevices.getUserMedia({video: true, audio: true})
            .then(function(stream) {
                webcamStream = stream;
                callback(stream);
            })
            .catch(function(err) {
                callback(null, err);
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
     * Initialise the rules-agreement step.
     */
    function initRules() {
        var checkbox   = el('tagwiz-rules-agree');
        var nextBtn    = el('tagwiz-rules-next');
        var scrollBox  = el('tagwiz-rules-scroll');
        var scrollHint = el('tagwiz-rules-scroll-hint');
        var confirmed  = el('tagwiz-rules-confirmed');
        if (!checkbox || !nextBtn) {
            return;
        }

        /**
         * Unlock the agreement checkbox once the student has scrolled to the bottom.
         */
        function enableCheckbox() {
            checkbox.disabled = false;
            if (scrollHint) {
                scrollHint.style.display = 'none';
            }
        }

        if (scrollBox) {
            // If the content already fits (no overflow), enable immediately.
            if (scrollBox.scrollHeight <= scrollBox.clientHeight + 5) {
                enableCheckbox();
            } else {
                scrollBox.addEventListener('scroll', function() {
                    if (scrollBox.scrollTop + scrollBox.clientHeight >= scrollBox.scrollHeight - 5) {
                        enableCheckbox();
                    }
                });
            }
        } else {
            enableCheckbox();
        }

        checkbox.addEventListener('change', function() {
            nextBtn.disabled = !checkbox.checked;
            if (confirmed) {
                confirmed.style.display = checkbox.checked ? 'block' : 'none';
            }
        });

        nextBtn.addEventListener('click', function() {
            if (!checkbox.checked) {
                return;
            }
            markDone(0);
            if (cfg && cfg.enableRoomScan === 0) {
                // Room scan disabled by admin — skip step 1.
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

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-rs-status',
                    'Camera access denied — please allow camera/microphone and reload.', 'danger');
                startBtn.disabled = true;
                return;
            }
            attachStream(video, stream);
            startAI(video);

            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = function(e) {
                if (e.data && e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = function() {
                var blob = new Blob(recordedChunks, {type: 'video/webm'});
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
                    timerEl.textContent = 'Starting in ' + countdown + '\u2026';

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
                            startBtn.textContent = '\u23fa Stop Recording';
                            startBtn.classList.remove('tagwiz-btn--record');
                            startBtn.classList.add('tagwiz-btn--danger');
                            statusEl.textContent =
                                'Recording\u2026 slowly pan 360\u00b0 around the room and desk.';
                            timerEl.textContent = '0s / ' + MAX_SEC + 's';

                            tickInterval = setInterval(function() {
                                elapsed++;
                                timerEl.textContent = elapsed + 's / ' + MAX_SEC + 's';
                                if (elapsed >= MAX_SEC) {
                                    clearInterval(tickInterval);
                                    stopRecording();
                                }
                            }, 1000);
                        } else {
                            timerEl.textContent = 'Starting in ' + countdown + '\u2026';
                        }
                    }, 1000);

                } else if (mediaRecorder.state === 'recording') {
                    if (elapsed < MIN_SEC) {
                        setStatus('tagwiz-rs-status',
                            'Minimum ' + MIN_SEC + 's required — currently at ' + elapsed + 's. Keep scanning.',
                            'warning');
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
            startBtn.textContent = 'Uploading\u2026';
            timerEl.textContent = '';
            mediaRecorder.stop();
        }

        /**
         * Upload the recorded room-scan video blob.
         *
         * @param {Blob} blob Recorded video blob.
         */
        function uploadRoomScan(blob) {
            statusEl.textContent = 'Uploading room scan\u2026';
            var fd = new FormData();
            fd.append('video', blob, 'roomscan.webm');
            fd.append('scan_objects', JSON.stringify(detectedObjects));
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.uploadurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        if (data.suspicious) {
                            var flagged = Array.isArray(data.flagged) && data.flagged.length
                                ? data.flagged.join(', ')
                                : 'prohibited item';
                            setStatus('tagwiz-rs-status',
                                '\u26a0\ufe0f Detected: ' + flagged + '. ' +
                                'Please remove these from your desk and redo the room scan.',
                                'danger');
                            startBtn.disabled = false;
                            startBtn.textContent = '\u21bb Redo Room Scan';
                            startBtn.classList.remove('tagwiz-btn--danger');
                            startBtn.classList.add('tagwiz-btn--record');
                            started = false;
                        } else {
                            successEl.style.display = 'block';
                            statusEl.textContent = '';
                            startBtn.disabled = true;
                            startBtn.textContent = '\u2705 Room scan complete';
                            startBtn.classList.remove('tagwiz-btn--danger');
                            startBtn.classList.add('tagwiz-btn--record');
                            nextBtn.disabled = false;
                            markDone(1);
                        }
                    } else {
                        setStatus('tagwiz-rs-status',
                            'Upload failed: ' + (data.message || 'unknown error') + '. Retry.', 'danger');
                        markFailed(1);
                        startBtn.disabled = false;
                        startBtn.textContent = '\u21bb Retry Recording';
                        startBtn.classList.remove('tagwiz-btn--danger');
                        startBtn.classList.add('tagwiz-btn--record');
                        started = false;
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-rs-status', 'Upload error — please retry.', 'danger');
                    markFailed(1);
                    startBtn.disabled = false;
                    startBtn.textContent = '\u21bb Retry Recording';
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
                setStatus('tagwiz-selfie-status', 'Camera unavailable.', 'danger');
                return;
            }
            attachStream(video, stream);
            if (selfieCtx) {
                drawBlurFrame();
            }
            setStatus('tagwiz-selfie-status', 'Loading face detection…', '');
            ensureFaceModel(function() {
                if (!faceModel) {
                    setStatus('tagwiz-selfie-status', 'Centre your face in the oval, then click Take Photo.', '');
                    return;
                }
                setStatus('tagwiz-selfie-status', 'Centre your face in the oval.', '');
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
                setStatus('tagwiz-selfie-status', 'No face detected — centre your face in the oval.', '');
                return;
            }
            if (faces.length > 1) {
                ovalColor    = 'rgba(239,68,68,0.85)';
                ovalDash     = [];
                stableFrames = 0;
                setStatus('tagwiz-selfie-status', 'Multiple faces detected — ensure only you are visible.', '');
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
                setStatus('tagwiz-selfie-status', 'Move closer — your face is too small.', '');
                return;
            }
            ovalColor = 'rgba(34,197,94,0.90)';
            ovalDash  = [10, 6];
            stableFrames++;
            setStatus('tagwiz-selfie-status', '✓ Face detected — hold still…', '');
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
                return {ok: false, reason: 'Lighting too dark — move to a brighter spot'};
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
                return {ok: false, reason: 'Image too blurry — hold the camera steady'};
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
                    'Photo rejected: ' + validation.reason + ' — please try again.', 'danger');
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
            setStatus('tagwiz-selfie-status', 'Centre your face in the oval.', '');
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
            setStatus('tagwiz-selfie-status', 'Uploading selfie…', '');
            var fd = new FormData();
            fd.append('selfie', blob, 'selfie.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.selfieurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-selfie-status', '✅ Selfie saved.', 'success');
                        nextBtn.disabled = false;
                        markDone(2);
                    } else {
                        setStatus('tagwiz-selfie-status', 'Upload failed — please retake.', 'danger');
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-selfie-status', 'Upload error — please retake.', 'danger');
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
                setStatus('tagwiz-id-status', 'Camera unavailable.', 'danger');
                return;
            }
            attachStream(video, stream);
            setGuide('rgba(156,163,175,.80)', 'Place your ID inside the green box');
            setStatus('tagwiz-id-status', 'Centre your face above the box and hold your ID inside it.', '');
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
                        'Centre your face above the box and hold your ID inside it.', '');
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
                setGuide('rgba(249,115,22,.85)', 'Improve room lighting');
                setStatus('tagwiz-id-status', 'Lighting too dark - improve your room lighting.', 'warning');
                return;
            }

            if (!faces || faces.length === 0) {
                cancelCountdown();
                stableFrames = 0;
                setGuide('rgba(239,68,68,.85)', 'Show your face above the ID box');
                setStatus('tagwiz-id-status', 'No face detected - show your face alongside your ID.', 'warning');
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
                setGuide('rgba(249,115,22,.85)', 'Move your face higher in the frame');
                setStatus('tagwiz-id-status',
                    'Move your face to the upper part of the frame and hold the ID below.', 'warning');
                return;
            }

            /* All good */
            stableFrames++;
            setGuide('rgba(34,197,94,.85)', '\u2714 Hold still...');
            setStatus('tagwiz-id-status', '\u2714 Face and ID detected - hold steady...', 'success');

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
            if (avgLuma < 30) { return {ok: false, reason: 'lighting too dark'}; }

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
            if (lapMean < 2.0) { return {ok: false, reason: 'please hold the ID steady'}; }

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
                setGuide('rgba(156,163,175,.80)', 'Place your ID inside the green box');
                setStatus('tagwiz-id-status',
                    'ID not readable - ' + validation.reason + '. Please try again.', 'danger');
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
            setGuide('rgba(156,163,175,.80)', 'Place your ID inside the green box');
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
            setStatus('tagwiz-id-status', 'Uploading ID photo\u2026', '');
            var fd = new FormData();
            fd.append('idphoto', blob, 'idphoto.jpg');
            fd.append('quizid',  cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.idphotourl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-id-status',
                            '\u2705 ID photo saved. Your invigilator will verify your identity.',
                            'success');
                        nextBtn.disabled = false;
                        markDone(3);
                    } else {
                        setStatus('tagwiz-id-status', 'Upload failed \u2014 please retake.', 'danger');
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-id-status', 'Upload error \u2014 please retake.', 'danger');
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
     * Initialise the screen-share verification step.
     */
    function initScreenShare() {
        var shareBtn    = el('tagwiz-ss-share');
        var checklistEl = el('tagwiz-checklist');
        var chkScanEl   = el('tagwiz-chk-scan');
        var startBtn    = el('tagwiz-start-quiz');
        var multimonEl  = el('tagwiz-ss-multimon');
        var recheckBtn  = el('tagwiz-ss-recheck');
        var beforeEl    = el('tagwiz-ss-before');
        if (!shareBtn) { return; }

        /**
         * Request display media and validate the surface type and monitor count.
         */
        function attemptShare() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                setStatus('tagwiz-ss-status',
                    'Screen sharing is not supported in this browser. Use Chrome or Edge.', 'danger');
                return;
            }

            navigator.mediaDevices.getDisplayMedia({video: {displaySurface: 'monitor'}, audio: false})
                .then(function(stream) {
                    var track    = stream.getVideoTracks()[0];
                    var settings = track ? track.getSettings() : {};
                    stream.getTracks().forEach(function(t) { t.stop(); });

                    if (settings.displaySurface && settings.displaySurface !== 'monitor') {
                        setStatus('tagwiz-ss-status',
                            '\u26a0\ufe0f You chose "'  + settings.displaySurface +
                            '" \u2014 you must select "Entire Screen". Click Share Screen and try again.',
                            'danger');
                        return;
                    }

                    if (window.screen && window.screen.isExtended === true) {
                        if (multimonEl) { multimonEl.style.display = 'block'; }
                        if (beforeEl)   { beforeEl.style.display   = 'none';  }
                        setStatus('tagwiz-ss-status', '', '');
                        return;
                    }

                    if (multimonEl) { multimonEl.style.display = 'none'; }
                    if (beforeEl)   { beforeEl.style.display   = 'none'; }
                    setStatus('tagwiz-ss-status', '', '');
                    shareBtn.style.display = 'none';
                    markDone(4);
                    if (chkScanEl && !(cfg && cfg.enableRoomScan !== 0)) {
                        chkScanEl.style.display = 'none';
                    }
                    if (checklistEl) { checklistEl.style.display = 'block'; }
                })
                .catch(function(err) {
                    if (err.name === 'NotAllowedError') {
                        setStatus('tagwiz-ss-status',
                            'Screen share was cancelled. Click Share Screen and allow access.',
                            'warning');
                    } else {
                        setStatus('tagwiz-ss-status', 'Screen sharing error: ' + err.message, 'danger');
                    }
                });
        }

        shareBtn.addEventListener('click', attemptShare);

        if (recheckBtn) {
            recheckBtn.addEventListener('click', function() {
                if (!(window.screen && window.screen.isExtended === true)) {
                    if (multimonEl) { multimonEl.style.display = 'none'; }
                    if (beforeEl)   { beforeEl.style.display   = 'block'; }
                    setStatus('tagwiz-ss-status',
                        '\u2705 Single screen detected. Click Share Screen to continue.', 'success');
                } else {
                    setStatus('tagwiz-ss-status',
                        'Multiple screens still detected. Disconnect or mirror your displays first.',
                        'warning');
                }
            });
        }

        if (startBtn) {
            startBtn.addEventListener('click', function() {
                if (!done[4]) { return; }
                startBtn.disabled = true;
                startBtn.textContent = 'Starting\u2026';

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
        var btn = el('id_submitbutton');
        if (btn) {
            btn.click();
            return;
        }
        var anyBtn = document.querySelector('button[type="submit"], input[type="submit"]');
        if (anyBtn) {
            anyBtn.click();
            return;
        }
        var form = document.querySelector('form[method="post"]');
        if (form) {
            form.submit();
        }
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    /**
     * Initialise the pre-exam wizard.
     */
    function initWizard() {
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

    return {
        /**
         * Initialise the recorder module with server-provided config.
         *
         * @param {Object} config Configuration: quizid, uploadurl, selfieurl, idphotourl.
         */
        init: function(config) {
            cfg = config;
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWizard);
            } else {
                initWizard();
            }
        }
    };
});
