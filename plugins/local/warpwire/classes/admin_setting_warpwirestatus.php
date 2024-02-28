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

class admin_setting_warpwirestatus extends \admin_setting {

    private static $metricnames = [
        'duration' => 'Video Duration',
        'video' => 'Number of Videos',
        'closed_caption_duration' => 'Machine Captions',
        'closed_caption_cost' => 'Human Captions',
        'bandwidth' => 'Total Bandwidth',
        'public_bandwidth' => 'Public Bandwidth',
        'internal_bandwidth' => 'Non-Public Bandwidth',
        'storage' => 'Storage',
    ];

    public function __construct() {
        $this->nosave = true;
        $this->plugin = 'local_warpwire';
        parent::__construct('setup_setup', 'Warpwire Status', '', '');
    }

    public function get_setting() {
        return true;
    }

    public function get_defaultsetting() {
        return true;
    }

    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    public function is_related($query) {
        return false;
    }

    public function output_html($data, $query='') {
        global $OUTPUT;

        $html = '';

        if (\local_warpwire\utilities::is_full_configured()) {
            $baseurl = get_config('local_warpwire', 'warpwire_url');

            $clientidentifier = explode('.', parse_url($baseurl, PHP_URL_HOST))[0];
            $html .= \html_writer::tag('p', get_string('notice_client_identifier', 'local_warpwire', $clientidentifier));

            try {
                $bootstrap = \local_warpwire\utilities::make_authenticated_get_request("{$baseurl}api/bootstrap/");
                $usagedata = \local_warpwire\utilities::make_authenticated_get_request("{$baseurl}api/upload/limit/all/");

                $html .= \html_writer::tag(
                    'p',
                    get_string('notice_account_type', 'local_warpwire', $bootstrap['client']['isTrial'] ? 'Trial' : 'Paid')
                );

                if ($bootstrap['client']['isTrial'] ?? false) {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits_trial', 'local_warpwire'));
                } else {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits', 'local_warpwire'));
                }

                $sortedusagedata = [];
                foreach (array_keys(self::$metricnames) as $metric) {
                    if (isset($usagedata[$metric])) {
                        $sortedusagedata[$metric] = $usagedata[$metric];
                    }
                }

                $header = ['', 'Current Usage', 'Limit', '% Used', ''];
                $data = [];

                foreach ($sortedusagedata as $metric => $info) {
                    $actual = $info['usage'];
                    $allowed = $info['limit'];

                    if ($allowed === null || !is_numeric($allowed) || $allowed <= 0) {
                        continue;
                    }

                    if ($allowed === null) {
                        $percentstring = 'N/A';
                        $statushtml = \html_writer::img(
                            $OUTPUT->image_url('i/checkedcircle'),
                            'OK',
                            ['style' => 'margin-right: 5px; width: 1em; height: 1em']
                        ) . \html_writer::span('OK');
                    } else if ($allowed === 0) {
                        $percentstring = 'N/A';
                        $statushtml = \html_writer::img(
                            $OUTPUT->image_url('t/unlock'),
                            'Not Allowed',
                            ['style' => 'margin-right: 5px; width: 1em; height: 1em']
                        ) . \html_writer::span('Not Allowed');
                    } else {
                        $percent = ($actual / $allowed) * 100;
                        $percentstring = sprintf('%d%%', $percent);
                        if ($percent < 100) {
                            $statushtml = \html_writer::img(
                                $OUTPUT->image_url('i/checkedcircle'),
                                'OK',
                                ['style' => 'margin-right: 5px; width: 1em; height: 1em']
                            ) . \html_writer::span('OK');
                        } else {
                            $statushtml = \html_writer::img(
                                $OUTPUT->image_url('i/warning'),
                                'Limit Reached',
                                ['style' => 'margin-right: 5px; width: 1em; height: 1em']
                            ) . \html_writer::span('Limit Reached');
                        }
                    }

                    $data[] = [
                        self::$metricnames[$metric] ?? $metric,
                        $this->pretty_print_amount($metric, $actual),
                        $this->pretty_print_amount($metric, $allowed),
                        $percentstring,
                        $statushtml,
                    ];
                }

                $table = new \html_table();
                $table->head = $header;
                $table->data = $data;
                $html .= \html_writer::table($table);
            } catch (\Throwable $ex) {
                \local_warpwire\utilities::error_log_long((string)$ex, 'WARPWIRE');
                $html .= \html_writer::tag('p', get_string('notice_error_usage', 'local_warpwire'));
            }
        } else if (\local_warpwire\utilities::is_configured()) {
            $baseurl = get_config('local_warpwire', 'warpwire_url');

            $clientidentifier = explode('.', parse_url($baseurl, PHP_URL_HOST))[0];

            $html .= \html_writer::tag('p', get_string('notice_client_identifier', 'local_warpwire', $clientidentifier));
        } else if (\local_warpwire\utilities::can_start_trial()) {
            if (!empty($status = get_config('local_warpwire', 'setup_status'))) {
                $html .= \html_writer::script('', new \moodle_url('/local/warpwire/checkstatus.js'));
                $html .= \html_writer::tag(
                    'p',
                    'Creating a new site may take several minutes. You may leave and return to this page at any time.'
                );
                if (!in_array(strtolower($status), ['queued', 'notstarted', 'processing', 'unknown'])) {
                    $html .= \html_writer::tag(
                        'p',
                        ucfirst(strtolower($status)) . ': ' . get_config('local_warpwire', 'setup_status_message')
                    );
                    $html .= $this->create_start_trial_button();
                } else {
                    $html .= \html_writer::start_tag('p');
                    $html .= \html_writer::img(
                        $OUTPUT->image_url('y/loading'),
                        'OK',
                        ['style' => 'margin-right: 5px; width: 1em; height: 1em']
                    );
                    $html .= \html_writer::tag('span', 'Checking status...', ['id' => 'warpwire_status_container']);
                    $html .= \html_writer::end_tag('p');
                    $html .= $this->create_start_trial_button(['id' => 'warpwire_trial_button', 'style' => 'display: none']);
                }
            } else {
                $html .= \html_writer::tag('p', get_string('notice_getting_started', 'local_warpwire'));
                $html .= $this->create_start_trial_button();
            }
        } else {
            $html .= \html_writer::tag('p', get_string('notice_getting_started_no_trial', 'local_warpwire'));
        }

        return highlight($query, $html);
    }

    private function create_start_trial_button($attrs = []) {
        return \html_writer::start_div('box generalbox py-3', $attrs)
             . \html_writer::link(
                    new \moodle_url('/local/warpwire/setup.php',
                    ['action' => 'setup', 'sesskey' => sesskey()]),
                    get_string('action_start_trial', 'local_warpwire'),
                    ['class' => 'btn btn-primary']
               )
             . \html_writer::end_div();
    }

    private function pretty_print_amount($metric, $value) {
        if ($value === null) {
            return 'No Limit';
        } else if (in_array($metric, ['storage', 'bandwidth', 'public_bandwidth', 'internal_bandwidth'])) {
            if ($value <= 0) {
                return '0 Bytes';
            } else {
                $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                $order = floor(log($value) / log(1000));
                return sprintf('%0.1d %s', $value / pow(1000, $order), $sizes[$order]);
            }
        } else if ($metric === 'duration') {
            $value /= 3600.0;
            return sprintf('%0.1f hours', round($value, 1));
        } else if ($metric === 'closed_caption_duration') {
            $value /= 3600.0;
            return sprintf('%0.1f hours', $value);
        } else if ($metric === 'closed_caption_cost') {
            return sprintf('$%0.2f', round($value, 2));
        } else {
            return sprintf('%d', $value);
        }
    }
}
