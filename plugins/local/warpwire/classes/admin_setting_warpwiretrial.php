<?php

namespace local_warpwire;

class admin_setting_warpwiretrial extends \admin_setting {
    public function __construct() {
        $this->nosave = true;
        parent::__construct('warpwire_trial_setup', 'Warpwire Trial Setup', '', '');
    }

    public function get_setting() {
        return true;
    }

    public function get_defaultsetting() {
        return true;
    }

    public function write_setting($data) {
        // do not write any setting
        return '';
    }

    public function is_related($query) {
        return false;
    }

    public function output_html($data, $query='') {
        global $CFG, $OUTPUT;

        $url = new \moodle_url('/local/warpwire/trial_setup.php', ['sesskey' => sesskey()]);

        $isConfigured = get_config('local_warpwire', 'is_configured');
        $status = get_config('local_warpwire', 'warpwire_trial_status');
        $statusMessage = get_config('local_warpwire', 'warpwire_trial_status_message');

        $html = $OUTPUT->box_start('generalbox');

        $table = new \html_table();
        $data = [];
        foreach (get_config('local_warpwire') as $key => $value) {
            $data[] = [$key, $value];
        }
        $table->data = $data;
        $table->head = ['Key', 'Value'];
        $html .= \html_writer::table($table);

        if ($isConfigured) {
            $html .= \html_writer::div('Your Warpwire trial has been set up. Please consult the usage information below to determine how much content you can add before you need to upgrade. If you wish to upgrade, please contact Moodle.us support.');

            $header = ['', 'Current Usage', 'Limit'];
            $data = [];

            $baseUrl = get_config('local_warpwire', 'warpwire_url');
            $adminUsername = get_config('local_warpwire', 'warpwire_admin_username');
            $adminPassword = get_config('local_warpwire', 'warpwire_admin_password');

            try {
                // TODO: save this token and reauthorize on a 401
                $auth = \local_warpwire\utilities::makePostRequest("{$baseUrl}api/authenticate/", [], $adminUsername, $adminPassword);
                if (!is_array($auth) || !isset($auth['token'])) {
                    throw new \Exception('Could not retrieve auth token');
                }

                $token = $auth['token'];
                $usage = \local_warpwire\utilities::makeGetRequest("{$baseUrl}api/usage/summary/current/", $token);

                $data[] = [ 'Hours of Video', $usage['summary']['actual_duration_hours'], $usage['summary']['allowed_duration_hours'] ];
                $data[] = [ 'Number of Videos', $usage['summary']['actual_video_count'], $usage['summary']['allowed_video_count'] ];

                $table = new \html_table();
                $table->head = $header;
                $table->data = $data;
                $html .= \html_writer::table($table);
            } catch(\Exception $ex) {
                \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE');
                $html .= \html_writer::div('Unable to retrieve usage information.');
            }

            $html .= \html_writer::start_div();
            $html .= \html_writer::link($url . '&reset=1', 'Reset Configuration');
            $html .= \html_writer::end_div();
        } elseif (!empty($status)) {
            $html .= \html_writer::div(strtoupper($status) . ': ' . $statusMessage);
            if (in_array(\strtolower($status), ['error', 'invalid'])) {
                $html .= \html_writer::div('Please contact support or try again.');

                $html .= \html_writer::start_div();
                $html .= \html_writer::link($url, 'Start Trial');
                $html .= \html_writer::end_div();
            }
        } else {
            $html .= \html_writer::start_div();
            $html .= \html_writer::link($url, 'Start Trial');
            $html .= \html_writer::end_div();
        }

        $html .= $OUTPUT->box_end();

        return highlight($query, $html);
    }
}
