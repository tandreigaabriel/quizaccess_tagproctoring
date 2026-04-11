define("quizaccess_tagproctoring/monitor", [], function () {

    let screenshotInterval = null;
    let webcamStream = null;
    let cfg = {};

    function logEvent(eventtype, detail) {
        const fd = new FormData();
        fd.append("cmid", cfg.cmid);
        fd.append("attemptid", cfg.attemptid);
        fd.append("eventtype", eventtype);
        fd.append("detail", detail || "");
        fd.append("sesskey", M.cfg.sesskey);
        fetch(M.cfg.wwwroot + "/mod/quiz/accessrule/tagproctoring/event_log.php", {
            method: "POST",
            body: fd,
            credentials: "same-origin"
        }).catch(function () {});
    }

    function captureScreenshot() {
        if (!webcamStream) return;
        const video = document.createElement("video");
        video.srcObject = webcamStream;
        video.play().then(function () {
            const canvas = document.createElement("canvas");
            canvas.width = 320;
            canvas.height = 240;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, 320, 240);
            canvas.toBlob(function (blob) {
                if (!blob) return;
                const fd = new FormData();
                fd.append("screenshot", blob, "shot.jpg");
                fd.append("cmid", cfg.cmid);
                fd.append("attemptid", cfg.attemptid);
                fd.append("sesskey", M.cfg.sesskey);
                fetch(M.cfg.wwwroot + "/mod/quiz/accessrule/tagproctoring/screenshot_upload.php", {
                    method: "POST",
                    body: fd,
                    credentials: "same-origin"
                }).catch(function () {});
                video.pause();
                video.srcObject = null;
            }, "image/jpeg", 0.7);
        }).catch(function () {});
    }

    function startWebcam() {
        navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 }, audio: false })
            .then(function (stream) {
                webcamStream = stream;
                // Take first screenshot after 5s, then every cfg.interval seconds
                setTimeout(captureScreenshot, 5000);
                screenshotInterval = setInterval(captureScreenshot, (cfg.interval || 30) * 1000);
            })
            .catch(function () {
                // No webcam - log it
                logEvent("nocamera", "Webcam unavailable during attempt");
            });
    }

    function detectMultipleScreens() {
        if (window.screen && window.screen.isExtended) {
            logEvent("multiplescreen", "window.screen.isExtended = true");
        }
    }

    function detectScreenShare() {
        // Listen for display media being shared (works in some browsers)
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            // We patch getDisplayMedia to detect if student initiates screen share
            const original = navigator.mediaDevices.getDisplayMedia.bind(navigator.mediaDevices);
            navigator.mediaDevices.getDisplayMedia = function (constraints) {
                logEvent("screenshare", "Student initiated screen share");
                return original(constraints);
            };
        }
    }

    function detectTabSwitch() {
        let switchCount = 0;
        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                switchCount++;
                logEvent("visibilityhidden", "Tab hidden, count=" + switchCount);
            }
        });
        window.addEventListener("blur", function () {
            logEvent("tabswitch", "Window lost focus");
        });
    }

    function detectCopyPaste() {
        document.addEventListener("copy", function () {
            logEvent("copypaste", "Copy event");
        });
        document.addEventListener("paste", function () {
            logEvent("copypaste", "Paste event");
        });
        document.addEventListener("cut", function () {
            logEvent("copypaste", "Cut event");
        });
    }

    function detectContextMenu() {
        document.addEventListener("contextmenu", function () {
            logEvent("contextmenu", "Right-click detected");
        });
    }

    function enforceFullscreen() {
        // Show a blocking overlay whenever the student exits fullscreen
        var overlay = document.createElement('div');
        overlay.id = 'tagproctor-fs-overlay';
        overlay.style.cssText = [
            'position:fixed;inset:0;z-index:999999',
            'background:rgba(0,0,0,.92)',
            'display:none',
            'flex-direction:column',
            'align-items:center',
            'justify-content:center',
            'color:#fff',
            'font-family:sans-serif',
            'text-align:center',
            'padding:32px'
        ].join(';');
        overlay.innerHTML = [
            '<div style="font-size:48px;margin-bottom:16px">⚠️</div>',
            '<h2 style="margin:0 0 10px;font-size:24px">Full Screen Required</h2>',
            '<p style="margin:0 0 24px;font-size:15px;opacity:.85">',
            'You exited full screen. Click the button below to return.',
            '</p>',
            '<button id="tagproctor-fs-reenter"',
            ' style="padding:12px 28px;font-size:16px;font-weight:700;',
            'border:none;border-radius:8px;background:#7e2a8e;color:#fff;cursor:pointer">',
            '🔒 Re-enter Full Screen',
            '</button>'
        ].join('');
        document.body.appendChild(overlay);

        document.getElementById('tagproctor-fs-reenter').addEventListener('click', function () {
            var fsReq = document.documentElement.requestFullscreen ||
                        document.documentElement.webkitRequestFullscreen ||
                        document.documentElement.mozRequestFullScreen;
            if (fsReq) fsReq.call(document.documentElement);
        });

        document.addEventListener('fullscreenchange', function () {
            var isFs = !!(document.fullscreenElement ||
                          document.webkitFullscreenElement ||
                          document.mozFullScreenElement);
            if (!isFs) {
                overlay.style.display = 'flex';
                logEvent('fullscreenexit', 'Student exited full screen');
            } else {
                overlay.style.display = 'none';
            }
        });
    }

    function detectDevTools() {
        // Basic devtools detection via window size difference
        const threshold = 160;
        setInterval(function () {
            const widthDiff  = window.outerWidth - window.innerWidth;
            const heightDiff = window.outerHeight - window.innerHeight;
            if (widthDiff > threshold || heightDiff > threshold) {
                logEvent("devtools", "Possible DevTools open: w=" + widthDiff + " h=" + heightDiff);
            }
        }, 10000);
    }

    return {
        init: function (config) {
            cfg = config;
            if (!cfg.attemptid || !cfg.cmid) {
                console.warn("TAG Monitor: missing attemptid or cmid");
                return;
            }

            startWebcam();
            enforceFullscreen();
            detectMultipleScreens();
            detectScreenShare();
            detectTabSwitch();
            detectCopyPaste();
            detectContextMenu();
            detectDevTools();

            // Stop webcam when quiz is submitted
            window.addEventListener("beforeunload", function () {
                if (webcamStream) {
                    webcamStream.getTracks().forEach(function (t) { t.stop(); });
                }
                if (screenshotInterval) {
                    clearInterval(screenshotInterval);
                }
            });
        }
    };
});
