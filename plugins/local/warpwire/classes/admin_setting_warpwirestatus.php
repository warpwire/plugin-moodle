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
        // do not write any setting
        return '';
    }

    public function is_related($query) {
        return false;
    }

    public function output_html($data, $query='') {
        global $OUTPUT;

        $html = '';

        $isConfigured = \local_warpwire\utilities::isConfigured();

        if ($isConfigured) {
            $baseUrl = get_config('local_warpwire', 'warpwire_url');

            try {
                $bootstrap = \local_warpwire\utilities::makeAuthenticatedGetRequest("{$baseUrl}api/bootstrap/");
                $usage = \local_warpwire\utilities::makeAuthenticatedGetRequest("{$baseUrl}api/usage/summary/current/");

                if ($bootstrap['client']['isTrial'] ?? false) {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits_trial', 'local_warpwire'));
                } else {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits', 'local_warpwire'));
                }

                $usageInfo = [];

                foreach ($usage['summary'] as $key => $value) {
                    if (preg_match('/^(actual|allowed)_(.*)_(tb|dollars|minutes|hours|count)$/', $key, $matches)) {
                        list (, $type, $metric) = $matches;
                        if ($value === null) {
                            $valueString = 'No Limit';
                        }
                        else if (in_array($metric, ['storage', 'total_bandwidth', 'public_bandwidth', 'internal_bandwidth'])) {
                            $value *= pow(10, 12);
                            if ($value <= 0) {
                                $valueString = '0 Bytes';
                            } else {
                                $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                                $order = floor(log($value) / log(1000));
                                $valueString = sprintf('%0.1d %s', $value / pow(1000, $order), $sizes[$order]);
                            }
                        } elseif ($metric === 'duration') {
                            $valueString = sprintf('%0.1f hours', round($value, 1));
                        } elseif ($metric === 'caption_duration') {
                            $valueString = sprintf('%0.1f hours', $value);
                        } elseif ($metric === 'caption_cost') {
                            $valueString = sprintf('$%0.2f', round($value, 2));
                        } else {
                            $valueString = sprintf('%d', $value);
                        }
                        $usageInfo[$metric][$type] = ['value' => $value, 'string' => $valueString];
                    } elseif (\preg_match('/^(.*)_used_percent$/', $key, $matches)) {
                        list (, $metric) = $matches;
                        if ($value === null) {
                            $valueString = 'N/A';
                            $statusHtml = \html_writer::img($OUTPUT->image_url('t/unlock'), 'Not Allowed', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('Not Allowed');
                        } else {
                            $valueString = sprintf('%d%%', $value);
                            if ($value < 100) {
                                $statusHtml = \html_writer::img($OUTPUT->image_url('i/checkedcircle'), 'OK', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('OK');
                            } else {
                                $statusHtml = \html_writer::img($OUTPUT->image_url('i/warning'), 'Limit Reached', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('Limit Reached');
                            }
                        }
                        $usageInfo[$metric]['percent'] = ['value' => $value, 'string' => $valueString, 'statusHtml' => $statusHtml];
                    }
                }

                ksort($usageInfo);

                $metricNames = [
                    'storage' => 'Storage',
                    'total_bandwidth' => 'Total Bandwidth',
                    'public_bandwidth' => 'Public Bandwidth',
                    'internal_bandwidth' => 'Non-Public Bandwidth',
                    'duration' => 'Video Duration',
                    'video' => 'Number of Videos',
                    'caption_cost' => 'Human Captions',
                    'caption_duration' => 'Machine Captions'
                ];

                $header = ['', 'Current Usage', 'Limit', '% Used', ''];
                $data = [];

                foreach ($usageInfo as $metric => $metricInfo) {
                    if (isset($metricInfo['allowed']['value']) && is_numeric($metricInfo['allowed']['value']) && $metricInfo['allowed']['value'] > 0) {
                        $data[] = [ $metricNames[$metric] ?? $metric, $metricInfo['actual']['string'], $metricInfo['allowed']['string'], $metricInfo['percent']['string'] ?? '', $metricInfo['percent']['statusHtml'] ?? '' ];
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
        } elseif (!empty($status = get_config('local_warpwire', 'setup_status'))) {
            $html .= \html_writer::script('', new \moodle_url('/local/warpwire/checkstatus.js'));
            $html .= \html_writer::tag('p', 'Creating a new site may take several minutes. You may leave and return to this page at any time.');
            if (!in_array(strtolower($status), ['queued', 'notstarted', 'processing', 'unknown'])) {
                $html .= \html_writer::tag('p', ucfirst(strtolower($status)) . ': ' . get_config('local_warpwire', 'setup_status_message'));
                $html .= $this->createStartTrialButton();
            } else {
                $html .= \html_writer::start_tag('p');
                $html .= \html_writer::img($OUTPUT->image_url('y/loading'), 'OK', ['style' => 'margin-right: 5px; width: 1em; height: 1em']);
                $html .= \html_writer::tag('span', 'Checking status...', ['id' => 'warpwire_status_container']);
                $html .= \html_writer::end_tag('p');
                $html .= $this->createStartTrialButton(['id' => 'warpwire_trial_button', 'style' => 'display: none']);
            }
        } else {
            $html .= \html_writer::tag('p', get_string('notice_getting_started', 'local_warpwire'));
            $html .= $this->createStartTrialButton();
        }

        return highlight($query, $html);
    }

    private function createStartTrialButton($attrs = []) {
        return \html_writer::start_div('box generalbox py-3', $attrs)
             . \html_writer::link(new \moodle_url('/local/warpwire/setup.php', ['action' => 'setup', 'sesskey' => sesskey()]), get_string('action_start_trial', 'local_warpwire'), ['class' => 'btn btn-primary'])
             . \html_writer::end_div();
    }
}
