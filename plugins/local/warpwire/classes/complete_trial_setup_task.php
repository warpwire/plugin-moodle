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

use moodle_exception;

class complete_lti_setup_task extends \core\task\adhoc_task {
    public function execute() {
        $statusurl = $this->get_custom_data()->status_url;
        $trialsetupcomplete = $this->check_trial_setup_status($statusurl);
        if ($trialsetupcomplete) {
            \local_warpwire\utilities::setup_lti_tool(true);
            return;
        }

        // Throw error to force a retry.
        throw new moodle_exception('Warpwire trial setup is still in progress');
    }

    private function check_trial_setup_status($statusurl) {
        try {
            $result = \local_warpwire\utilities::make_get_request($statusurl, null, true);

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
            } else {
                $initialltikey = [
                    'key' => '',
                    'secret' => '',
                ];
            }

            if (!empty($initialadmincredentialsurl)) {
                $initialadmincredentials = \local_warpwire\utilities::make_get_request($initialadmincredentialsurl, null, true);
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
            return false;
        }
    }
}
