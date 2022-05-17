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

defined('MOODLE_INTERNAL') || die('Invalid access');

require_once('lang/en/local_warpwire.php');

print_r(get_config('local_warpwire'));

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_warpwire', get_string('pluginname', 'local_warpwire'));
    $ADMIN->add('localplugins', $settings);

    //heading
    $setting = new admin_setting_heading('local_warpwire/heading', '', get_string('setting_heading_desc', 'local_warpwire'));
    $settings->add($setting);

    $settings->add(new \local_warpwire\admin_setting_warpwiretrial());

    $ADMIN->add('modules', new admin_externalpage('warpwire_trial_setup', 'Warpwire Trial Setup', new moodle_url('/local/warpwire/trial_setup.php'), 'moodle/site:config', true));
}
