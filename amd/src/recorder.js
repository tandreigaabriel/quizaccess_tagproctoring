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
 * @copyright  2026 Andrei Toma <https://www.tagwebdesign.co.uk>
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

    // Face-to-ID verification state.
    var selfieBlobRef = null;  // saved when selfie is captured
    var faceApiReady = false;  // true once face-api.js models are loaded

    // CDN URLs for face-api.js (vladmandic fork — supports TF.js 4.x, drop-in API).
    var FACE_API_JS = "https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/dist/face-api.js";
    var FACE_API_MODELS = "https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/model/";

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
            ind.classList.add('tagwiz-ind--done');
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
        var TFJS = "https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js";
        var COCO = "https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js";
        var FACE = "https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js";

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
                                        if (o.score < 0.35) {
                                            return;
                                        }
                                        var cls = o.class;
                                        if (["cell phone", "book", "laptop", "tablet", "remote"].includes(cls)) {
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
                                        if (faces.length === 0) {
                                            detectedObjects["no_face"] =
                                                (detectedObjects["no_face"] || 0) + 1;
                                        }
                                        if (faces.length > 1) {
                                            detectedObjects["multiple_faces"] =
                                                (detectedObjects["multiple_faces"] || 0) + 1;
                                        }
                                        // Face direction: if nose is offset from eye midpoint the face is turned.
                                        if (faces.length === 1) {
                                            var lm = faces[0].landmarks;
                                            if (lm && lm.length >= 3) {
                                                var rEyeX   = lm[0][0];
                                                var lEyeX   = lm[1][0];
                                                var noseTipX = lm[2][0];
                                                var eyeMidX  = (rEyeX + lEyeX) / 2;
                                                var eyeDist  = Math.abs(lEyeX - rEyeX);
                                                if (eyeDist > 0 &&
                                                        Math.abs(noseTipX - eyeMidX) / eyeDist > 0.25) {
                                                    detectedObjects["face_turned"] =
                                                        (detectedObjects["face_turned"] || 0) + 1;
                                                }
                                            }
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

    // ── Face-to-ID Verification ──────────────────────────────────────────────

    /**
     * Resolve a Blob to an HTMLImageElement (needed by face-api.js).
     *
     * @param {Blob} blob Image blob.
     * @returns {Promise<HTMLImageElement>}
     */
    function blobToImage(blob) {
        return new Promise(function(resolve) {
            var img = new Image();
            img.onload = function() { resolve(img); };
            img.src = URL.createObjectURL(blob);
        });
    }

    /**
     * Load face-api.js and its three required models from jsDelivr CDN.
     * Models: SSD MobileNet v1 (detection) + 68-point landmarks + face recognition.
     * Called once in the background during the room-scan step.
     */
    function loadFaceApi() {
        // TF.js must already be present (loaded by startAI earlier).
        if (!window.tf) {
            return;
        }
        loadScript(FACE_API_JS, function(err) {
            if (err || !window.faceapi) {
                return;
            }
            Promise.all([
                window.faceapi.nets.ssdMobilenetv1.loadFromUri(FACE_API_MODELS),
                window.faceapi.nets.faceLandmark68Net.loadFromUri(FACE_API_MODELS),
                window.faceapi.nets.faceRecognitionNet.loadFromUri(FACE_API_MODELS)
            ]).then(function() {
                faceApiReady = true;
            }).catch(function() {
                // Models failed to load — face matching will be skipped gracefully.
            });
        });
    }

    /**
     * Compare the stored selfie against a freshly captured ID-photo blob.
     * Resolves with a match result object: { verified: 1|0|-1, distance: number }.
     *  1 = faces match (same person)
     *  0 = faces detected but do not match
     * -1 = could not detect a face in one or both images (model unavailable, bad image, etc.)
     *
     * @param {Blob} idBlob JPEG blob of the ID-photo frame.
     * @returns {Promise<{verified: number, distance: number}>}
     */
    function runFaceMatch(idBlob) {
        if (!faceApiReady || !selfieBlobRef) {
            return Promise.resolve({verified: -1, distance: -1});
        }

        return Promise.all([blobToImage(selfieBlobRef), blobToImage(idBlob)])
            .then(function(imgs) {
                var opts = new window.faceapi.SsdMobilenetv1Options({minConfidence: 0.2});
                return Promise.all([
                    window.faceapi.detectSingleFace(imgs[0], opts).withFaceLandmarks().withFaceDescriptor(),
                    window.faceapi.detectSingleFace(imgs[1], opts).withFaceLandmarks().withFaceDescriptor()
                ]);
            })
            .then(function(detections) {
                var selfieD = detections[0];
                var idD = detections[1];
                if (!selfieD || !idD) {
                    return {verified: -1, distance: -1};
                }
                var dist = window.faceapi.euclideanDistance(selfieD.descriptor, idD.descriptor);
                return {verified: dist < 0.6 ? 1 : 0, distance: dist};
            })
            .catch(function() {
                return {verified: -1, distance: -1};
            });
    }

    // ── Step 0 — Rules ──────────────────────────────────────────────────────

    /**
     * Initialise the rules-agreement step.
     */
    function initRules() {
        var checkbox = el('tagwiz-rules-agree');
        var nextBtn  = el('tagwiz-rules-next');
        if (!checkbox || !nextBtn) {
            return;
        }

        checkbox.addEventListener('change', function() {
            nextBtn.disabled = !checkbox.checked;
        });

        nextBtn.addEventListener('click', function() {
            if (!checkbox.checked) {
                return;
            }
            markDone(0);
            showStep(1);
            initRoomScan();
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

        var MIN_SEC = 30;
        var MAX_SEC = 45;
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
                            nextBtn.disabled = false;
                            markDone(1);
                        }
                    } else {
                        setStatus('tagwiz-rs-status',
                            'Upload failed: ' + (data.message || 'unknown error') + '. Retry.', 'danger');
                        startBtn.disabled = false;
                        startBtn.textContent = '\u21bb Retry Recording';
                        startBtn.classList.remove('tagwiz-btn--danger');
                        startBtn.classList.add('tagwiz-btn--record');
                        started = false;
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-rs-status', 'Upload error — please retry.', 'danger');
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

    // ── Step 2 — Selfie ─────────────────────────────────────────────────────

    /**
     * Initialise the selfie capture step.
     */
    function initSelfie() {
        var video       = el('tagwiz-selfie-video');
        var captureBtn  = el('tagwiz-selfie-capture');
        var retakeBtn   = el('tagwiz-selfie-retake');
        var previewWrap = el('tagwiz-selfie-preview-wrap');
        var previewImg  = el('tagwiz-selfie-preview');
        var nextBtn     = el('tagwiz-selfie-next');

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-selfie-status', 'Camera unavailable.', 'danger');
                return;
            }
            attachStream(video, stream);
        });

        captureBtn.addEventListener('click', function() {
            var canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            canvas.getContext('2d').drawImage(video, 0, 0, 640, 480);
            canvas.toBlob(function(blob) {
                if (!blob) {
                    return;
                }
                selfieBlobRef = blob; // save for face-to-ID comparison in step 3
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-block';
                uploadSelfie(blob);
            }, 'image/jpeg', 0.88);
        });

        retakeBtn.addEventListener('click', function() {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            nextBtn.disabled = true;
            done[2] = false;
            selfieBlobRef = null;
            setStatus('tagwiz-selfie-status', '', '');
        });

        /**
         * Upload a selfie blob.
         *
         * @param {Blob} blob JPEG image blob.
         */
        function uploadSelfie(blob) {
            setStatus('tagwiz-selfie-status', 'Uploading selfie\u2026', '');
            var fd = new FormData();
            fd.append('selfie', blob, 'selfie.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.selfieurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-selfie-status', '\u2705 Selfie saved.', 'success');
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
            showStep(3);
            initIdPhoto();
        });
    }

    // ── Step 3 — ID Photo ───────────────────────────────────────────────────

    /**
     * Initialise the ID-photo capture step.
     */
    function initIdPhoto() {
        var video       = el('tagwiz-id-video');
        var captureBtn  = el('tagwiz-id-capture');
        var retakeBtn   = el('tagwiz-id-retake');
        var previewWrap = el('tagwiz-id-preview-wrap');
        var previewImg  = el('tagwiz-id-preview');
        var nextBtn     = el('tagwiz-id-next');

        ensureWebcam(function(stream) {
            if (!stream) {
                setStatus('tagwiz-id-status', 'Camera unavailable.', 'danger');
                return;
            }
            attachStream(video, stream);
        });

        captureBtn.addEventListener('click', function() {
            var canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            canvas.getContext('2d').drawImage(video, 0, 0, 640, 480);
            canvas.toBlob(function(blob) {
                if (!blob) {
                    return;
                }
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-block';

                // Run face-to-ID comparison before uploading.
                setStatus('tagwiz-id-status', faceApiReady ? 'Verifying identity\u2026' : 'Uploading ID photo\u2026', '');
                runFaceMatch(blob).then(function(match) {
                    uploadIdPhoto(blob, match);
                });
            }, 'image/jpeg', 0.88);
        });

        retakeBtn.addEventListener('click', function() {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            nextBtn.disabled = true;
            done[3] = false;
            setStatus('tagwiz-id-status', '', '');
        });

        /**
         * Upload an ID-photo blob together with the face-match result.
         *
         * @param {Blob}   blob  JPEG image blob.
         * @param {{verified: number, distance: number}} match Face-match result from runFaceMatch().
         */
        function uploadIdPhoto(blob, match) {
            var fd = new FormData();
            fd.append('idphoto', blob, 'idphoto.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);
            fd.append('face_match_verified', match.verified);
            fd.append('face_match_distance', match.distance >= 0 ? match.distance.toFixed(3) : '-1');

            fetch(cfg.idphotourl, {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.status === 'ok') {
                        if (match.verified === 1) {
                            // Clear match — allow through.
                            setStatus('tagwiz-id-status',
                                '\u2705 Identity verified — face matches your ID.', 'success');
                            nextBtn.disabled = false;
                            markDone(3);
                        } else if (match.verified === 0) {
                            // Clear mismatch — block and force retake.
                            setStatus('tagwiz-id-status',
                                '\u274c Face does not match your ID photo. ' +
                                'Make sure your face AND your ID card are both clearly visible, ' +
                                'then retake.',
                                'danger');
                            // Keep Next disabled; student must retake.
                            retakeBtn.click();
                        } else {
                            // Unknown (-1): models not ready or no face detected in ID image.
                            // Allow through but flag for manual review.
                            setStatus('tagwiz-id-status',
                                '\u26a0\ufe0f Could not verify your face automatically. ' +
                                'Make sure your ID card is held up clearly and your face is visible. ' +
                                'Your invigilator will review this manually. Click Next to continue.',
                                'warning');
                            nextBtn.disabled = false;
                            markDone(3);
                        }
                    } else {
                        setStatus('tagwiz-id-status', 'Upload failed — please retake.', 'danger');
                    }
                })
                .catch(function() {
                    setStatus('tagwiz-id-status', 'Upload error — please retake.', 'danger');
                });
        }

        nextBtn.addEventListener('click', function() {
            if (!done[3]) {
                return;
            }
            showStep(4);
        });
    }

    // ── Step 4 — Screen Share ────────────────────────────────────────────────

    /**
     * Initialise the screen-share verification step.
     */
    function initScreenShare() {
        var shareBtn  = el('tagwiz-ss-share');
        var successEl = el('tagwiz-ss-success');
        var startBtn  = el('tagwiz-start-quiz');
        if (!shareBtn) {
            return;
        }

        shareBtn.addEventListener('click', function() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                setStatus('tagwiz-ss-status',
                    'Screen sharing is not supported in this browser. Use Chrome or Edge.', 'danger');
                return;
            }

            navigator.mediaDevices.getDisplayMedia({video: {displaySurface: 'monitor'}, audio: false})
                .then(function(stream) {
                    var track    = stream.getVideoTracks()[0];
                    var settings = track ? track.getSettings() : {};

                    // Stop the stream immediately — we only needed verification.
                    stream.getTracks().forEach(function(t) { t.stop(); });

                    // Validate that the user shared the entire screen, not a tab/window.
                    if (settings.displaySurface && settings.displaySurface !== 'monitor') {
                        setStatus('tagwiz-ss-status',
                            '\u26a0\ufe0f You selected a ' + settings.displaySurface +
                            ' \u2014 you must choose "Entire Screen". Please try again.',
                            'danger');
                        return;
                    }

                    // Block if a second monitor is detected.
                    if (window.screen && window.screen.isExtended === true) {
                        setStatus('tagwiz-ss-status',
                            '\u26a0\ufe0f Multiple monitors detected. ' +
                            'Please disconnect your secondary screen and try again.',
                            'danger');
                        return;
                    }

                    setStatus('tagwiz-ss-status', '', '');
                    successEl.style.display = 'block';
                    shareBtn.style.display = 'none';
                    markDone(4);
                    startBtn.style.display = 'inline-block';
                })
                .catch(function(err) {
                    if (err.name === 'NotAllowedError') {
                        setStatus('tagwiz-ss-status',
                            'Screen share was cancelled. Please click Share and allow access.',
                            'warning');
                    } else {
                        setStatus('tagwiz-ss-status', 'Screen sharing error: ' + err.message, 'danger');
                    }
                });
        });

        if (startBtn) {
            startBtn.addEventListener('click', function() {
                if (!done[4]) {
                    return;
                }
                startBtn.disabled = true;
                startBtn.textContent = 'Starting\u2026';

                // Mark Moodle's hidden consent field as checked.
                var moodleConsent = el('id_tagproctoring_consent');
                if (moodleConsent) {
                    moodleConsent.checked = true;
                }

                // Mark wizard complete.
                var completeField = el('id_tagproctoring_complete');
                if (completeField) {
                    completeField.value = '1';
                }

                // Signal monitor.js to reset the quiz timer once the attempt page loads.
                // We store the quizid so monitor can pass it as cmid context.
                try {
                    sessionStorage.setItem('tagproctoring_reset_timer', cfg.quizid || '1');
                } catch (e) { /* sessionStorage blocked — timer reset will be skipped */ }

                // Enter fullscreen then submit.
                var fsTarget = document.documentElement;
                var fsReq = fsTarget.requestFullscreen ||
                    fsTarget.webkitRequestFullscreen ||
                    fsTarget.mozRequestFullScreen;

                var fsPromise = fsReq ? fsReq.call(fsTarget) : Promise.resolve();

                Promise.resolve(fsPromise)
                    .catch(function() { /* fullscreen may be denied — proceed anyway */ })
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

        // Preload TF.js immediately, then chain-load face-api.js models in the background.
        // By the time the student finishes the room scan and selfie, both will be ready.
        loadScript("https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js", function() {
            loadFaceApi();
        });
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
