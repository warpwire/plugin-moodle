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

        if (\local_warpwire\utilities::isConfigured()) {
            \local_warpwire\utilities::errorLogLong('Warpwire plugin is configured. Setting up features.', 'WARPWIRE EVENT');

            \local_warpwire\utilities::setupLtiTool(false);
        } else {
            \local_warpwire\utilities::errorLogLong('Warpwire plugin is not configured. Disabling features.', 'WARPWIRE EVENT');

            // It's not possible to remove and later re-add the tool as it breaks any embedded content
            // \local_warpwire\utilities::removeLtiTool(false);
        }
    }
}
