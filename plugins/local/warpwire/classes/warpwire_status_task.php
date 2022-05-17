<?php

namespace local_warpwire;

class warpwire_status_task extends \core\task\adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();
        $statusUrl = $data->status_url;

        \local_warpwire\utilities::stdoutLogLong('Starting provisioning status check...', 'WARPWIRE STATUS');
        $startTime = \time();
        while (time() - $startTime < 600 && $this->getStatus($statusUrl) === false) {
            \local_warpwire\utilities::stdoutLogLong('Waiting 10 seconds...', 'WARPWIRE STATUS');
            sleep(10);
        }

        if (get_config('local_warpwire', 'is_configured') !== 'yes') {
            \local_warpwire\utilities::stdoutLogLong('Failed to configure after 600 seconds or due to error', 'WARPWIRE STATUS');
        }
    }

    private function getStatus($statusUrl) {
        try {
            $result = \local_warpwire\utilities::makeGetRequest($statusUrl, null, true);

            \local_warpwire\utilities::stdoutLogLong($result, 'WARPWIRE STATUS');

            set_config('warpwire_trial_status', $result['status'], 'local_warpwire');
            set_config('warpwire_trial_status_message', $result['message'], 'local_warpwire');

            if ($result['done'] === false) {
                // keep waiting
                return false;
            }

            if (strtolower($result['status']) !== 'success') {
                // no further processing on error states
                return true;
            }

            $domain = $result['internal_domain'];
            $initialAdminCredentialsUrl = $result['initial_admin_credentials_url'];
            $initiaLtiKeyUrl = $result['initial_lti_key_credentials_url'];

            if (!empty($initiaLtiKeyUrl)) {
                $initialLtiKey = \local_warpwire\utilities::makeGetRequest($initiaLtiKeyUrl, null, true);
                \local_warpwire\utilities::stdoutLogLong($initialLtiKey, 'WARPWIRE STATUS');
            } else {
                $initialLtiKey = [
                    'key' => '',
                    'secret' => ''
                ];
            }

            if (!empty($initialAdminCredentialsUrl)) {
                $initialAdminCredentials = \local_warpwire\utilities::makeGetRequest($initialAdminCredentialsUrl, null, true);
                \local_warpwire\utilities::stdoutLogLong($initialAdminCredentials, 'WARPWIRE STATUS');
            } else {
                $initialAdminCredentials = [
                    'unique_id' => '',
                    'password' => ''
                ];
            }

            set_config('warpwire_lti', 'https://' . $domain . '/api/lti/', 'local_warpwire');

            set_config('warpwire_key', $initialLtiKey['key'], 'local_warpwire');
            set_config('warpwire_secret', $initialLtiKey['secret'], 'local_warpwire');

            set_config('warpwire_url', 'https://' . $domain . '/', 'local_warpwire');

            set_config('warpwire_admin_username', $initialAdminCredentials['unique_id'], 'local_warpwire');
            set_config('warpwire_admin_password', $initialAdminCredentials['password'], 'local_warpwire');

            set_config('is_configured', 'yes', 'local_warpwire');

            return true;
        } catch (\Exception $ex) {
            \local_warpwire\utilities::stdoutLogLong((string)$ex, 'WARPWIRE STATUS');
            return false;
        }
    }
}
