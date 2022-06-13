<?php
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

namespace local_warpwire;

require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

class utilities {
    public static function errorLogLong($message, $prefix) {
        if (!is_string($message)) {
            $message = json_encode($message, \JSON_PRETTY_PRINT);
        }

        foreach (explode("\n", $message) as $line) {
            foreach( str_split($line, 100) as $chunk) {
                error_log('[' . $prefix . '] ' . $chunk);
            }
        }
    }

    public static function stdoutLogLong($message, $prefix) {
        if (!is_string($message)) {
            $message = json_encode($message, \JSON_PRETTY_PRINT);
        }

        foreach (explode("\n", $message) as $line) {
            foreach( str_split($line, 100) as $chunk) {
                print('[' . $prefix . '] ' . $chunk . "\n");
                error_log('[' . $prefix . '] ' . $chunk);
            }
        }
    }

    public static function logLong($message, $prefix, $useStdout) {
        if ($useStdout) {
            self::stdoutLogLong($message, $prefix);
        } else {
            self::errorLogLong($message, $prefix);
        }
    }

    public static function makeGetRequest($url, $token = null, $useStdout = false) {
        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        self::logLong('GET ' . $url, 'WARPWIRE', $useStdout);

        if ($token !== null) {
            curl_setopt($ch, \CURLOPT_HTTPHEADER, ['x-auth-wwtoken: ' . $token]);
        }

        $result = curl_exec($ch);
        $responseCode = intval(\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE));

        \curl_close($ch);

        if ($responseCode < 200 || $responseCode >= 300) {
            throw new \Exception('GET request failed due to response code: ' . $responseCode . '; body: ' . $result, $responseCode);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception('GET request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result, $responseCode);
        }

        return $decoded;
    }

    public static function makePostRequest($url, $body, $authUser = '', $authPassword = '', $useStdout = false) {
        $headers = [
            'Content-Type: application/json'
        ];

        if (!empty($authUser)) {
            $headers[] = 'Authorization: Basic ' . base64_encode($authUser . ':' . $authPassword);
        }

        $ch = curl_init($url);
        \curl_setopt_array($ch, [
            \CURLOPT_URL => $url,
            \CURLOPT_POST => 1,
            \CURLOPT_RETURNTRANSFER => 1,
            \CURLOPT_POSTFIELDS => \json_encode($body),
            \CURLOPT_HTTPHEADER => $headers
        ]);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        self::logLong('POST ' . $url, 'WARPWIRE', $useStdout);

        $result = curl_exec($ch);
        $responseCode = intval(\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE));

        \curl_close($ch);

        if ($responseCode < 200 || $responseCode >= 300) {
            throw new \Exception('POST request failed due to response code: ' . $responseCode . '; body: ' . $result, $responseCode);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception('POST request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result, $responseCode);
        }

        return $decoded;
    }

    public static function makeAuthenticatedGetRequest($url, $useStdout = false) {
        if (!self::isFullConfigured()) {
            throw new \Exception('Site is not configured to connect to Warpwire');
        }

        $authToken = get_config('local_warpwire', 'warpwire_auth_token');
        if (empty($authToken)) {
            $authToken = self::authorize();
            set_config('warpwire_auth_token', $authToken, 'local_warpwire');
        }

        try {
            $result = self::makeGetRequest($url, $authToken, $useStdout);
        } catch(\Exception $ex) {
            if ($ex->getCode() === 401) {
                $authToken = self::authorize();
                set_config('warpwire_auth_token', $authToken, 'local_warpwire');

                $result = self::makeGetRequest($url, $authToken, $useStdout);
            } else {
                throw $ex;
            }
        }

        return $result;
    }

    public static function isConfigured() {
        $allConfig = (array)get_config('local_warpwire');
        return !empty($allConfig['warpwire_lti']) &&
               !empty($allConfig['warpwire_key']) &&
               !empty($allConfig['warpwire_secret']);
    }

    public static function isFullConfigured() {
        $allConfig = (array)get_config('local_warpwire');
        return !empty($allConfig['warpwire_lti']) &&
               !empty($allConfig['warpwire_key']) &&
               !empty($allConfig['warpwire_secret']) &&
               !empty($allConfig['warpwire_url']) &&
               !empty($allConfig['warpwire_admin_username']) &&
               !empty($allConfig['warpwire_admin_password']);
    }

    public static function setConfigLog($name, $value) {
        $oldValue = get_config('local_warpwire', $name);
        set_config($name, $value, 'local_warpwire');
        add_to_config_log($name, $oldValue, $value, 'local_warpwire');
    }

    public static function setupLtiTool($enabled, $useStdout = false) {
        global $CFG;

        try {
            $existingLtiIds = [];
            $existingTypes = \lti_get_lti_types();
            foreach ($existingTypes as $existingType) {
                if ($existingType->name === 'Warpwire Graded Activity') {
                    $existingLtiIds[] = $existingType->id;
                }
            }

            if (preg_match('!^https?://(localhost|127.0.0.1)!', $CFG->wwwroot)) {
                $icon = '/local/warpwire/pix/icon.png';
            } else {
                $icon = $CFG->wwwroot . '/local/warpwire/pix/icon.png';
            }

            $toolUrl = get_config('local_warpwire', 'warpwire_url') . 'api/ltix/';

            $data = (object)[
                'lti_typename' => 'Warpwire Graded Activity',
                'lti_toolurl' => $toolUrl,
                'lti_description' => get_string('lti_tool_description', 'local_warpwire'),
                'lti_version' => \LTI_VERSION_1,
                'lti_resourcekey' => get_config('local_warpwire', 'warpwire_key'),
                'lti_password' => get_config('local_warpwire', 'warpwire_secret'),
                'lti_clientid' => '',
                'lti_keytype' => \LTI_JWK_KEYSET,
                'lti_customparameters' => '',
                'lti_coursevisible' => $enabled ? \LTI_COURSEVISIBLE_ACTIVITYCHOOSER : \LTI_COURSEVISIBLE_NO,
                'typeid' => 1,
                'lti_launchcontainer' => \LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'lti_contentitem' => '1',
                'lti_toolurl_ContentItemSelection' => $toolUrl,
                'oldicon' => $icon,
                'lti_icon' => $icon,
                'ltiservice_gradesynchronization' => '2',
                'ltiservice_memberships' => '1',
                'ltiservice_toolsettings' => '1',
                'lti_sendname' => \LTI_SETTING_ALWAYS,
                'lti_sendemailaddr' => \LTI_SETTING_ALWAYS,
                'lti_acceptgrades' => \LTI_SETTING_ALWAYS,
                'lti_organizationid_default' => 'SITEID',
                'lti_organizationid' => '',
                'lti_organizationurl' => '',
                'tab' => '',
                'course' => 1
            ];

            if (!empty($existingLtiIds)) {
                foreach ($existingLtiIds as $existingLtiId) {
                    \local_warpwire\utilities::logLong('Updating existing Warpwire LTI type (id: ' . $existingLtiId . ')', 'WARPWIRE LTI', $useStdout);
                    $type = new \stdClass();
                    $type->state = \LTI_TOOL_STATE_CONFIGURED;
                    $type->id = $existingLtiId;
                    \lti_load_type_if_cartridge($data);
                    \lti_update_type($type, $data);
                }
            } else {
                \local_warpwire\utilities::logLong('Creating new Warpwire LTI type', 'WARPWIRE LTI', $useStdout);
                $type = new \stdClass();
                $type->state = \LTI_TOOL_STATE_CONFIGURED;
                \lti_load_type_if_cartridge($data);
                \lti_add_type($type, $data);
            }
        } catch(\Throwable $ex) {
            \local_warpwire\utilities::logLong('Failed to configure LTI tool: ' . $ex, 'WARPWIRE LTI', $useStdout);
        }
    }

    public static function removeLtiTool($useStdout = false) {
        try {
            $existingLtiId = null;
            $existingTypes = \lti_get_lti_types();
            foreach ($existingTypes as $existingType) {
                if ($existingType->name === 'Warpwire Graded Activity') {
                    $existingLtiId = $existingType->id;
                    \local_warpwire\utilities::logLong('Removing Warpwire LTI type with id ' . $existingLtiId, 'WARPWIRE LTI', $useStdout);
                    \lti_delete_type($existingLtiId);
                }
            }

            if ($existingLtiId === null) {
                \local_warpwire\utilities::logLong('No LTI tool to remove', 'WARPWIRE LTI', $useStdout);
            }
        } catch(\Throwable $ex) {
            \local_warpwire\utilities::logLong('Failed to remove LTI tool: ' . $ex, 'WARPWIRE LTI', $useStdout);
        }
    }

    private static function authorize() {
        $baseUrl = get_config('local_warpwire', 'warpwire_url');
        $adminUsername = get_config('local_warpwire', 'warpwire_admin_username');
        $adminPassword = get_config('local_warpwire', 'warpwire_admin_password');

        $auth = self::makePostRequest("{$baseUrl}api/authenticate/", [], $adminUsername, $adminPassword);
        if (!is_array($auth) || !isset($auth['token'])) {
            throw new \Exception('Could not retrieve auth token');
        }

        return $auth['token'];
    }
}
