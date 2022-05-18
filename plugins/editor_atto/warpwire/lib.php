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
 * Atto text editor integration version file.
 *
 * @package    atto_warpwire
 * @copyright  2016 Warpwire  <warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the js strings required for this plugin
 */

defined('MOODLE_INTERNAL') || die('Invalid access');

/**
 * Sends parameters to the JS module.
 *
 * @return array
 */
function atto_warpwire_params_for_js() {
	global $CFG, $COURSE;

    $ltiUrl = get_config('local_warpwire', 'warpwire_lti');
    if (empty($ltiUrl)) {
        return array('warpwire_url' => $CFG->wwwroot . '/local/warpwire/html/setup.html');
    }

	// build the query params to pass
	$url_params_query = http_build_query(array('mode' => 'plugin'), '', '&');

	$url_parts = parse_url($ltiUrl . '?' . $url_params_query);

	$parameters = array();
	if(!empty($url_parts['query']))
		parse_str($url_parts['query'], $parameters);

	$url_parts['query'] = http_build_query($parameters, '', '&');

	$url = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].'?'.$url_parts['query'];

	$parts = array(
		'url' => $url,
		'course_id' => $COURSE->id
	);

	$partsString = http_build_query($parts, '', '&');

	$url = $CFG->wwwroot . '/local/warpwire/?' .$partsString;

	return(array(
		'warpwire_url' => $url
	));
}
