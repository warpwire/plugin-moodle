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

namespace atto_warpwire;

class event_handler {
    public static function on_setting_changed(\core\event\config_log_created $evt) {
        if ($evt->other['plugin'] !== 'local_warpwire') {
            return;
        }

        if (\local_warpwire\utilities::is_configured()) {
            self::install_toolbar_button();
        } else {
            self::remove_toolbar_button();;
        }
    }

    public static function install_toolbar_button() {
        $toolbar = get_config('editor_atto', 'toolbar');
        $haswarpwire = stristr($toolbar, 'warpwire');

        if (!$haswarpwire) {
            $newtoolbar = preg_replace('/(.+?=.+?)media($|\s|,)/m', '$1media, warpwire$2', $toolbar, 1);
            set_config('toolbar', $newtoolbar, 'editor_atto');
            add_to_config_log('toolbar', $toolbar, $newtoolbar, 'editor_atto');
        }
    }

    public static function remove_toolbar_button() {
        $toolbar = get_config('editor_atto', 'toolbar');
        $haswarpwire = stristr($toolbar, 'warpwire');

        if ($haswarpwire) {
            $newtoolbar = preg_replace('/(.+?=.+?)media, warpwire($|\s|,)/m', '$1media$2', $toolbar, 1);
            set_config('toolbar', $newtoolbar, 'editor_atto');
            add_to_config_log('toolbar', $toolbar, $newtoolbar, 'editor_atto');
        }
    }
}
