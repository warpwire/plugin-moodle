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

class event_handler {
    public static function on_setting_changed(\core\event\config_log_created $evt) {
        if ($evt->other['plugin'] !== 'local_warpwire') {
            return;
        }

        if (\local_warpwire\utilities::is_configured()) {
            \local_warpwire\utilities::error_log_long('Warpwire plugin is configured. Setting up features.', 'WARPWIRE LTI');

            \local_warpwire\utilities::setup_lti_tool(true);
        } else {
            \local_warpwire\utilities::error_log_long('Warpwire plugin is not configured. Disabling features.', 'WARPWIRE LTI');

            // Disable the tool by changing its visibility.
            \local_warpwire\utilities::setup_lti_tool(false);

            // It's not possible to remove and later re-add the tool as it breaks any embedded content.
        }

        if (in_array($evt->other['name'], ['warpwire_url', 'warpwire_admin_username', 'warpwire_admin_password'])) {
            \local_warpwire\utilities::error_log_long(
                'Warpwire site configuration has changed. Resetting auth token.',
                'WARPWIRE LTI'
            );
            set_config('warpwire_auth_token', null, 'local_warpwire');
        }
    }
}
