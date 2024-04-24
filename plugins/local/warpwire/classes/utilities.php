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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

class utilities {
    /**
     * Makes a get request with curl.
     *
     * @param string $url
     * @param string|null $token
     * @return mixed
     */
    public static function make_get_request($url, $token = null) {
        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        if ($token !== null) {
            curl_setopt($ch, \CURLOPT_HTTPHEADER, ['x-auth-wwtoken: ' . $token]);
        }

        $result = curl_exec($ch);
        $responsecode = intval(\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE));

        \curl_close($ch);

        if ($responsecode < 200 || $responsecode >= 300) {
            throw new \Exception('GET request failed due to response code: ' . $responsecode . '; body: ' . $result, $responsecode);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception
            ('GET request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result, $responsecode);
        }

        return $decoded;
    }

    /**
     * Makes a post request with curl.
     *
     * @param string $url
     * @param mixed $body
     * @param string $authuser
     * @param string $authpassword
     */
    public static function make_post_request($url, $body, $authuser = '', $authpassword = '') {
        $headers = [
            'Content-Type: application/json',
        ];

        if (!empty($authuser)) {
            $headers[] = 'Authorization: Basic ' . base64_encode($authuser . ':' . $authpassword);
        }

        $ch = curl_init($url);
        \curl_setopt_array($ch, [
            \CURLOPT_URL => $url,
            \CURLOPT_POST => 1,
            \CURLOPT_RETURNTRANSFER => 1,
            \CURLOPT_POSTFIELDS => \json_encode($body),
            \CURLOPT_HTTPHEADER => $headers,
        ]);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $responsecode = intval(\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE));

        \curl_close($ch);

        if ($responsecode < 200 || $responsecode >= 300) {
            throw new \Exception
            ('POST request failed due to response code: ' . $responsecode . '; body: ' . $result, $responsecode);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception
            ('POST request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result, $responsecode);
        }

        return $decoded;
    }

    /**
     * Uses Warpwire auth token to make an authenticated request
     *
     * @param string $url
     * @return mixed $result
     */
    public static function make_authenticated_get_request($url) {
        if (!self::is_full_configured()) {
            throw new \Exception('Site is not configured to connect to Warpwire');
        }

        $authtoken = get_config('local_warpwire', 'warpwire_auth_token');
        if (empty($authtoken)) {
            $authtoken = self::authorize();
            set_config('warpwire_auth_token', $authtoken, 'local_warpwire');
        }

        try {
            $result = self::make_get_request($url, $authtoken);
        } catch (\Exception $ex) {
            if ($ex->getCode() === 401) {
                $authtoken = self::authorize();
                set_config('warpwire_auth_token', $authtoken, 'local_warpwire');

                $result = self::make_get_request($url, $authtoken);
            } else {
                throw $ex;
            }
        }

        return $result;
    }

    /**
     * Determine if the requisite config values for starting a trial are set.
     *
     * @return boolean
     */
    public static function can_start_trial() {
        global $CFG;

        return !empty($CFG->warpwireWebhookUrl) && !empty($CFG->warpwireWebhookAuthKey) && !empty($CFG->warpwireWebhookAuthSecret);
    }

    /**
     * Checks if site is configured with necessary Warpwire credentials.
     *
     * @return boolean
     */
    public static function is_configured() {
        $allconfig = (array)get_config('local_warpwire');
        return (
            !empty($allconfig['warpwire_url']) &&
            !empty($allconfig['warpwire_key']) &&
            !empty($allconfig['warpwire_secret'])
        );
    }

    /**
     * Checks if site has all Warpwire credentials configured.
     *
     * @return boolean
     */
    public static function is_full_configured() {
        $allconfig = (array)get_config('local_warpwire');
        return !empty($allconfig['warpwire_url']) &&
               !empty($allconfig['warpwire_key']) &&
               !empty($allconfig['warpwire_secret']) &&
               !empty($allconfig['warpwire_admin_username']) &&
               !empty($allconfig['warpwire_admin_password']);
    }

    /**
     * Update the config and add the update to the config log
     *
     * @param string $name the config name
     * @param string $value
     */
    public static function set_config_log($name, $value) {
        $oldvalue = get_config('local_warpwire', $name);
        set_config($name, $value, 'local_warpwire');
        add_to_config_log($name, $oldvalue, $value, 'local_warpwire');
    }

    /**
     * Setup lti tool for Warpwire graded activities.
     * Used for setting up the lti or to disable it.
     *
     * @param boolean $enabled
     */
    public static function setup_lti_tool($enabled) {
        global $CFG;

        try {
            $existingltiids = [];
            $existingtypes = \lti_get_lti_types();
            foreach ($existingtypes as $existingtype) {
                if ($existingtype->name === 'Warpwire Graded Activity') {
                    $existingltiids[] = $existingtype->id;
                }
            }

            if (preg_match('!^https?://(localhost|127.0.0.1)!', $CFG->wwwroot)) {
                $icon = '/local/warpwire/pix/icon.png';
            } else {
                $icon = $CFG->wwwroot . '/local/warpwire/pix/icon.png';
            }

            $toolurl = \rtrim(get_config('local_warpwire', 'warpwire_url'), '/') . '/api/ltix/';

            $data = (object)[
                'lti_typename' => 'Warpwire Graded Activity',
                'lti_toolurl' => $toolurl,
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
                'lti_toolurl_ContentItemSelection' => $toolurl,
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
                'course' => 1,
            ];

            if (!empty($existingltiids)) {
                foreach ($existingltiids as $existingltiid) {
                    $type = new \stdClass();
                    $type->state = \LTI_TOOL_STATE_CONFIGURED;
                    $type->id = $existingltiid;
                    \lti_load_type_if_cartridge($data);
                    \lti_update_type($type, $data);
                }
            } else {
                $type = new \stdClass();
                $type->state = \LTI_TOOL_STATE_CONFIGURED;
                \lti_load_type_if_cartridge($data);
                \lti_add_type($type, $data);
            }
        } catch (\Throwable $ex) {
            $errmessage = $ex->getMessage();
            debugging("Failed to configure Warpwire LTI: $errmessage", DEBUG_NORMAL);
        }
    }

    /**
     * Gets an auth token from Warpwire.
     *
     * @return string auth token
     */
    private static function authorize() {
        $baseurl = get_config('local_warpwire', 'warpwire_url');
        $adminusername = get_config('local_warpwire', 'warpwire_admin_username');
        $adminpassword = get_config('local_warpwire', 'warpwire_admin_password');

        $auth = self::make_post_request("{$baseurl}api/authenticate/", [], $adminusername, $adminpassword);
        if (!is_array($auth) || !isset($auth['token'])) {
            throw new \Exception('Could not retrieve auth token');
        }

        return $auth['token'];
    }
}
