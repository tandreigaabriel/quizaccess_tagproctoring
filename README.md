# TAG Proctoring — Moodle Quiz Access Rule

**Component:** `quizaccess_tagproctoring`  
**Moodle:** 4.5+  
**PHP:** 8.1+  
**License:** GNU GPL v3 or later

## Overview

TAG Proctoring is a quiz access rule plugin for Moodle that enables lightweight browser-based exam proctoring. Before a student can start a quiz attempt, they are guided through a multi-step wizard that records a room scan video, captures a selfie and photo ID, and prompts for screen sharing. During the attempt, the plugin monitors for suspicious behaviour and captures periodic screenshots from both the webcam and the shared screen. Instructors review proctoring data through a dedicated report page with per-attempt risk scores, evidence carousels, and manual override controls.

## Features

### Pre-exam wizard

- Room scan video recording with AI-assisted object detection (phones, books, laptops, extra people, and configurable object classes)
- Selfie capture with animated guide, auto-capture countdown, and blur/brightness/face validation
- Photo ID capture with alignment guide box, face detection, and auto-capture
- Screen share prompt with surface-type enforcement (tab share blocked, full-screen or monitor required)
- Multi-monitor detection — blocks exam start when more than one display is active
- Animated fullscreen takeover overlay with escalating warnings on repeated exits
- Mobile and unsupported browser blocking with clear messaging

### In-exam monitoring

- Periodic webcam screenshots (configurable interval and cap)
- Periodic screen-share screenshots
- Event logging: tab switch, window blur, copy/paste, DevTools open, context menu, fullscreen exit
- Face-turn detection via AI (logs `faceturn` event after configurable threshold)
- Status bar showing webcam dot, screen-share dot, and elapsed timer

### Instructor report

- Three-level drill-down: students → quiz sessions → evidence panel
- Per-attempt risk score and risk level (High / Needs Review / Valid)
- AI summary block: plain-English event breakdown per attempt
- Flags panel with violation categories and screenshot carousel
- Room scan video playback in the report
- Selfie and ID photo display per student
- Face match verification status
- Manual override (mark attempt as Valid regardless of score)
- Mark attempt as Reviewed
- Grant rescan (allow student to re-record room scan)
- Delete screenshots, delete events, delete scan data per student
- CSV export of all attempt data
- Search/filter student list

### Administration

- Risk scoring with configurable per-event weights and threshold boundaries
- Post-attempt email alerts to configured recipients when risk is Medium or High
- Grades table proctoring column showing risk badge in Moodle gradebook
- Automatic data retention cleanup via scheduled task
- Full Moodle Privacy API (GDPR data export and deletion)
- Licence key management with automatic activation

## Requirements

- Moodle 4.5 or later
- PHP 8.1 or later
- HTTPS required — webcam, screen share, and MediaRecorder APIs require a secure context
- Modern browser with support for `getUserMedia`, `getDisplayMedia`, and `MediaRecorder` (Chrome 72+, Edge 79+, Firefox 66+)
- A valid TAG Proctoring licence key (free — request at [tandreig.com/request-key](https://tandreig.com/request-key?plugin=tagproctoring))

## Installation

1. Copy the `tagproctoring` folder into `mod/quiz/accessrule/` inside your Moodle installation so the path is:
   ```
   mod/quiz/accessrule/tagproctoring/
   ```
2. Log in as a Moodle administrator and go to **Site administration → Notifications** to run the database upgrade. This creates the required tables.
3. Request a free licence key at **[tandreig.com/request-key](https://tandreig.com/request-key?plugin=tagproctoring)** — the key is emailed to you instantly. Then go to **Site administration → Plugins → Quiz access rules → TAG Proctoring** and paste it. The plugin contacts the activation service and stores the licence status.
4. Enable the access rule on a quiz via **Quiz settings → Extra restrictions on attempts → Enable TAG Proctoring**.

## Configuration

### Global settings — Site administration → Plugins → Quiz access rules → TAG Proctoring

| Setting                     | Description                                                             |
| --------------------------- | ----------------------------------------------------------------------- |
| Licence key                 | Free key — request at tandreig.com/request-key                          |
| Enable room scan            | Master switch for the room scan wizard step                             |
| Enable screenshots          | Master switch for in-exam screenshot capture                            |
| Enable event logging        | Master switch for in-exam event tracking                                |
| Data retention period       | Days before scan videos and screenshots are purged (default: 30)        |
| Screenshot interval         | Seconds between automatic screenshots (default: 30)                     |
| Max screenshots per attempt | Storage cap per attempt (default: 20)                                   |
| AI confidence threshold     | Minimum confidence for object detection (default: 0.35)                 |
| Flagged object types        | Comma-separated list of COCO-SSD classes that trigger a suspicious flag |
| High risk threshold         | Risk score at or above which an attempt is marked High (default: 60)    |
| Medium risk threshold       | Score at or above which an attempt is marked Needs Review (default: 20) |
| Email alerts                | Enable post-attempt alert emails                                        |
| Alert recipients            | Comma-separated email addresses; defaults to quiz teachers when empty   |

### Per-quiz settings — Quiz settings → Extra restrictions on attempts

| Setting                                | Description                                                                |
| -------------------------------------- | -------------------------------------------------------------------------- |
| Enable TAG Proctoring                  | Activates proctoring for this quiz                                         |
| Require room scan                      | Forces the room scan step in the wizard                                    |
| Room scan duration                     | Maximum recording time in seconds for this quiz (overrides global, min 30) |
| Require new room scan for each attempt | Student must re-scan before each attempt, not just the first               |

## Author & Support

**Author:** Andrei Toma — [tandreig.com](https://www.tandreig.com)  
**Plugin page:** [tandreig.com/plugins/proctoring-system](https://tandreig.com/plugins/proctoring-system)  
**Support:** [tandreig.com/plugins/support](https://tandreig.com/plugins/support)  
**Licence key:** [tandreig.com/request-key](https://tandreig.com/request-key?plugin=tagproctoring) — free, emailed instantly

### v1.0.0 — 2026-07-12

- Initial stable release
- Fixed: PHPCS — 0 errors, 0 warnings across all plugin files
- Fixed: Mustache CI — example context added to report.mustache, HTML validation errors resolved
- Fixed: PSR12 — blank line after class opening brace in send_attempt_alert.php
- Fixed: Missing lang string `wizard_id_checklist_title`
- Fixed: Lang file rewritten with all 260+ strings in strict lexicographic order, section comments removed
