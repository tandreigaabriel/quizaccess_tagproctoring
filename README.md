# TAG Proctoring — Moodle Quiz Access Rule

**Component:** `quizaccess_tagproctoring`  
**Moodle:** 4.5+  
**PHP:** 8.1+  
**License:** GNU GPL v3 or later

## Overview

TAG Proctoring is a quiz access rule plugin for Moodle that enables lightweight browser-based exam proctoring. Before a student can start a quiz attempt, they are guided through a pre-exam wizard that records a room scan video, captures a selfie and photo ID, and prompts for screen sharing. During the attempt, the plugin monitors for suspicious behaviour and captures periodic webcam screenshots.

Proctoring data is reviewed by instructors through a dedicated report page accessible from the quiz activity.

## Features

- Pre-exam wizard: room scan video, identity selfie, photo ID capture, screen-share prompt
- AI-assisted object detection during room scan (detects phones, books, extra people, laptops, and other configurable objects)
- Periodic webcam screenshots during the attempt
- Suspicious event tracking: tab switches, copy/paste, DevTools, fullscreen exit, window blur, multiple screens
- Risk scoring system with configurable thresholds (High / Medium / Low)
- Instructor proctoring report with per-attempt review, manual override, and CSV export
- Post-attempt email alerts to configured recipients when attempts are flagged
- Data retention with automatic cleanup via scheduled task
- Full Moodle Privacy API (GDPR) implementation

## Requirements

- Moodle 4.5 or later
- PHP 8.1 or later
- HTTPS required (webcam and screen share APIs require a secure context)
- Modern browser with support for `getUserMedia`, `getDisplayMedia`, and `MediaRecorder`
- A valid TAG Proctoring licence key (issued after plugin activation)

## Installation

1. Download or clone this repository into `mod/quiz/accessrule/tagproctoring/` inside your Moodle installation.
2. Log in as a Moodle administrator and navigate to **Site administration → Notifications** to run the database upgrade.
3. Go to **Site administration → Plugins → Quiz access rules → TAG Proctoring** and enter your licence key.
4. Enable the access rule on any quiz via the quiz settings page under **Extra restrictions on attempts**.

## Configuration

Global settings are available at **Site administration → Plugins → Quiz access rules → TAG Proctoring**:

| Setting | Description |
|---|---|
| Licence key | Paste the key issued after purchase verification |
| Enable room scan | Master switch for the room scan step |
| Room scan duration | Maximum recording duration in seconds (default: 45) |
| Data retention period | Days before scan videos and screenshots are purged (default: 30) |
| Screenshot interval | How often webcam screenshots are captured in seconds (default: 30) |
| Max screenshots per attempt | Storage cap per attempt (default: 20) |
| AI confidence threshold | Minimum confidence for object detection (default: 0.35) |
| Flagged object types | List of object classes that trigger a suspicious flag |
| Risk thresholds | Score boundaries for High and Medium risk levels |
| Email alerts | Enable post-attempt alert emails to configured recipients |

Per-quiz settings (on the quiz settings page):

- Enable TAG Proctoring for this quiz
- Require room scan
- Require new room scan for each attempt

## Licence

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/> for the full licence text.

## Changelog

### v1.0.0 — 2026-07-11

- Initial release
