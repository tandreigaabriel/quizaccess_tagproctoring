# TAG Proctoring Update Notes

## Student Pre-Exam Flow

- Reworked the pre-exam wizard into a TAG-branded guided secure exam flow.
- Added a full-screen dark overlay style inspired by the reference proctoring screenshots, without copying external branding.
- Updated the first step to show clearer rules and privacy messaging.
- Added rule categories for:
  - camera visibility
  - messaging apps
  - talking or receiving help
  - other people in the room
  - AI tools and remote access tools
  - phones, tablets, headphones, books, notes, and external materials
  - room and desk scan
  - fullscreen, single monitor, and screen sharing
- Added clearer room and desk scan instructions before recording.
- Simplified the selfie, ID photo, and screen-share step wording.
- Added green circular action buttons and camera-style capture buttons.

## Report Changes

- Changed report detail wording from "Violation Flags" to "Potential Violation of Rules".
- Changed "Violation Categories" to "Rule Categories".
- Changed "Review Evidence" button text to "View Flags".
- Changed evidence heading from "Tab / Window Evidence" to "Screen and Webcam Evidence".
- Added a readable evidence source label helper for screenshots.
- Fixed misleading screenshot labels:
  - `tabswitch` now displays as screen evidence, not webcam evidence.
  - `windowblur` now displays as screen evidence, not webcam evidence.
  - `contextmenu` now displays as right-click screen evidence.
- Added a separator before the source label in rule categories so copied/read text is clearer, e.g. "Exam Page Hidden · During Exam".

## Event Wording Fixes

- Replaced accusatory report labels:
  - "Left Exam View" is now "Exam Page Hidden".
  - "Switched Away From Exam" is now "Exam Window Lost Focus".
  - "Right-Click Menu" is now "Right-Click Attempt".
- Updated summary wording to match the softer labels.

## Monitoring Behavior Changes

- Added a 1-second debounce before logging `tabswitch`.
  - This reduces false positives caused by brief browser dialogs or temporary hidden-page states.
- Changed the tab hidden event detail from "Student left the exam tab" to "Exam page was hidden".
- Right-click events now trigger screenshot capture with `captureScreenshot('contextmenu')`.
  - Old attempts will not have right-click screenshots because the plugin did not capture them before this update.
  - New attempts should capture evidence for right-click attempts when screenshot capture is enabled.

## Files Updated

- `templates/wizard.mustache`
- `styles.css`
- `report.php`
- `amd/src/report.js`
- `amd/build/report.min.js`
- `build/report.min.js`
- `amd/src/monitor.js`
- `amd/build/monitor.min.js`
- `build/monitor.min.js`

## Notes

- Those images are not used by this plugin and were not copied into the UI.
- Moodle caches may need to be purged after deployment so updated templates, CSS, and AMD JavaScript are loaded.
