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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_warpwire_upgrade($oldversion) {
    if ($oldversion < 20220712) {
        $lti = get_config('local_warpwire', 'warpwire_lti');
        $url = get_config('local_warpwire', 'warpwire_url');

        if (empty($url) && !empty($lti)) {
            $newUrl = preg_replace('!/api/ltix?/$!', '/', $lti);
            set_config('local_warpwire', $newUrl);
        }

        upgrade_plugin_savepoint(true, 20220712, 'local', 'warpwire');
    }

    return true;
}
