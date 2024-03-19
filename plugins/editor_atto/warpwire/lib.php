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

    $warpwireurl = get_config('local_warpwire', 'warpwire_url');
    if (empty($warpwireurl)) {
        return ['warpwire_url' => $CFG->wwwroot . '/local/warpwire/html/setup.html'];
    }

    $ltiurl = \rtrim($warpwireurl, '/') . '/api/lti/';

    // Build the query params to pass.
    $urlparamsquery = http_build_query(['mode' => 'plugin'], '', '&');

    $urlparts = parse_url($ltiurl . '?' . $urlparamsquery);

    $parameters = [];
    if (!empty($urlparts['query'])) {
        parse_str($urlparts['query'], $parameters);
    }
    $urlparts['query'] = http_build_query($parameters, '', '&');

    $url = $urlparts['scheme'].'://'.$urlparts['host'].$urlparts['path'].'?'.$urlparts['query'];

    $parts = [
        'url' => $url,
        'course_id' => $COURSE->id,
    ];

    $partsstring = http_build_query($parts, '', '&');

    $url = $CFG->wwwroot . '/local/warpwire/?' .$partsstring;

    return([
        'warpwire_url' => $url,
    ]);
}
