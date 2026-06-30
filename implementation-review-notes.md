# TAG Proctoring implementation review notes

Date: 2026-06-30

## Browser support

- Replaced Chrome/Edge-only blocking with capability checks for secure context, webcam, screen sharing, and fullscreen.
- Added Firefox and Safari reporting support where the required APIs are available.
- Kept mobile and tablet blocked because this proctoring flow depends on desktop/laptop behavior.
- Added a browser capability warning when monitor-count detection is unavailable.

## Permission and fullscreen flow

- Removed early fullscreen entry from the setup wizard because Firefox cancels permission prompts in DOM fullscreen.
- Changed the attempt-page monitor so screen sharing is requested before returning to fullscreen.
- Camera setup now retries camera-only if the combined camera+microphone request fails.

## Room scan uploads

- Added MediaRecorder MIME negotiation instead of assuming WebM.
- Uploads can now save `.webm` or `.mp4`.
- Upload responses are parsed from raw text first so server errors are visible instead of leaving the UI stuck on "Uploading".

## Screenshots and evidence

- Screenshots are now captured only when the student leaves the exam view:
  - `tabswitch`: tab becomes hidden.
  - `windowblur`: browser/window loses focus and does not quickly regain it.
- Screenshots are no longer captured for:
  - face movement left/right.
  - face not visible.
  - fullscreen exit.
  - screen-share stop.
- Report wording now calls this evidence "Tab / Window Evidence".

## Student left exam trigger

The plugin should flag review evidence when a student leaves the exam because they may be opening notes, reading elsewhere, or checking another app before returning.

Current trigger behavior:

- `visibilitychange` with `document.hidden` logs `tabswitch`.
- `window.blur` logs `windowblur` only after a short debounce and only if focus does not return.
- Both triggers store a screenshot when screen sharing is available.
- Navigation/submission is ignored through `beforeunload`.

Report wording:

- `tabswitch` is shown as "Left Exam View".
- `windowblur` is shown as "Switched Away From Exam".

## Face movement policy

- Normal left/right face movement is not treated as cheating evidence.
- `faceturn` no longer contributes to risk.
- Old `faceturn` records are hidden from violation categories in the report UI.
- Face-not-visible can still be logged for context, but it no longer captures screenshots or adds risk.

## Risk scoring

- `screensharestop` is high risk.
- `tabswitch` and `windowblur` add review risk and are the main screenshot evidence triggers.
- `capabilitywarning` adds review risk when browser APIs cannot verify monitor count.
- `faceturn` and `faceaway` no longer add risk.

## UX/report improvements

- Pre-exam wizard copy was softened and made clearer.
- Rules section now clearly tells students they must scroll to the bottom before continuing.
- Report evidence thumbnails are larger and the report layout is cleaner.
- Report labels use more review-oriented language instead of treating every event as automatic cheating.
- Wizard wrapper was changed to behave better inside the Moodle modal instead of forcing a fixed viewport overlay.
- Rules, header, stepper, and card spacing were reduced so the first step is less oversized.
- Selfie/ID camera panels now have max heights and smaller countdown text.
- Fullscreen overlay button/panel was tightened so the button sits centered and does not stretch awkwardly in the modal.
- Invigilator session cards now use clearer wording: "Review Evidence", "Evidence shots", and "Delete Evidence".

## Files touched

- `amd/src/monitor.js`
- `amd/src/recorder.js`
- `amd/src/report.js`
- `amd/build/*.min.js`
- `build/*.min.js`
- `lib.php`
- `report.php`
- `upload.php`
- `styles.css`
- `templates/wizard.mustache`
- `lang/en/quizaccess_tagproctoring.php`
