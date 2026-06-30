# Firefox and Safari support notes

Date reviewed: 2026-06-30

## What this plugin is

This is a Moodle quiz access rule plugin named `quizaccess_tagproctoring`.

The plugin adds a proctored quiz flow with:

- Pre-exam wizard in `templates/wizard.mustache`.
- Wizard browser logic in `amd/src/recorder.js`.
- Live attempt monitoring in `amd/src/monitor.js`.
- Moodle integration and AMD module bootstrapping in `rule.php`.
- Upload endpoints for room scan, selfie, ID photo, and event screenshots.
- Event/risk reporting through PHP report pages and `amd/src/report.js`.

The student flow is:

1. Accept proctoring rules.
2. Complete a room scan using webcam and microphone.
3. Capture a selfie.
4. Capture ID photo.
5. Share the entire screen.
6. Start quiz.
7. During the quiz, the monitor enforces fullscreen, keeps screen sharing active, logs events, captures screenshots on suspicious events, monitors webcam face direction, and detects possible tab/app switching.

## Current browser support state

The plugin currently allows Chrome and Edge only on desktop/laptop.

The hard browser block is in `amd/src/monitor.js`:

- `getSupportedBrowser()` returns `Edge` for `Edg/`.
- `getSupportedBrowser()` returns `Chrome` for `Chrome/`.
- Firefox and Safari return `null`.
- `checkEnvironment()` blocks when the browser is not Chrome/Edge.

There is also a Chrome/Edge message in `amd/src/recorder.js` when `getDisplayMedia` is missing:

- "Screen sharing is not supported in this browser. Use Chrome or Edge."

So Firefox and Safari are blocked by policy in the code, but there are also real browser API differences that need handling before they can be safely enabled.

## Main APIs used

The browser-facing code depends on:

- `navigator.mediaDevices.getUserMedia()` for webcam and microphone.
- `MediaRecorder` for room scan video recording.
- `navigator.mediaDevices.getDisplayMedia()` for screen sharing.
- `MediaStreamTrack.getSettings().displaySurface` to check whether the user selected an entire monitor.
- `window.screen.isExtended` to detect multiple monitors.
- Fullscreen API: `requestFullscreen`, `webkitRequestFullscreen`, `mozRequestFullScreen`.
- Page visibility/focus events for tab/app switching.
- Canvas `toBlob()` for screenshots/selfie/ID images.
- TensorFlow.js, COCO-SSD, and BlazeFace for object/face detection.

MDN notes that `getDisplayMedia()` is not Baseline because it does not work in some widely used browsers, and requires secure context/HTTPS in supporting browsers:
https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getDisplayMedia

MDN marks `Screen.isExtended` as limited availability and experimental:
https://developer.mozilla.org/en-US/docs/Web/API/Screen/isExtended

MDN says `MediaRecorder` is broadly available, but MIME/container support can vary, and `MediaRecorder.isTypeSupported()` should be used for specific formats:
https://developer.mozilla.org/en-US/docs/Web/API/MediaRecorder

## Firefox support considerations

Firefox should be closer than Safari for this plugin because it supports many of the required media and fullscreen APIs.

Likely work:

- Add Firefox to `getSupportedBrowser()`.
- Update user-facing messages from "Chrome or Edge" to "Chrome, Edge, Firefox, or Safari" only after feature checks pass.
- Use feature detection first, user-agent second.
- Test `getDisplayMedia({video: {displaySurface: 'monitor'}})` because Firefox may ignore or handle constraints differently from Chromium.
- Do not rely fully on `track.getSettings().displaySurface`; if it is absent, the plugin cannot prove the user selected the entire screen.
- Decide whether missing `displaySurface` should block the attempt, warn the student, or allow with a logged risk event.
- Test fullscreen and screen-share sequencing because current comments say the `getDisplayMedia` to `requestFullscreen` timing was tuned around Chrome/Edge behavior.
- Replace hard-coded `video/webm` room scan output with MIME negotiation using `MediaRecorder.isTypeSupported()`.

## Safari support considerations

Safari needs more care.

Likely work:

- Add Safari to browser detection only after runtime feature checks pass.
- Require HTTPS, because media capture and screen capture APIs need secure context.
- Check whether `getDisplayMedia` exists before showing the screen-share step.
- Expect screen-share options and returned `displaySurface` values to differ from Chromium.
- Expect `window.screen.isExtended` to be unavailable or unreliable.
- Use `MediaRecorder.isTypeSupported()` to choose a Safari-compatible recording MIME type. Safari may prefer MP4/H.264 variants rather than WebM.
- Ensure uploads and report playback can handle non-WebM room scan files if Safari records MP4.
- Test `canvas.toBlob('image/jpeg')`, fullscreen behavior, camera permission persistence, and autoplay behavior on Safari/macOS.
- iPhone/iPad should probably remain blocked because the proctoring rules require desktop/laptop behavior, fullscreen, screen sharing, and reliable multi-window/tab monitoring.

## Biggest implementation risks

1. Entire-screen verification

The code currently checks:

```js
settings.displaySurface && settings.displaySurface !== 'monitor'
```

If Firefox or Safari does not return `displaySurface`, the plugin cannot confirm "Entire Screen" with the same confidence as Chromium.

2. Multiple-monitor detection

The code currently checks:

```js
window.screen && window.screen.isExtended === true
```

This API is experimental and limited. Firefox/Safari may not support it. If unsupported, the plugin should not assume "single monitor"; it should show a weaker check status or log that monitor detection is unavailable.

3. Recording format

The room scan currently creates:

```js
mediaRecorder = new MediaRecorder(stream);
var blob = new Blob(recordedChunks, {type: 'video/webm'});
```

That assumes WebM. Cross-browser support should select a supported MIME type and use the same type for the uploaded blob filename/extension.

4. Fullscreen plus screen-share timing

Current code has a Chrome/Edge-specific note that a short timeout keeps fullscreen inside a gesture window those browsers allow. Firefox/Safari may need different sequencing.

## Recommended support strategy

Implement browser support as capability tiers, not browser-name tiers.

Tier A: full support

- Webcam available.
- MediaRecorder available with supported MIME type.
- Screen sharing available.
- Fullscreen available.
- Entire-screen selection can be verified.
- Multiple-monitor detection available.

Tier B: compatible with warnings/logging

- Webcam, recording, screen sharing, and fullscreen available.
- Entire-screen or multiple-monitor detection cannot be fully verified.
- Allow only if business rules accept reduced enforcement, and log a session-start detail such as "monitor detection unavailable".

Tier C: blocked

- No webcam.
- No MediaRecorder.
- No screen sharing.
- No fullscreen.
- Mobile/tablet device.

## Suggested code changes later

1. Add a browser capability helper in `amd/src/monitor.js` and `amd/src/recorder.js`.
2. Replace `getSupportedBrowser()` with `getBrowserName()` plus `getCapabilityStatus()`.
3. Add Firefox/Safari user-agent names for reporting, not for permission decisions.
4. Change room scan recording to negotiate MIME type:

```js
var preferredTypes = [
    'video/webm;codecs=vp9,opus',
    'video/webm;codecs=vp8,opus',
    'video/webm',
    'video/mp4'
];
```

5. Store the chosen MIME type and upload a matching extension.
6. Make screen-share verification handle missing `displaySurface`.
7. Make multi-monitor detection handle unsupported `screen.isExtended`.
8. Rebuild AMD assets after source changes.
9. Test on:

- Chrome on Windows/macOS.
- Edge on Windows/macOS.
- Firefox on Windows/macOS.
- Safari on macOS.

## Files most relevant for this work

- `amd/src/monitor.js`
- `amd/src/recorder.js`
- `templates/wizard.mustache`
- `rule.php`
- `upload.php`
- `screenshot_upload.php`
- `report.php`
- `screenshots.php`

