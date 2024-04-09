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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('./lib.php');

admin_externalpage_setup('warpwire_trial');
$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_warpwire']);

if (!confirm_sesskey()) {
    redirect($returnurl);
}

$action = optional_param('action', false, PARAM_TEXT);
$isconfigured = \local_warpwire\utilities::is_configured();
$canstarttrial = \local_warpwire\utilities::can_start_trial();

switch($action) {
    case 'setup':
        if ($isconfigured) {
            redirectandexit(get_string('notice_already_configured', 'local_warpwire'));
        } else if (!$canstarttrial) {
            redirectandexit(get_string('notice_cannot_start_trial', 'local_warpwire'));
        }

        resetconfiguration();
        setuptrial();
        break;
    default:
        redirectandexit(get_string('notice_invalid_action', 'local_warpwire'));
        break;
}
