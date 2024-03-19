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


// Main file for warpwire module.

require_once("../../config.php");
require_once("./lib.php");
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

$wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);

if (!isloggedin() && !isguestuser() && $wstoken) {
    // This will authenticate the browser session to the user associated with the wstoken.
    require_once($CFG->dirroot . '/webservice/lib.php');
    $webservicelib = new webservice();
    $webservicelib->authenticate_user($wstoken);
}

require_login();

global $USER, $COURSE;

$courseid = required_param('course_id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid]);
$sectionid = optional_param('section_id', '', PARAM_INT);
$moduleid = optional_param('module_id', '', PARAM_INT);

warpwire_external_content($USER, $course, $sectionid, $moduleid);
exit;
