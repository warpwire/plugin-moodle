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

require_once("$CFG->libdir/externallib.php");

class check_setup_status extends \external_api {
    public static function get_status_parameters() {
        return new \external_function_parameters([]);
    }

    public static function get_status_returns() {
        return new \external_single_structure([
            'status' => new \external_value(\PARAM_TEXT, 'general status'),
            'status_message' => new \external_value(\PARAM_TEXT, 'detailed message associated with status')
        ]);
    }

    public static function get_status() {
        if (\local_warpwire\utilities::isConfigured()) {
            return [
                'status' => 'Success',
                'status_message' => 'Setup is complete'
            ];
        }

        $status = get_config('local_warpwire', 'setup_status');
        $statusMessage = get_config('local_warpwire', 'setup_status_message');

        return [
            'status' => empty($status) ? 'Unknown' : ucfirst(strtolower($status)),
            'status_message' => empty($statusMessage) ? 'unknown' : $statusMessage
        ];
    }
}
