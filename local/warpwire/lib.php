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

define('LOCAL_WARPWIRE_PLUGIN_NAME', 'local_warpwire');

define('LOCAL_WARPWIRE_DEFAULT_URL', 'https://example.warpwire.com/');
define('LOCAL_WARPWIRE_URL_PARAMETER', 'warpwire_url');

define('LOCAL_WARPWIRE_DEFAULT_LTI', 'https://example.warpwire.com/api/lti/');
define('LOCAL_WARPWIRE_LTI_PARAMETER', 'warpwire_lti');

define('LOCAL_WARPWIRE_DEFAULT_KEY', 'warpwire_key');
define('LOCAL_WARPWIRE_KEY_PARAMETER', 'warpwire_key');

define('LOCAL_WARPWIRE_DEFAULT_SECRET', 'warpwire_secret');
define('LOCAL_WARPWIRE_SECRET_PARAMETER', 'warpwire_secret');

$path = dirname(__FILE__) . '/library';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
