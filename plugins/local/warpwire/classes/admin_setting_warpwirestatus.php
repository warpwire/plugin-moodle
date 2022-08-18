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
    private static $METRIC_NAMES = [
        'duration' => 'Video Duration',
        'video' => 'Number of Videos',
        'closed_caption_duration' => 'Machine Captions',
        'closed_caption_cost' => 'Human Captions',
        'bandwidth' => 'Total Bandwidth',
        'public_bandwidth' => 'Public Bandwidth',
        'internal_bandwidth' => 'Non-Public Bandwidth',
        'storage' => 'Storage'
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
        // do not write any setting
        return '';
    }

    public function is_related($query) {
        return false;
    }

    public function output_html($data, $query='') {
        global $OUTPUT, $CFG;

        $html = '';

        if (\local_warpwire\utilities::isFullConfigured()) {
            $baseUrl = get_config('local_warpwire', 'warpwire_url');

            $clientIdentifier = explode('.', parse_url($baseUrl, PHP_URL_HOST))[0];
            $html .= \html_writer::tag('p', get_string('notice_client_identifier', 'local_warpwire', $clientIdentifier));

            try {
                $bootstrap = \local_warpwire\utilities::makeAuthenticatedGetRequest("{$baseUrl}api/bootstrap/");
                $usageData = \local_warpwire\utilities::makeAuthenticatedGetRequest("{$baseUrl}api/upload/limit/all/");

                $html .= \html_writer::tag('p', get_string('notice_account_type', 'local_warpwire', $bootstrap['client']['isTrial'] ? 'Trial' : 'Paid'));

                if ($bootstrap['client']['isTrial'] ?? false) {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits_trial', 'local_warpwire'));
                } else {
                    $html .= \html_writer::tag('p', get_string('notice_usage_limits', 'local_warpwire'));
                }

                $sortedUsageData = [];
                foreach (array_keys(self::$METRIC_NAMES) as $metric) {
                    if (isset($usageData[$metric])) {
                        $sortedUsageData[$metric] = $usageData[$metric];
                    }
                }

                $header = ['', 'Current Usage', 'Limit', '% Used', ''];
                $data = [];

                foreach ($sortedUsageData as $metric => $info) {
                    $actual = $info['usage'];
                    $allowed = $info['limit'];

                    if ($allowed === null || !is_numeric($allowed) || $allowed <= 0) {
                        continue;
                    }

                    if ($allowed === null) {
                        $percentString = 'N/A';
                        $statusHtml = \html_writer::img($OUTPUT->image_url('i/checkedcircle'), 'OK', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('OK');
                    } elseif ($allowed === 0) {
                        $percentString = 'N/A';
                        $statusHtml = \html_writer::img($OUTPUT->image_url('t/unlock'), 'Not Allowed', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('Not Allowed');
                    } else {
                        $percent = ($actual / $allowed) * 100;
                        $percentString = sprintf('%d%%', $percent);
                        if ($percent < 100) {
                            $statusHtml = \html_writer::img($OUTPUT->image_url('i/checkedcircle'), 'OK', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('OK');
                        } else {
                            $statusHtml = \html_writer::img($OUTPUT->image_url('i/warning'), 'Limit Reached', ['style' => 'margin-right: 5px; width: 1em; height: 1em']) . \html_writer::span('Limit Reached');
                        }
                    }

                    $data[] = [
                        self::$METRIC_NAMES[$metric] ?? $metric,
                        $this->prettyPrintAmount($metric, $actual),
                        $this->prettyPrintAmount($metric, $allowed),
                        $percentString,
                        $statusHtml
                    ];
                }

                $table = new \html_table();
                $table->head = $header;
                $table->data = $data;
                $html .= \html_writer::table($table);
            } catch(\Throwable $ex) {
                \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE');
                $html .= \html_writer::tag('p', get_string('notice_error_usage', 'local_warpwire'));
            }
        } else if (\local_warpwire\utilities::isConfigured()) {
            $baseUrl = get_config('local_warpwire', 'warpwire_url');

            $clientIdentifier = explode('.', parse_url($baseUrl, PHP_URL_HOST))[0];

            $html .= \html_writer::tag('p', get_string('notice_client_identifier', 'local_warpwire', $clientIdentifier));
        } elseif (\local_warpwire\utilities::canStartTrial()) {
            if (!empty($status = get_config('local_warpwire', 'setup_status'))) {
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
        } else {
            $html .= \html_writer::tag('p', get_string('notice_getting_started_no_trial', 'local_warpwire'));
        }

        return highlight($query, $html);
    }

    private function createStartTrialButton($attrs = []) {
        return \html_writer::start_div('box generalbox py-3', $attrs)
             . \html_writer::link(new \moodle_url('/local/warpwire/setup.php', ['action' => 'setup', 'sesskey' => sesskey()]), get_string('action_start_trial', 'local_warpwire'), ['class' => 'btn btn-primary'])
             . \html_writer::end_div();
    }

    private function prettyPrintAmount($metric, $value) {
        if ($value === null) {
            return 'No Limit';
        } elseif (in_array($metric, ['storage', 'bandwidth', 'public_bandwidth', 'internal_bandwidth'])) {
            if ($value <= 0) {
                return '0 Bytes';
            } else {
                $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                $order = floor(log($value) / log(1000));
                return sprintf('%0.1d %s', $value / pow(1000, $order), $sizes[$order]);
            }
        } elseif ($metric === 'duration') {
            $value /= 3600.0;
            return sprintf('%0.1f hours', round($value, 1));
        } elseif ($metric === 'closed_caption_duration') {
            $value /= 3600.0;
            return sprintf('%0.1f hours', $value);
        } elseif ($metric === 'closed_caption_cost') {
            return sprintf('$%0.2f', round($value, 2));
        } else {
            return sprintf('%d', $value);
        }
    }
}
