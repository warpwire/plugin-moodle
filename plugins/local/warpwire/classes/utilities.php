<?php

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
            throw new \Exception('GET request failed due to response code: ' . $responseCode . '; body: ' . $result);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception('GET request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result);
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
            throw new \Exception('POST request failed due to response code: ' . $responseCode . '; body: ' . $result);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new \Exception('POST request failed due to JSON error: ' . \json_last_error_msg() . '; body: ' . $result);
        }

        return $decoded;
    }
}
