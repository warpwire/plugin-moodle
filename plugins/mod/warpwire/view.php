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
 * Prints a particular instance of the Warpwire Activity Module
 *
 * @package    mod_warpwire
 * @copyright  2016 Warpwire <https://warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$w  = optional_param('w', 0, PARAM_INT);  // Warpwire instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('warpwire', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $warpwire   = $DB->get_record('warpwire', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($w) {
    $warpwire   = $DB->get_record('warpwire', array('id' => $w), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $warpwire->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('warpwire', $warpwire->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_warpwire\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $warpwire);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/warpwire/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($warpwire->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

$warpwireUrl = get_config('local_warpwire', 'warpwire_url');
if (empty($warpwireUrl)) {
    $pageUrl = $CFG->wwwroot . '/local/warpwire/html/setup.html';
} else {
    $url_parts = parse_url($warpwireUrl);

$parameters = array();
    if (!empty($url_parts['query'])) {
    parse_str($url_parts['query'], $parameters);
    }

$url_parts['query'] = http_build_query($parameters, '', '&');

    $url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $url_parts['query'];

$parts = array(
    'url' => $url,
    'course_id' => $course->id
);

$partsString = http_build_query($parts, '', '&');

    $pageUrl = $CFG->wwwroot . '/local/warpwire/?' .$partsString;
}

$content = '<iframe 
		id="contentframe" height="600" width="100%" 
		src="'.$pageUrl.'"
		frameborder="0" scrolling="0" 
		allow="autoplay *; encrypted-media *; fullscreen *; camera *; microphone *;" 
		title="Warpwire Media Library" allowfullscreen></iframe>';

echo $content;

// Finish the page.
echo $OUTPUT->footer();
