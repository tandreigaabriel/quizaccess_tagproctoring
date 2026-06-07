<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * License management for TAG Proctoring.
 *
 * @package    quizaccess_tagproctoring
 * @copyright  2026 Andrei Toma <https://www.tandreig.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tagproctoring;

defined('MOODLE_INTERNAL') || die();

/**
 * License check, activation and renewal.
 *
 * Token is stored as JSON in Moodle's config_plugins table under
 * quizaccess_tagproctoring / license_token.
 *
 * Token object shape:
 *   key          string  License key as entered by admin
 *   plan         string  'trial' | 'paid'
 *   domain       string  $CFG->wwwroot host at activation time
 *   expires_at   int     Unix timestamp when token expires
 *   grace_until  int     Unix timestamp end of grace period (expires_at + 7 days)
 *   activated_at int     Unix timestamp of first activation
 *   local        bool    true = activated locally (license server not yet reachable)
 *
 * To bypass all license checks during development, add to Moodle's config.php:
 *   define('TAGPROCTORING_LICENSE_DEV', true);
 */
class license {

    /** Remote activation endpoint — point this at your license server when live. */
    const ACTIVATION_ENDPOINT = 'https://www.tandreig.com/api/license/activate';

    /** Remote renewal endpoint. */
    const RENEWAL_ENDPOINT = 'https://www.tandreig.com/api/license/renew';

    /** Days of grace period after token expires before plugin is disabled. */
    const GRACE_DAYS = 7;

    /** Status: license is valid and not expired. */
    const STATUS_VALID = 'valid';

    /** Status: token has expired but within the grace window — plugin still works. */
    const STATUS_GRACE = 'grace';

    /** Status: token has expired and grace period has passed — plugin disabled. */
    const STATUS_EXPIRED = 'expired';

    /** Status: no license key or token stored — plugin disabled. */
    const STATUS_UNLICENSED = 'unlicensed';

    /**
     * Return the current license status.
     *
     * @return string One of the STATUS_* constants.
     */
    public static function get_status() {
        // Dev bypass: add define('TAGPROCTORING_LICENSE_DEV', true); to config.php.
        if (defined('TAGPROCTORING_LICENSE_DEV') && TAGPROCTORING_LICENSE_DEV) {
            return self::STATUS_VALID;
        }

        $tokenjson = get_config('quizaccess_tagproctoring', 'license_token');
        if (empty($tokenjson)) {
            return self::STATUS_UNLICENSED;
        }

        $token = json_decode($tokenjson);
        if (!$token || empty($token->expires_at)) {
            return self::STATUS_UNLICENSED;
        }

        $now = time();

        if ($token->expires_at > $now) {
            return self::STATUS_VALID;
        }

        $graceuntil = isset($token->grace_until) ? (int) $token->grace_until : 0;
        if ($graceuntil > $now) {
            return self::STATUS_GRACE;
        }

        return self::STATUS_EXPIRED;
    }

    /**
     * Return the stored token object, or null if none.
     *
     * @return object|null
     */
    public static function get_info() {
        $tokenjson = get_config('quizaccess_tagproctoring', 'license_token');
        if (empty($tokenjson)) {
            return null;
        }
        return json_decode($tokenjson);
    }

    /**
     * Activate a license key.
     *
     * Calls the remote activation API. If the server is unreachable (not yet live),
     * falls back to a local trial token valid for 60 days. Remove the fallback block
     * once the license server is deployed.
     *
     * @param string $key License key entered by the admin.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function activate($key) {
        global $CFG;

        $key = trim($key);

        if (empty($key)) {
            set_config('license_token', '', 'quizaccess_tagproctoring');
            return ['success' => false, 'message' => ''];
        }

        $domain = parse_url($CFG->wwwroot, PHP_URL_HOST);

        $payload = json_encode([
            'key'            => $key,
            'domain'         => $domain,
            'moodle_version' => $CFG->version,
        ]);

        $response = self::http_post(self::ACTIVATION_ENDPOINT, $payload);

        if ($response && isset($response->status) && $response->status === 'ok') {
            // Server responded successfully.
            $token = (object) [
                'key'          => $key,
                'plan'         => $response->plan,
                'domain'       => $domain,
                'expires_at'   => (int) $response->expires_at,
                'grace_until'  => (int) $response->expires_at + (self::GRACE_DAYS * 86400),
                'activated_at' => time(),
                'local'        => false,
            ];
            set_config('license_token', json_encode($token), 'quizaccess_tagproctoring');
            return [
                'success' => true,
                'message' => get_string('license_activated', 'quizaccess_tagproctoring'),
            ];
        }

        if ($response && isset($response->status) && $response->status === 'error') {
            // Server rejected the key (invalid, revoked, wrong domain, etc.).
            $msg = isset($response->message) ? $response->message
                : get_string('license_invalid_key', 'quizaccess_tagproctoring');
            return ['success' => false, 'message' => $msg];
        }

        // -------------------------------------------------------------------------
        // TODO: Remove this fallback block once the license server is live.
        // Until then, any non-empty key generates a local 60-day trial token so
        // development and early testing are not blocked.
        // -------------------------------------------------------------------------
        $token = (object) [
            'key'          => $key,
            'plan'         => 'trial',
            'domain'       => $domain,
            'expires_at'   => time() + (60 * 86400),
            'grace_until'  => time() + ((60 + self::GRACE_DAYS) * 86400),
            'activated_at' => time(),
            'local'        => true,
        ];
        set_config('license_token', json_encode($token), 'quizaccess_tagproctoring');
        return [
            'success' => true,
            'message' => get_string('license_trial_local', 'quizaccess_tagproctoring'),
        ];
    }

    /**
     * Renew the current token. Called by the weekly scheduled task.
     *
     * Skips renewal if the token still has more than 7 days remaining.
     *
     * @return bool True on success or if renewal not yet needed.
     */
    public static function renew() {
        $info = self::get_info();
        if (!$info || empty($info->key)) {
            return false;
        }

        // No need to renew if plenty of time remains.
        if (!empty($info->expires_at) && ($info->expires_at - time()) > (7 * 86400)) {
            return true;
        }

        return self::activate($info->key)['success'];
    }

    /**
     * POST a JSON body to a URL and return the decoded response, or null on failure.
     *
     * @param string $url
     * @param string $jsonbody
     * @return object|null
     */
    private static function http_post($url, $jsonbody) {
        if (!function_exists('curl_init')) {
            return null;
        }
        $curl = curl_init($url);
        if (!$curl) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonbody,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($curl);
        $err = curl_errno($curl);
        curl_close($curl);
        if ($err || !$raw) {
            return null;
        }
        return json_decode($raw);
    }
}
