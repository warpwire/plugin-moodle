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
            }
        }
    }

    public static function makeGetRequest($url, $token = null, $useStdout = false) {
        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        if ($useStdout) {
            self::stdoutLogLong('GET ' . $url, 'WARPWIRE');
        } else {
            self::errorLogLong('GET ' . $url, 'WARPWIRE');
        }

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

        if ($useStdout) {
            self::stdoutLogLong('POST ' . $url, 'WARPWIRE');
        } else {
            self::errorLogLong('POST ' . $url, 'WARPWIRE');
        }

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
                $authToken = get_config('local_warpwire', 'warpwire_auth_token');
                if (empty($authToken)) {
                    $authToken = self::authorize(get_config('local_warpwire', 'warpwire_url'), get_config('local_warpwire', 'warpwire_admin_username'), get_config('local_warpwire', 'warpwire_admin_password'));
                    set_config('warpwire_auth_token', $authToken, 'local_warpwire');
                }

                $result = self::makeGetRequest($url, $authToken, $useStdout);
            } else {
                throw $ex;
            }
        }

        return $result;
    }

    public static function isConfigured() {
        return !empty(get_config('local_warpwire', 'warpwire_lti')) &&
               !empty(get_config('local_warpwire', 'warpwire_key')) &&
               !empty(get_config('local_warpwire', 'warpwire_secret'));
    }

    public static function isFullConfigured() {
        return self::isConfigured() &&
               !empty(get_config('local_warpwire', 'warpwire_url')) &&
               !empty(get_config('local_warpwire', 'warpwire_admin_username')) &&
               !empty(get_config('local_warpwire', 'warpwire_admin_password'));
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
