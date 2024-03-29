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
use core_external\external_api;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class check_setup_status extends external_api {
    public static function get_status_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_status_returns() {
        return new external_single_structure([
            'status' => new external_value(\PARAM_TEXT, 'general status'),
            'status_message' => new external_value(\PARAM_TEXT, 'detailed message associated with status'),
        ]);
    }

    public static function get_status() {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        if (\local_warpwire\utilities::is_configured()) {
            return [
                'status' => 'Success',
                'status_message' => 'Setup is complete',
            ];
        }

        $status = get_config('local_warpwire', 'setup_status');
        $statusmessage = get_config('local_warpwire', 'setup_status_message');

        return [
            'status' => empty($status) ? 'Unknown' : ucfirst(strtolower($status)),
            'status_message' => empty($statusmessage) ? 'unknown' : $statusmessage,
        ];
    }
}
