define("quizaccess_tagproctoring/recorder", [], function () {
    'use strict';

    var cfg = {};

    // Step completion flags
    var done = [false, false, false, false, false];
    // 0=rules, 1=roomscan, 2=selfie, 3=idphoto, 4=screenshare

    // Shared webcam stream (reused across steps 1-3)
    var webcamStream = null;
    var mediaRecorder = null;
    var recordedChunks = [];

    // AI detection models
    var tfModel = null;
    var faceModel = null;
    var detectedObjects = {};
    var detectionInterval = null;
    // ── Helpers ──────────────────────────────────────────────────────────────

    function el(id) { return document.getElementById(id); }

    function showStep(idx) {
        for (var i = 0; i < 5; i++) {
            var panel = el('tagwiz-step-' + i);
            if (panel) panel.style.display = (i === idx) ? 'block' : 'none';
            var ind = el('tagwiz-ind-' + i);
            if (ind) {
                ind.className = 'tagwiz-ind';
                if (i < idx || done[i]) ind.classList.add('tagwiz-ind--done');
                else if (i === idx) ind.classList.add('tagwiz-ind--active');
            }
            var conn = el('tagwiz-conn-' + i);
            if (conn) conn.className = 'tagwiz-connector' + (i < idx ? ' tagwiz-connector--done' : '');
        }
    }

    function markDone(idx) {
        done[idx] = true;
        var ind = el('tagwiz-ind-' + idx);
        if (ind) ind.classList.add('tagwiz-ind--done');
    }

    function setStatus(elId, msg, type) {
        var s = el(elId);
        if (!s) return;
        s.textContent = msg;
        s.className = 'tagwiz-status-msg' + (type ? ' alert alert-' + type + ' mt-2 py-1 px-2' : '');
    }

    // ── Webcam bootstrap ────────────────────────────────────────────────────

    function ensureWebcam(callback) {
        if (webcamStream && webcamStream.active) {
            callback(webcamStream);
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(function (stream) {
                webcamStream = stream;
                callback(stream);
            })
            .catch(function (err) {
                callback(null, err);
            });
    }

    function attachStream(videoEl, stream) {
        if (!videoEl || !stream) return;
        videoEl.srcObject = stream;
        videoEl.play().catch(function () { });
    }

    // ── AI Detection ─────────────────────────────────────────────

    function loadScript(src, callback) {

        if (document.querySelector('script[src="' + src + '"]')) {
            callback();
            return;
        }

        // Temporarily remove RequireJS define to prevent "Mismatched anonymous define()"
        // errors when TF.js / COCO-SSD / BlazeFace call define() without a module name.
        var savedDefine = window.define;
        window.define = undefined;

        var s = document.createElement("script");
        s.src = src;
        s.async = true;

        s.onload = function () {
            window.define = savedDefine;
            callback();
        };

        s.onerror = function () {
            window.define = savedDefine;
            callback("error");
        };

        document.head.appendChild(s);
    }

    function startAI(videoEl) {

        var TFJS = "https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js";
        var COCO = "https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js";
        var FACE = "https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js";

        loadScript(TFJS, function () {

            if (!window.tf) return;

            loadScript(COCO, function () {

                loadScript(FACE, function () {

                    cocoSsd.load().then(function (model) {

                        tfModel = model;

                        blazeface.load().then(function (face) {

                            faceModel = face;

                            detectionInterval = setInterval(function () {

                                if (!videoEl || videoEl.readyState < 2) return;

                                tfModel.detect(videoEl).then(function (objects) {

                                    var persons = 0;

                                    objects.forEach(function (o) {

                                        if (o.score < 0.35) return;

                                        var cls = o.class;

                                        if (["cell phone", "book", "laptop", "tablet", "remote"].includes(cls)) {

                                            detectedObjects[cls] =
                                                (detectedObjects[cls] || 0) + 1;

                                        }

                                        if (cls === "person") persons++;

                                    });

                                    if (persons > 1) {

                                        detectedObjects["extra_person"] =
                                            (detectedObjects["extra_person"] || 0) + 1;

                                    }

                                });

                                if (faceModel) {

                                    faceModel.estimateFaces(videoEl, false).then(function (faces) {

                                        if (faces.length === 0) {

                                            detectedObjects["no_face"] =
                                                (detectedObjects["no_face"] || 0) + 1;

                                        }

                                        if (faces.length > 1) {

                                            detectedObjects["multiple_faces"] =
                                                (detectedObjects["multiple_faces"] || 0) + 1;

                                        }

                                        // Face direction: if nose is offset from eye midpoint the face is turned
                                        if (faces.length === 1) {
                                            var lm = faces[0].landmarks;
                                            if (lm && lm.length >= 3) {
                                                var rEyeX  = lm[0][0];
                                                var lEyeX  = lm[1][0];
                                                var noseTipX = lm[2][0];
                                                var eyeMidX  = (rEyeX + lEyeX) / 2;
                                                var eyeDist  = Math.abs(lEyeX - rEyeX);
                                                if (eyeDist > 0 && Math.abs(noseTipX - eyeMidX) / eyeDist > 0.25) {
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

    function stopAI() {

        if (detectionInterval) {

            clearInterval(detectionInterval);
            detectionInterval = null;

        }

    }

    // ── Step 0 — Rules ──────────────────────────────────────────────────────

    function initRules() {
        var checkbox = el('tagwiz-rules-agree');
        var nextBtn = el('tagwiz-rules-next');
        if (!checkbox || !nextBtn) return;

        checkbox.addEventListener('change', function () {
            nextBtn.disabled = !checkbox.checked;
        });

        nextBtn.addEventListener('click', function () {
            if (!checkbox.checked) return;
            markDone(0);
            showStep(1);
            initRoomScan();
        });
    }

    // ── Step 1 — Room Scan ──────────────────────────────────────────────────

    function initRoomScan() {
        var video = el('tagwiz-rs-video');
        var startBtn = el('tagwiz-rs-start');
        var dot = el('tagwiz-rs-dot');
        var statusEl = el('tagwiz-rs-status');
        var timerEl = el('tagwiz-rs-timer');
        var successEl = el('tagwiz-rs-success');
        var nextBtn = el('tagwiz-rs-next');

        var MIN_SEC = 10, MAX_SEC = 15;
        var elapsed = 0, tickInterval = null, started = false;

        ensureWebcam(function (stream) {
            if (!stream) {
                setStatus('tagwiz-rs-status', 'Camera access denied — please allow camera/microphone and reload.', 'danger');
                startBtn.disabled = true;
                return;
            }
            attachStream(video, stream);
            startAI(video);

            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = function (e) {
                if (e.data && e.data.size > 0) recordedChunks.push(e.data);
            };

            mediaRecorder.onstop = function () {
                var blob = new Blob(recordedChunks, { type: 'video/webm' });
                recordedChunks = [];
                uploadRoomScan(blob);
                    stopAI();
            };

            startBtn.addEventListener('click', function () {
                if (mediaRecorder.state === 'inactive' && !started) {
                    // Start recording
                    started = true;
                    elapsed = 0;
                    recordedChunks = [];
                    mediaRecorder.start();
                    dot.classList.add('is-recording');
                    startBtn.textContent = 'Stop Recording';
                    startBtn.classList.remove('btn-primary');
                    startBtn.classList.add('btn-danger');
                    statusEl.textContent = 'Recording… slowly pan 360° around the room and desk.';

                    tickInterval = setInterval(function () {
                        elapsed++;
                        timerEl.textContent = elapsed + 's / ' + MAX_SEC + 's';
                        if (elapsed >= MAX_SEC) {
                            clearInterval(tickInterval);
                            stopRecording();
                        }
                    }, 1000);

                } else if (mediaRecorder.state === 'recording') {
                    if (elapsed < MIN_SEC) {
                        setStatus('tagwiz-rs-status',
                            'Minimum ' + MIN_SEC + 's required — currently at ' + elapsed + 's. Keep scanning.', 'warning');
                        return;
                    }
                    clearInterval(tickInterval);
                    stopRecording();
                }
            });
        });

        function stopRecording() {
            dot.classList.remove('is-recording');
            startBtn.disabled = true;
            startBtn.textContent = 'Uploading…';
            timerEl.textContent = '';
            mediaRecorder.stop();
        }

        function uploadRoomScan(blob) {
            statusEl.textContent = 'Uploading room scan…';
            var fd = new FormData();
            fd.append('video', blob, 'roomscan.webm');
            fd.append('scan_objects', JSON.stringify(detectedObjects));
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.uploadurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.status === 'ok') {
                        successEl.style.display = 'block';
                        statusEl.textContent = '';
                        nextBtn.disabled = false;
                        markDone(1);
                    } else {
                        setStatus('tagwiz-rs-status', 'Upload failed: ' + (data.message || 'unknown error') + '. Retry.', 'danger');
                        startBtn.disabled = false;
                        startBtn.textContent = 'Retry Recording';
                        startBtn.classList.remove('btn-danger');
                        startBtn.classList.add('btn-primary');
                        started = false;
                    }
                })
                .catch(function () {
                    setStatus('tagwiz-rs-status', 'Upload error — please retry.', 'danger');
                    startBtn.disabled = false;
                    startBtn.textContent = 'Retry Recording';
                    startBtn.classList.remove('btn-danger');
                    startBtn.classList.add('btn-primary');
                    started = false;
                });
        }

        nextBtn.addEventListener('click', function () {
            if (!done[1]) return;
            showStep(2);
            initSelfie();
        });
    }

    // ── Step 2 — Selfie ─────────────────────────────────────────────────────

    function initSelfie() {
        var video = el('tagwiz-selfie-video');
        var captureBtn = el('tagwiz-selfie-capture');
        var retakeBtn = el('tagwiz-selfie-retake');
        var previewWrap = el('tagwiz-selfie-preview-wrap');
        var previewImg = el('tagwiz-selfie-preview');
        var nextBtn = el('tagwiz-selfie-next');

        // Reuse existing webcam stream (already audio+video from room scan)
        ensureWebcam(function (stream) {
            if (!stream) { setStatus('tagwiz-selfie-status', 'Camera unavailable.', 'danger'); return; }
            // Attach audio-free clone if possible, else just show the stream
            attachStream(video, stream);
        });

        captureBtn.addEventListener('click', function () {
            var canvas = document.createElement('canvas');
            canvas.width = 640; canvas.height = 480;
            canvas.getContext('2d').drawImage(video, 0, 0, 640, 480);
            canvas.toBlob(function (blob) {
                if (!blob) return;
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-block';
                uploadSelfie(blob);
            }, 'image/jpeg', 0.88);
        });

        retakeBtn.addEventListener('click', function () {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            nextBtn.disabled = true;
            done[2] = false;
            setStatus('tagwiz-selfie-status', '', '');
        });

        function uploadSelfie(blob) {
            setStatus('tagwiz-selfie-status', 'Uploading selfie…', '');
            var fd = new FormData();
            fd.append('selfie', blob, 'selfie.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.selfieurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-selfie-status', '✅ Selfie saved.', 'success');
                        nextBtn.disabled = false;
                        markDone(2);
                    } else {
                        setStatus('tagwiz-selfie-status', 'Upload failed — please retake.', 'danger');
                    }
                })
                .catch(function () {
                    setStatus('tagwiz-selfie-status', 'Upload error — please retake.', 'danger');
                });
        }

        nextBtn.addEventListener('click', function () {
            if (!done[2]) return;
            showStep(3);
            initIdPhoto();
        });
    }

    // ── Step 3 — ID Photo ───────────────────────────────────────────────────

    function initIdPhoto() {
        var video = el('tagwiz-id-video');
        var captureBtn = el('tagwiz-id-capture');
        var retakeBtn = el('tagwiz-id-retake');
        var previewWrap = el('tagwiz-id-preview-wrap');
        var previewImg = el('tagwiz-id-preview');
        var nextBtn = el('tagwiz-id-next');

        ensureWebcam(function (stream) {
            if (!stream) { setStatus('tagwiz-id-status', 'Camera unavailable.', 'danger'); return; }
            attachStream(video, stream);
        });

        captureBtn.addEventListener('click', function () {
            var canvas = document.createElement('canvas');
            canvas.width = 640; canvas.height = 480;
            canvas.getContext('2d').drawImage(video, 0, 0, 640, 480);
            canvas.toBlob(function (blob) {
                if (!blob) return;
                previewImg.src = URL.createObjectURL(blob);
                previewWrap.style.display = 'block';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-block';
                uploadIdPhoto(blob);
            }, 'image/jpeg', 0.88);
        });

        retakeBtn.addEventListener('click', function () {
            previewImg.src = '';
            previewWrap.style.display = 'none';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            nextBtn.disabled = true;
            done[3] = false;
            setStatus('tagwiz-id-status', '', '');
        });

        function uploadIdPhoto(blob) {
            setStatus('tagwiz-id-status', 'Uploading ID photo…', '');
            var fd = new FormData();
            fd.append('idphoto', blob, 'idphoto.jpg');
            fd.append('quizid', cfg.quizid);
            fd.append('sesskey', M.cfg.sesskey);

            fetch(cfg.idphotourl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.status === 'ok') {
                        setStatus('tagwiz-id-status', '✅ ID photo saved.', 'success');
                        nextBtn.disabled = false;
                        markDone(3);
                    } else {
                        setStatus('tagwiz-id-status', 'Upload failed — please retake.', 'danger');
                    }
                })
                .catch(function () {
                    setStatus('tagwiz-id-status', 'Upload error — please retake.', 'danger');
                });
        }

        nextBtn.addEventListener('click', function () {
            if (!done[3]) return;
            showStep(4);
        });
    }

    // ── Step 4 — Screen Share ────────────────────────────────────────────────

    function initScreenShare() {
        var shareBtn = el('tagwiz-ss-share');
        var successEl = el('tagwiz-ss-success');
        var startBtn = el('tagwiz-start-quiz');
        if (!shareBtn) return;

        shareBtn.addEventListener('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                setStatus('tagwiz-ss-status', 'Screen sharing is not supported in this browser. Use Chrome or Edge.', 'danger');
                return;
            }

            navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' }, audio: false })
                .then(function (stream) {
                    var track = stream.getVideoTracks()[0];
                    var settings = track ? track.getSettings() : {};

                    // Stop the stream immediately — we only needed verification
                    stream.getTracks().forEach(function (t) { t.stop(); });

                    // Validate that the user shared the entire screen, not a tab/window
                    if (settings.displaySurface && settings.displaySurface !== 'monitor') {
                        setStatus('tagwiz-ss-status',
                            '⚠️ You selected a ' + settings.displaySurface + ' — you must choose "Entire Screen". Please try again.',
                            'danger');
                        return;
                    }

                    // Success
                    setStatus('tagwiz-ss-status', '', '');
                    successEl.style.display = 'block';
                    shareBtn.style.display = 'none';
                    markDone(4);
                    startBtn.style.display = 'inline-block';
                })
                .catch(function (err) {
                    if (err.name === 'NotAllowedError') {
                        setStatus('tagwiz-ss-status', 'Screen share was cancelled. Please click Share and allow access.', 'warning');
                    } else {
                        setStatus('tagwiz-ss-status', 'Screen sharing error: ' + err.message, 'danger');
                    }
                });
        });

        if (startBtn) {
            startBtn.addEventListener('click', function () {
                if (!done[4]) return;
                startBtn.disabled = true;
                startBtn.textContent = 'Starting…';

                // Mark Moodle's hidden consent field as checked
                var moodleConsent = el('id_tagproctoring_consent');
                if (moodleConsent) moodleConsent.checked = true;

                // Mark wizard complete
                var completeField = el('id_tagproctoring_complete');
                if (completeField) completeField.value = '1';

                // Enter fullscreen then submit
                var fsTarget = document.documentElement;
                var fsReq = fsTarget.requestFullscreen ||
                    fsTarget.webkitRequestFullscreen ||
                    fsTarget.mozRequestFullScreen;

                var fsPromise = fsReq ? fsReq.call(fsTarget) : Promise.resolve();

                Promise.resolve(fsPromise)
                    .catch(function () { /* fullscreen may be denied — proceed anyway */ })
                    .then(function () { submitMoodleForm(); });
            });
        }
    }

    function submitMoodleForm() {
        // Try the preflight form submit button first
        var btn = el('id_submitbutton');
        if (btn) { btn.click(); return; }
        // Fallback: any submit button on the page
        var anyBtn = document.querySelector('button[type="submit"], input[type="submit"]');
        if (anyBtn) { anyBtn.click(); return; }
        // Last resort
        var form = document.querySelector('form[method="post"]');
        if (form) form.submit();
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function initWizard() {
        showStep(0);
        initRules();
        initScreenShare(); // attach listener upfront; step panel is hidden until reached
        // Preload TF.js immediately so models are ready by the time room scan starts
        loadScript("https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js", function(){});
    }

    return {
        init: function (config) {
            cfg = config;
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWizard);
            } else {
                initWizard();
            }
        }
    };
});
