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
    public function __construct() {
        $this->nosave = true;
        parent::__construct('setup_setup', 'Warpwire Status', '', '');
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
        $html = '';

        $isConfigured = \local_warpwire\utilities::isConfigured();

        if ($isConfigured) {
            $html .= \html_writer::tag('p', get_string('notice_usage_limits', 'local_warpwire'));

            $header = ['', 'Current Usage', 'Limit'];
            $data = [];

            $baseUrl = get_config('local_warpwire', 'warpwire_url');

            try {
                $usage = \local_warpwire\utilities::makeAuthenticatedGetRequest("{$baseUrl}api/usage/summary/current/");

                $usageInfo = [];

                foreach ($usage['summary'] as $key => $value) {
                    if (preg_match('/^(actual|allowed)_(.*)$/', $key, $matches)) {
                        list (, $type, $metric) = $matches;
                        if ($value === null) {
                            $valueString = 'No Limit';
                        }
                        else if (in_array($metric, ['storage_tb', 'total_bandwidth_tb', 'public_bandwidth_tb', 'internal_bandwidth_tb'])) {
                            $value *= pow(10, 12);
                            if ($value <= 0) {
                                $valueString = '0 Bytes';
                            } else {
                                $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                                $order = floor(log($value) / log(1000));
                                $valueString = sprintf('%0.1d %s', $value / pow(1000, $order), $sizes[$order]);
                            }
                        } elseif ($metric === 'duration_hours') {
                            $valueString = sprintf('%0.1f hours', round($value, 1));
                        } elseif ($metric === 'caption_duration_minutes') {
                            $valueString = sprintf('%0.1f hours', $value);
                        } elseif ($metric === 'caption_cost_dollars') {
                            $valueString = sprintf('$%0.2f', round($value, 2));
                        } else {
                            $valueString = sprintf('%d', $value);
                        }
                        $usageInfo[$metric][$type] = ['value' => $value, 'string' => $valueString];
                    }
                }

                ksort($usageInfo);

                $metricNames = [
                    'storage_tb' => 'Storage',
                    'total_bandwidth_tb' => 'Total Bandwidth',
                    'public_bandwidth_tb' => 'Public Bandwidth',
                    'internal_bandwidth_tb' => 'Non-Public Bandwidth',
                    'duration_hours' => 'Video Duration',
                    'video_count' => 'Number of Videos',
                    'caption_cost_dollars' => 'Human Captions',
                    'caption_duration_minutes' => 'Machine Captions'
                ];

                foreach ($usageInfo as $metric => $metricInfo) {
                    if (is_numeric($metricInfo['allowed']['value']) && $metricInfo['allowed']['value'] >= 0) {
                        $data[] = [ $metricNames[$metric] ?? $metric, $metricInfo['actual']['string'], $metricInfo['allowed']['string'] ];
                    }
                }

                $table = new \html_table();
                $table->head = $header;
                $table->data = $data;
                $html .= \html_writer::table($table);
            } catch(\Throwable $ex) {
                \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE');
                $html .= \html_writer::tag('p', get_string('notice_error_usage', 'local_warpwire'));
            }
        } elseif (!empty(get_config('local_warpwire', 'setup_status'))) {
            $html .= \html_writer::script('', new \moodle_url('/local/warpwire/checkstatus.js'));
            $html .= \html_writer::tag('p', 'Checking status...', ['id' => 'warpwire_status_container']);
            $html .= $this->createStartTrialButton('warpwire_trial_button');
        } else {
            $html .= \html_writer::tag('p', get_string('notice_getting_started', 'local_warpwire'));
            $html .= $this->createStartTrialButton();
        }

        return highlight($query, $html);
    }

    private function createStartTrialButton($id = '') {
        if ($id != '') {
            $attrs = ['id' => $id];
        } else {
            $attrs = [];
        }

        return \html_writer::start_div('box generalbox py-3', $attrs)
             . \html_writer::link(new \moodle_url('/local/warpwire/setup.php', ['action' => 'setup', 'sesskey' => sesskey()]), get_string('action_start_trial', 'local_warpwire'), ['class' => 'btn btn-primary'])
             . \html_writer::end_div();
    }
}
