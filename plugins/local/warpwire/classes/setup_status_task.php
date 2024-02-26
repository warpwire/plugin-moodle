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

class setup_status_task extends \core\task\adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();
        $statusurl = $data->status_url;

        \local_warpwire\utilities::stdout_log_long('Starting provisioning status check...', 'WARPWIRE STATUS');
        $starttime = \time();
        while (time() - $starttime < 600 && $this->getstatus($statusurl) === false) {
            \local_warpwire\utilities::stdout_log_long('Waiting 10 seconds...', 'WARPWIRE STATUS');
            sleep(10);
        }

        if (!\local_warpwire\utilities::is_configured()) {
            if (time() - $starttime < 600) {
                \local_warpwire\utilities::stdout_log_long('Failed to configure due to error', 'WARPWIRE STATUS');
            } else {
                \local_warpwire\utilities::stdout_log_long('Failed to configure after 600 seconds', 'WARPWIRE STATUS');
            }
        }

        \local_warpwire\utilities::setupLtiTool(true);
    }

    private function getstatus($statusurl) {
        try {
            $result = \local_warpwire\utilities::make_get_request($statusurl, null, true);

            \local_warpwire\utilities::stdout_log_long($result, 'WARPWIRE STATUS');

            set_config('setup_status', $result['status'], 'local_warpwire');
            set_config('setup_status_message', $result['message'], 'local_warpwire');

            if ($result['done'] === false) {
                // Keep waiting.
                return false;
            }

            if (strtolower($result['status']) !== 'success') {
                // No further processing on error states.
                return true;
            }

            $internaldomain = $result['internal_domain'];
            $initialadmincredentialsurl = $result['initial_admin_credentials_url'];
            $initialtikeyurl = $result['initial_lti_key_credentials_url'];

            if (!empty($initialtikeyurl)) {
                $initialltikey = \local_warpwire\utilities::make_get_request($initialtikeyurl, null, true);
                \local_warpwire\utilities::stdout_log_long($initialltikey, 'WARPWIRE STATUS');
            } else {
                $initialltikey = [
                    'key' => '',
                    'secret' => '',
                ];
            }

            if (!empty($initialadmincredentialsurl)) {
                $initialadmincredentials = \local_warpwire\utilities::make_get_request($initialadmincredentialsurl, null, true);
                \local_warpwire\utilities::stdout_log_long($initialadmincredentials, 'WARPWIRE STATUS');
            } else {
                $initialadmincredentials = [
                    'unique_id' => '',
                    'password' => '',
                ];
            }

            \local_warpwire\utilities::set_config_log('warpwire_url',  'https://' . $internaldomain . '/');
            \local_warpwire\utilities::set_config_log('warpwire_key',  $initialltikey['key']);
            \local_warpwire\utilities::set_config_log('warpwire_secret',  $initialltikey['secret']);

            \local_warpwire\utilities::set_config_log('warpwire_admin_username',  $initialadmincredentials['unique_id']);
            \local_warpwire\utilities::set_config_log('warpwire_admin_password',  $initialadmincredentials['password']);

            set_config('setup_status', null, 'local_warpwire');
            set_config('setup_status_message', null, 'local_warpwire');

            return true;
        } catch (\Throwable $ex) {
            \local_warpwire\utilities::stdout_log_long((string)$ex, 'WARPWIRE STATUS');
            return false;
        }
    }
}
