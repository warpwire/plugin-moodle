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

defined('MOODLE_INTERNAL') || die();

class check_setup_status extends \external_api {
    /**
     * Defines the parameters for the get_status function.
     *
     * @return external_function_parameters An object describing the parameters.
     */
    public static function get_status_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Describes the structure of the data returned by the get_status function.
     *
     * @return external_single_structure An object describing the structure of the returned data.
     */
    public static function get_status_returns() {
        return new \external_single_structure([
            'status' => new \external_value(\PARAM_TEXT, 'general status'),
            'status_message' => new \external_value(\PARAM_TEXT, 'detailed message associated with status'),
        ]);
    }

    /**
     * Checks if warpwire is configured and returns the status and a message.
     * Requires the 'moodle/site:config' capability.
     *
     * @return array An array containing the status (notstarted, processing, success, error) and a message.
     */
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
