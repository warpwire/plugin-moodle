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

/**
 * Defines the version and other meta-info about the Warpwire Activity Module
 *
 * @package    mod_warpwire
 * @copyright  2016 Warpwire <https://warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_warpwire';
$plugin->version   = 2022060909;
$plugin->release   = '4.1.0-beta';
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2019111800;
$plugin->cron      = 0;

$plugin->dependencies = array(
    'local_warpwire' => 2022060909
);
