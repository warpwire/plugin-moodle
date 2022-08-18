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

$services = [
    'warpwiresetupservice' => [
        'functions' => ['local_warpwire_check_setup_status'],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => '',
        'downloadfils' => '',
        'uploadfiles' => ''
    ]
];

$functions = [
    'local_warpwire_check_setup_status' => [
        'classname' => 'local_warpwire\check_setup_status',
        'methodname' => 'get_status',
        'description' => 'Checks Warpwire setup status',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
        'capabilities' => ''
    ]
];
