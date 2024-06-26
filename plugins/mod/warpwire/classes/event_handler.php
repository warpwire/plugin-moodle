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

namespace mod_warpwire;

class event_handler {
    /**
     * Changes the visibility of Warpwire module if a critical setting changed.
     * Called by observer in db/events.
     *
     * @param \core\event\config_log_created $evt
     * @return void
     */
    public static function on_setting_changed(\core\event\config_log_created $evt) {
        if ($evt->other['plugin'] !== 'local_warpwire') {
            return;
        }

        if (\local_warpwire\utilities::is_configured()) {
            self::setmodulevisibility(true);
        } else {
            self::setmodulevisibility(false);
        }
    }

    /**
     * Sets the module visibility.
     *
     * @param boolean $visible
     * @return void
     */
    private static function setmodulevisibility($visible) {
        global $DB;

        try {
            $DB->set_field('modules', 'visible', $visible ? '1' : '0', ['name' => 'warpwire']);
        } catch (\Throwable $ex) {
            $errmessage = $ex->getMessage();
            debugging("Failed to change visibility of warpwire module: $errmessage", DEBUG_NORMAL);
        }
    }
}
