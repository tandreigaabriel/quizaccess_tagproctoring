# Moodle Local Plugin Development Guide

This guide is derived from `local_examnotice` and serves as the canonical reference for building new local plugins in this project. Follow every convention here unless there is a documented reason to deviate.

---

## Table of Contents

1. [Directory Structure](#1-directory-structure)
2. [version.php](#2-versionphp)
3. [Namespacing and Class Naming](#3-namespacing-and-class-naming)
4. [Language Strings](#4-language-strings)
5. [Mustache Templates](#5-mustache-templates)
6. [AMD JavaScript Modules](#6-amd-javascript-modules)
7. [External API / Web Services](#7-external-api--web-services)
8. [Hooks](#8-hooks)
9. [Database — install.xml and upgrade.php](#9-database--installxml-and-upgradephp)
10. [Capabilities — db/access.php](#10-capabilities--dbaccessphp)
11. [Settings and Admin Pages](#11-settings-and-admin-pages)
12. [lib.php](#12-libphp)
13. [CSS / Styling](#13-css--styling)
14. [Privacy API](#14-privacy-api)
15. [Configuration Pattern](#15-configuration-pattern)
16. [Database Query Pattern](#16-database-query-pattern)
17. [Capability Guard Pattern](#17-capability-guard-pattern)
18. [Quick Reference Cheatsheet](#18-quick-reference-cheatsheet)

---

## 1. Directory Structure

Every local plugin must follow this exact layout. Add directories only when the corresponding feature exists.

```
local/pluginname/
├── admin/
│   └── manage.php              # Admin/settings UI page
├── amd/
│   ├── src/
│   │   └── module.js           # Source AMD modules (one per concern)
│   └── build/
│       ├── module.min.js       # Compiled output (grunt/webpack)
│       └── module.min.js.map   # Source map
├── classes/
│   ├── external/
│   │   └── action_name.php     # One file per web service method
│   ├── form/
│   │   └── thing_form.php      # Moodle form classes
│   ├── hook/
│   │   └── hook_name.php       # Hook callback classes
│   └── privacy/
│       └── provider.php        # GDPR — always required
├── db/
│   ├── access.php              # Capability definitions
│   ├── hooks.php               # Hook registrations
│   ├── install.xml             # XMLDB schema
│   ├── services.php            # Web service definitions
│   └── upgrade.php             # Migration script
├── lang/
│   └── en/
│       └── local_pluginname.php
├── templates/
│   └── thing.mustache
├── lib.php
├── settings.php
├── styles.css
└── version.php
```

Rules:

- Plugin type is always `local`, so the component string is `local_pluginname`.
- `admin/` holds pages reachable via the Moodle admin menu. Never put admin logic in the plugin root.
- `classes/` follows PSR-4 autoloading via Moodle's class map — the directory path mirrors the namespace.
- `amd/src/` is the authoring location. `amd/build/` is generated — never hand-edit it.
- `templates/` holds only `.mustache` files.

---

## 2. version.php

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_pluginname';   // Must match directory name exactly
$plugin->version   = 2026040400;           // YYYYMMDDNN — increment NN for same-day releases
$plugin->requires  = 2024042200;           // Minimum Moodle build (4.4 = 2024042200, 4.5 = 2024100700)
$plugin->release   = '1.0.0';             // Semantic version shown in plugin manager
$plugin->maturity  = MATURITY_STABLE;     // MATURITY_ALPHA | MATURITY_BETA | MATURITY_RC | MATURITY_STABLE
```

Version number rules:

- Format is `YYYYMMDDNN` where `NN` starts at `00` and increments per release on the same day.
- Bump version any time `db/install.xml` or `db/upgrade.php` changes.
- The `$plugin->requires` value must match the actual Moodle build number on the server.

---

## 3. Namespacing and Class Naming

### Namespace pattern

```
local_pluginname\category\ClassName
```

| Category      | Namespace segment | Example                                    |
| ------------- | ----------------- | ------------------------------------------ |
| Web service   | `external`        | `local_pluginname\external\dismiss_notice` |
| Moodle form   | `form`            | `local_pluginname\form\notice_form`        |
| Hook callback | `hook`            | `local_pluginname\hook\before_footer`      |
| Privacy       | `privacy`         | `local_pluginname\privacy\provider`        |

### Class naming

- Classes use **PascalCase**: `DismissNotice`, `NoticeForm`, `BeforeFooter`.
- File names use **snake_case** matching the class name lowercased: `dismiss_notice.php`, `notice_form.php`.
- The file path under `classes/` must mirror the namespace exactly.

### Function naming (lib.php only)

Free functions defined in `lib.php` are not namespaced. Use:

```
local_pluginname_verb_noun()
```

Examples: `local_examnotice_default_content()`, `local_examnotice_build_modal_html()`.

Never put business logic in free functions. Reserve `lib.php` for Moodle callback hooks and small utility functions that cannot live in a class.

---

## 4. Language Strings

**File:** `lang/en/local_pluginname.php`

Every visible string in the plugin must come from this file. No hardcoded English text in PHP or JavaScript (use `get_string()` / Mustache `{{#str}}`).

### Naming convention

```
[context]_[item]
[context]_[item]_desc       ← description shown under a setting
[context]_[item]_error      ← validation/error message variant
```

### Required strings

Every plugin must define these at minimum:

```php
$string['pluginname']   = 'My Plugin';          // Shown in plugin manager, breadcrumbs
$string['settings']     = 'My Plugin settings';
$string['manage']       = 'Manage My Plugin';
```

### Capability strings

```php
// Pattern: capability action portion only — Moodle prepends context automatically
$string['pluginname:manage'] = 'Manage plugin settings';
$string['pluginname:view']   = 'View plugin content';
```

### Privacy strings

```php
$string['privacy:metadata:local_pluginname_tablename']          = 'Description of the table.';
$string['privacy:metadata:local_pluginname_tablename:fieldname'] = 'Description of the field.';
```

One string per field in every table that stores personal data.

### Grouping strategy

Organise strings in the file by category with a comment header:

```php
// Core plugin
$string['pluginname'] = '...';

// Capabilities
$string['pluginname:view'] = '...';

// Settings
$string['enabled']      = '...';
$string['enabled_desc'] = '...';

// UI elements (buttons, labels)
$string['confirm_read']  = '...';
$string['remind_later']  = '...';

// Errors
$string['error_notfound'] = '...';

// Privacy
$string['privacy:metadata:...'] = '...';
```

---

## 5. Mustache Templates

**Location:** `templates/name.mustache`
**Reference in PHP:** `'local_pluginname/name'` (matches filename without extension)

### Syntax reference

| Syntax                                   | Purpose                                                 |
| ---------------------------------------- | ------------------------------------------------------- |
| `{{variable}}`                           | Output a variable (HTML-escaped)                        |
| `{{{variable}}}`                         | Output raw HTML — only for trusted, pre-escaped content |
| `{{#variable}} ... {{/variable}}`        | Render block if truthy                                  |
| `{{^variable}} ... {{/variable}}`        | Render block if falsy (default/fallback)                |
| `{{#str}} stringkey, component {{/str}}` | Moodle i18n string lookup                               |
| `{{! comment }}`                         | Template comment (not rendered)                         |

### Default value pattern

```mustache
{{title}}{{^title}}Default Title Text{{/title}}
```

### Button IDs

JavaScript hooks into buttons by ID. IDs must be camelCase and unique enough to avoid collisions:

```mustache
<button id="myPluginConfirm">...</button>
<button id="myPluginDismiss">...</button>
```

Prefix every ID with the plugin name in camelCase (`examNotice`, `myPlugin`).

### Bootstrap compatibility

Support both Bootstrap 4 and 5 by including both attribute variants:

```mustache
data-bs-backdrop="static" data-backdrop="static"
data-bs-keyboard="false"  data-keyboard="false"
```

### Template boilerplate (modal)

```mustache
{{! Modal template for local_pluginname }}
<div class="modal fade" id="myPluginModal" tabindex="-1" role="dialog"
     data-bs-backdrop="static" data-backdrop="static"
     data-bs-keyboard="false"  data-keyboard="false"
     aria-labelledby="myPluginModalLabel" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">

      <div class="modal-header mp-header">
        <h4 class="modal-title mp-title" id="myPluginModalLabel">
          {{title}}{{^title}}{{#str}}default_title, local_pluginname{{/str}}{{/title}}
        </h4>
      </div>

      <div class="modal-body" style="max-height:62vh; overflow-y:auto;">
        {{{content}}}
      </div>

      <div class="modal-footer mp-footer">
        <button type="button" id="myPluginDismiss" class="btn mp-btn-dismiss">
          {{#str}}remind_later, local_pluginname{{/str}}
        </button>
        <button type="button" id="myPluginConfirm" class="btn mp-btn-confirm">
          {{#str}}confirm_read, local_pluginname{{/str}}
        </button>
      </div>

    </div>
  </div>
</div>
```

---

## 6. AMD JavaScript Modules

**Source:** `amd/src/module.js`
**Build:** `amd/build/module.min.js` (generated — never edit manually)
**Reference in PHP:** `'local_pluginname/module'`

### Module boilerplate

```javascript
define(["jquery", "core/log", "core/ajax"], function ($, Log, Ajax) {
  /**
   * Show the modal, with Bootstrap 5 → 4 → CSS fallback.
   */
  function show(modalEl) {
    if (window.bootstrap && window.bootstrap.Modal) {
      new window.bootstrap.Modal(modalEl).show();
    } else if (typeof $ !== "undefined" && $.fn.modal) {
      $(modalEl).modal("show");
    } else {
      modalEl.style.display = "block";
      modalEl.classList.add("show");
    }
  }

  function hide(modalEl) {
    if (window.bootstrap && window.bootstrap.Modal) {
      window.bootstrap.Modal.getInstance(modalEl).hide();
    } else if (typeof $ !== "undefined" && $.fn.modal) {
      $(modalEl).modal("hide");
    } else {
      modalEl.style.display = "none";
      modalEl.classList.remove("show");
    }
  }

  /**
   * Call a Moodle web service via AJAX.
   *
   * @param {string} methodname  Web service function name
   * @param {object} args
   * @returns {Promise}
   */
  function callService(methodname, args) {
    return Ajax.call([{ methodname: methodname, args: args }])[0];
  }

  return {
    /**
     * Entry point called by PHP via $PAGE->requires->js_call_amd().
     *
     * @param {object} params
     * @param {number} params.itemid   - The record ID
     * @param {boolean} params.ispreview - True in admin preview
     */
    init: function (params) {
      var modalEl = document.getElementById("myPluginModal");
      if (!modalEl) {
        Log.error("local_pluginname/module: modal element not found");
        return;
      }

      // Wire up buttons using event delegation
      modalEl.addEventListener("click", function (e) {
        var target = e.target.closest("#myPluginConfirm, #myPluginDismiss");
        if (!target) {
          return;
        }
        if (target.id === "myPluginConfirm") {
          handleConfirm(params, modalEl);
        } else {
          hide(modalEl);
          if (!params.ispreview) {
            callService("local_pluginname_dismiss", {
              itemid: params.itemid,
              action: "dismiss",
            });
          }
        }
      });

      if (!params.ispreview) {
        show(modalEl);
      }
    },
  };

  function handleConfirm(params, modalEl) {
    if (params.ispreview) {
      hide(modalEl);
      return;
    }
    var btn = document.getElementById("myPluginConfirm");
    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Saving\u2026";

    callService("local_pluginname_confirm", {
      itemid: params.itemid,
      action: "confirm",
    })
      .then(function () {
        hide(modalEl);
      })
      .catch(function (err) {
        Log.error(err);
        btn.disabled = false;
        btn.textContent = originalText;
      });
  }
});
```

### Invocation from PHP

```php
$PAGE->requires->js_call_amd('local_pluginname/module', 'init', [[
    'itemid'    => (int) $record->id,
    'ispreview' => false,
]]);
```

Rules:

- The third argument to `js_call_amd` is always an **array containing one array** (array of argument arrays).
- Cast all integers explicitly with `(int)`.
- Never pass sensitive data (tokens, passwords) through AMD params.
- Use `core/log` instead of `console.log` directly.
- Use event delegation on the container element, not direct listeners on buttons.

### Building AMD modules

Run grunt from the Moodle root after editing source files:

```bash
grunt amd --root=local/pluginname
```

Or to watch for changes:

```bash
grunt watch
```

---

## 7. External API / Web Services

### File location

`classes/external/action_name.php` — one class per logical action.

### Class boilerplate

```php
<?php
namespace local_pluginname\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\course as context_course;

class action_name extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT,   'The item ID'),
            'action' => new external_value(PARAM_ALPHA, 'Action: confirm or dismiss'),
        ]);
    }

    public static function execute(int $itemid, string $action): array {
        global $DB, $USER;

        // 1. Validate and clean parameters
        ['itemid' => $itemid, 'action' => $action] =
            self::validate_parameters(self::execute_parameters(), [
                'itemid' => $itemid,
                'action' => $action,
            ]);

        // 2. Validate allowed action values
        if (!in_array($action, ['confirm', 'dismiss'], true)) {
            throw new \invalid_parameter_exception('Invalid action');
        }

        // 3. Fetch record and context
        $item = $DB->get_record('some_table', ['id' => $itemid], '*', MUST_EXIST);
        $context = context_course::instance($item->courseid);

        // 4. Validate session and capability
        self::validate_context($context);
        require_capability('local/pluginname:view', $context);

        // 5. Business logic
        $status = ($action === 'confirm') ? 1 : 0;
        $existing = $DB->get_record('local_pluginname_seen', [
            'userid' => $USER->id,
            'itemid' => $itemid,
        ]);

        if ($existing) {
            if ($status > $existing->status) {
                $DB->update_record('local_pluginname_seen', (object)[
                    'id'           => $existing->id,
                    'status'       => $status,
                    'timemodified' => time(),
                ]);
            }
        } else {
            $DB->insert_record('local_pluginname_seen', (object)[
                'userid'       => $USER->id,
                'itemid'       => $itemid,
                'status'       => $status,
                'timecreated'  => time(),
                'timemodified' => time(),
            ]);
        }

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
        ]);
    }
}
```

### Registration in db/services.php

```php
$functions = [
    'local_pluginname_action_name' => [
        'classname'     => 'local_pluginname\external\action_name',
        'methodname'    => 'execute',
        'description'   => 'One sentence describing what this does.',
        'type'          => 'write',    // 'read' or 'write'
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
```

Function name format: `local_pluginname_action_name` (all snake_case).

---

## 8. Hooks

### Hook callback class

```php
<?php
namespace local_pluginname\hook;

defined('MOODLE_INTERNAL') || die();

class before_footer {

    public static function callback(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE, $USER, $COURSE, $DB, $OUTPUT;

        // Guard: feature disabled
        if (!get_config('local_pluginname', 'enabled')) {
            return;
        }

        // Guard: wrong page context
        if ($PAGE->context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        // Guard: skip admins and teachers
        $context = $PAGE->context;
        if (is_siteadmin() || has_capability('mod/quiz:grade', $context)) {
            return;
        }

        // Guard: must have view capability
        if (!has_capability('local/pluginname:view', $context)) {
            return;
        }

        // Guard: must be enrolled
        if (!is_enrolled($context, $USER->id, '', true)) {
            return;
        }

        // Business logic — build HTML, inject it
        $html = $OUTPUT->render_from_template('local_pluginname/modal', $templatecontext);
        $hook->add_html($html);
        $PAGE->requires->js_call_amd('local_pluginname/module', 'init', [[
            'itemid'    => (int) $record->id,
            'ispreview' => false,
        ]]);
    }
}
```

### Registration in db/hooks.php

```php
$callbacks = [
    [
        'hook'     => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_pluginname\hook\before_footer::class . '::callback',
    ],
];
```

Rules:

- Hook classes have no constructor and no state — all methods are `static`.
- Always return early (guard pattern) instead of nesting logic.
- Order guards from cheapest (config check) to most expensive (DB queries).
- Call `$hook->add_html()` only once per invocation.

---

## 9. Database — install.xml and upgrade.php

### install.xml conventions

- Table name: `local_pluginname_noun` (plugin prefix + descriptive noun).
- Every table must have: `id` (sequence), `timecreated`, `timemodified`.
- Foreign keys to Moodle core tables (`userid`, `courseid`, `quizid`) are `int(10) NOT NULL DEFAULT 0`.
- Status/flag columns: `int(2) NOT NULL DEFAULT 0`.
- Unique constraints expressed as `<INDEX UNIQUE="true">` on the relevant column combination.

Minimal table skeleton:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/pluginname/db" VERSION="20260404" COMMENT="XMLDB file for local/pluginname"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="../../../lib/xmldb/xmldb.xsd">
  <TABLES>
    <TABLE NAME="local_pluginname_seen" COMMENT="Tracks which items a user has seen">
      <FIELDS>
        <FIELD NAME="id"           TYPE="int"  LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="userid"       TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="itemid"       TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="courseid"     TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="status"       TYPE="int"  LENGTH="2"  NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="timecreated"  TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="timemodified" TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="userid_itemid" UNIQUE="true" FIELDS="userid, itemid"/>
        <INDEX NAME="userid"        UNIQUE="false" FIELDS="userid"/>
        <INDEX NAME="itemid"        UNIQUE="false" FIELDS="itemid"/>
      </INDEXES>
    </TABLE>
  </TABLES>
</XMLDB>
```

### upgrade.php pattern

```php
<?php
function xmldb_local_pluginname_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026040400) {
        $table = new xmldb_table('local_pluginname_seen');

        $field = new xmldb_field('newcolumn', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026040400, 'local', 'pluginname');
    }

    return true;
}
```

Rules:

- Each migration block checks `$oldversion < VERSION_NUMBER`.
- Always guard schema operations with `field_exists()` / `table_exists()`.
- Version number in `savepoint` must match the corresponding entry in `version.php`.
- Return `true` at the end.

---

## 10. Capabilities — db/access.php

```php
$capabilities = [

    'local/pluginname:manage' => [
        'riskbitmask' => RISK_CONFIG,
        'captype'     => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'  => [
            'manager' => CAP_ALLOW,
        ],
    ],

    'local/pluginname:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'student' => CAP_ALLOW,
        ],
    ],

];
```

Rules:

- Capability name format: `local/pluginname:action` (the plugin type and name before the colon, action after).
- Use `CONTEXT_SYSTEM` for site-wide admin operations, `CONTEXT_COURSE` for per-course access.
- Only grant to the minimum required archetype. Teachers and managers inherit from roles above student.
- Use `RISK_CONFIG` for capabilities that change plugin behaviour; omit `riskbitmask` for read-only caps.

---

## 11. Settings and Admin Pages

### settings.php (menu registration)

```php
<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_pluginname_manage',
            get_string('pluginname', 'local_pluginname'),
            new moodle_url('/local/pluginname/admin/manage.php'),
            'local/pluginname:manage'
        )
    );
}
```

### admin/manage.php (settings page)

```php
<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/pluginname:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/pluginname/admin/manage.php'));
$PAGE->set_title(get_string('manage', 'local_pluginname'));
$PAGE->set_heading(get_string('admin_page_heading', 'local_pluginname'));

$form = new \local_pluginname\form\thing_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/'));
}

if ($data = $form->get_data()) {
    set_config('enabled',     (int) $data->enabled,     'local_pluginname');
    set_config('days_before', (int) $data->days_before, 'local_pluginname');
    redirect($PAGE->url, get_string('savechanges', 'local_pluginname'), 2);
}

// Pre-populate form with saved config
$form->set_data([
    'enabled'     => get_config('local_pluginname', 'enabled')     ?: 0,
    'days_before' => get_config('local_pluginname', 'days_before') ?: 7,
]);

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
```

### Form class boilerplate

```php
<?php
namespace local_pluginname\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class thing_form extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('settings', 'local_pluginname'));

        $mform->addElement('advcheckbox', 'enabled',
            get_string('enabled', 'local_pluginname'));
        $mform->setType('enabled', PARAM_INT);

        $mform->addElement('text', 'days_before',
            get_string('days_before', 'local_pluginname'));
        $mform->setType('days_before', PARAM_INT);
        $mform->addRule('days_before', null, 'required', null, 'client');
        $mform->addHelpButton('days_before', 'days_before', 'local_pluginname');

        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!is_numeric($data['days_before']) || $data['days_before'] < 1 || $data['days_before'] > 90) {
            $errors['days_before'] = get_string('days_before_range_error', 'local_pluginname');
        }

        return $errors;
    }
}
```

---

## 12. lib.php

Keep `lib.php` thin. It is loaded on every page request when Moodle resolves callbacks — bloated code here degrades site performance.

Allowed in `lib.php`:

- Moodle callback functions (e.g., `local_pluginname_extend_navigation`)
- Small utility/helper functions used by multiple classes

Not allowed in `lib.php`:

- Classes (put them in `classes/`)
- Database queries beyond simple lookups
- Any logic that could be encapsulated in a class method

### Rendering helpers pattern

```php
function local_pluginname_build_modal_html(object $data, string $content): string {
    global $OUTPUT;
    return $OUTPUT->render_from_template('local_pluginname/modal', [
        'title'   => format_string($data->title),
        'name'    => format_string($data->name),
        'days'    => (int) $data->days,
        'content' => $content,
    ]);
}

function local_pluginname_default_content(): string {
    $setupurl = get_config('local_pluginname', 'setup_url') ?: '#';
    return html_writer::tag('p', 'Please review the following before your exam:') .
           html_writer::start_tag('ul') .
           html_writer::tag('li', html_writer::link($setupurl, 'Setup guide')) .
           html_writer::end_tag('ul');
}
```

---

## 13. CSS / Styling

**File:** `styles.css` (one file per plugin — Moodle auto-loads it)

### Scoping rule

**Every selector must be scoped** under the plugin's unique modal or container ID/class to prevent conflicts with Moodle core or other plugins.

```css
/* BAD — global scope */
.modal-header {
  background: green;
}

/* GOOD — scoped */
#myPluginModal .modal-header {
  background: green;
}
#myPluginModal .mp-header {
  background: green;
}
```

### Naming convention for plugin-specific classes

Use a two-letter abbreviation of the plugin name as a prefix:

```
.mp-header      /* my plugin header */
.mp-title
.mp-badge
.mp-list
.mp-footer
.mp-btn-confirm
.mp-btn-dismiss
```

### Structure

```css
/* ========================================================
   local_pluginname — styles.css
   All rules scoped to #myPluginModal
   ======================================================== */

/* Header */
#myPluginModal .mp-header {
  background: linear-gradient(135deg, #1a4a1a, #2d7a2d);
  color: #fff;
  padding: 1.5rem 1.75rem;
}

/* Body */
#myPluginModal .modal-body {
  background: #fafffe;
  max-height: 62vh;
  overflow-y: auto;
}

/* Footer */
#myPluginModal .mp-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
}

/* Buttons */
#myPluginModal .mp-btn-confirm {
  background: linear-gradient(135deg, #1a4a1a, #2d7a2d);
  color: #fff;
  border: none;
  padding: 0.65rem 1.5rem;
  border-radius: 6px;
}

#myPluginModal .mp-btn-confirm:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(45, 122, 45, 0.4);
}

#myPluginModal .mp-btn-dismiss {
  background: transparent;
  color: #555;
  border: 1px solid #ccc;
  padding: 0.65rem 1.5rem;
  border-radius: 6px;
}
```

---

## 14. Privacy API

Every plugin that stores personal data must implement the Privacy API. This is not optional.

```php
<?php
namespace local_pluginname\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_pluginname_seen',
            [
                'userid'       => 'privacy:metadata:local_pluginname_seen:userid',
                'itemid'       => 'privacy:metadata:local_pluginname_seen:itemid',
                'status'       => 'privacy:metadata:local_pluginname_seen:status',
                'timecreated'  => 'privacy:metadata:local_pluginname_seen:timecreated',
                'timemodified' => 'privacy:metadata:local_pluginname_seen:timemodified',
            ],
            'privacy:metadata:local_pluginname_seen'
        );
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_pluginname_seen} s ON s.courseid = ctx.instanceid
                 WHERE s.userid = :userid
                   AND ctx.contextlevel = :contextlevel";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_COURSE]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $sql = "SELECT userid FROM {local_pluginname_seen} WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        // Export data per context — use writer API
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_course) {
            return;
        }
        $DB->delete_records('local_pluginname_seen', ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB, $USER;
        $DB->delete_records('local_pluginname_seen', ['userid' => $USER->id]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'u');
        $DB->delete_records_select('local_pluginname_seen', "userid $insql", $params);
    }
}
```

---

## 15. Configuration Pattern

### Saving settings

```php
set_config('setting_name', $value, 'local_pluginname');
```

### Reading settings (with defaults)

```php
// Boolean — default false
$enabled = (bool) get_config('local_pluginname', 'enabled');

// Integer — default 7
$days = (int) (get_config('local_pluginname', 'days_before') ?: 7);

// String — default empty
$title = get_config('local_pluginname', 'modal_title') ?: '';

// URL — default hash
$url = get_config('local_pluginname', 'setup_url') ?: '#';
```

Rules:

- Always provide a sensible default via `?: default`.
- Cast to the expected type explicitly (`(int)`, `(bool)`).
- Never use `get_config()` inside loops — cache the value in a local variable.

---

## 16. Database Query Pattern

### Parameterised queries

Always use **named parameters**. Never concatenate user data into SQL.

```php
$sql = "SELECT q.id, q.name, q.timeopen
          FROM {quiz} q
          JOIN {course_modules} cm ON cm.instance = q.id
                                   AND cm.module = :modid
         WHERE q.course = :courseid
           AND q.timeopen BETWEEN :now AND :cutoff
         ORDER BY q.timeopen ASC";

$params = [
    'modid'    => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
    'courseid' => $COURSE->id,
    'now'      => time(),
    'cutoff'   => time() + (DAYSECS * $days),
];

$quizzes = $DB->get_records_sql($sql, $params);
```

### IN clause

```php
$ids = [1, 2, 3];
[$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'qid');
$records = $DB->get_records_select('local_pluginname_seen', "quizid $insql", $inparams);
```

### Upsert pattern (check then insert or update)

```php
$existing = $DB->get_record('local_pluginname_seen', [
    'userid' => $USER->id,
    'itemid' => $itemid,
]);

if ($existing) {
    if ($newstatus > $existing->status) {   // Only escalate, never downgrade
        $DB->update_record('local_pluginname_seen', (object)[
            'id'           => $existing->id,
            'status'       => $newstatus,
            'timemodified' => time(),
        ]);
    }
} else {
    $DB->insert_record('local_pluginname_seen', (object)[
        'userid'       => $USER->id,
        'itemid'       => $itemid,
        'courseid'     => $COURSE->id,
        'status'       => $newstatus,
        'timecreated'  => time(),
        'timemodified' => time(),
    ]);
}
```

---

## 17. Capability Guard Pattern

Order guards from cheapest (no DB) to most expensive (DB query). Return early at each gate.

```php
// 1. Feature toggle (config cache — cheapest)
if (!get_config('local_pluginname', 'enabled')) {
    return;
}

// 2. Page/context type
if ($PAGE->context->contextlevel !== CONTEXT_COURSE) {
    return;
}

// 3. Admin bypass
if (is_siteadmin()) {
    return;
}

// 4. Role-based exclusions (has_capability uses cache)
$context = $PAGE->context;
if (has_capability('mod/quiz:grade', $context)) {
    return;   // Teachers see nothing
}

// 5. Positive capability check
if (!has_capability('local/pluginname:view', $context)) {
    return;
}

// 6. Enrollment check (DB hit — last resort)
if (!is_enrolled($context, $USER->id, '', true)) {
    return;
}

// All guards passed — do the work
```

---

## 18. Quick Reference Cheatsheet

| Item                 | Convention                            | Example                                     |
| -------------------- | ------------------------------------- | ------------------------------------------- |
| Component string     | `local_pluginname`                    | `local_examnotice`                          |
| Namespace root       | `local_pluginname\`                   | `local_examnotice\external\`                |
| Class names          | PascalCase                            | `DismissNotice`, `NoticeForm`               |
| File names (classes) | snake_case                            | `dismiss_notice.php`                        |
| Free functions       | `local_pluginname_verb_noun`          | `local_examnotice_default_content()`        |
| Lang file            | `lang/en/local_pluginname.php`        | —                                           |
| String keys          | `context_item` or `context_item_desc` | `days_before`, `days_before_desc`           |
| DB table prefix      | `local_pluginname_`                   | `local_examnotice_seen`                     |
| Capability format    | `local/pluginname:action`             | `local/examnotice:view`                     |
| Web service name     | `local_pluginname_action`             | `local_examnotice_dismiss_notice`           |
| Template reference   | `local_pluginname/name`               | `local_examnotice/modal`                    |
| AMD module reference | `local_pluginname/module`             | `local_examnotice/modal`                    |
| Version format       | `YYYYMMDDNN`                          | `2026040400`                                |
| Button IDs           | camelCase, plugin-prefixed            | `examNoticeConfirm`                         |
| CSS class prefix     | 2-letter abbreviation                 | `.en-header`, `.en-btn-confirm`             |
| Config scope         | `'local_pluginname'`                  | `get_config('local_examnotice', 'enabled')` |
| SQL params           | named (`:param`)                      | `WHERE id = :id`                            |
| AMD dependencies     | `["jquery", "core/log", "core/ajax"]` | —                                           |
| Hook registration    | `db/hooks.php` `$callbacks` array     | —                                           |
| Privacy provider     | always implement all 7 methods        | —                                           |
