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
 * Library of interface functions and constants for Warpwire Activity Module
 *
 * @package    mod_warpwire
 * @copyright  2016 Warpwire <https://warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Moodle core API */

/**
 * Required moodle callback.
 * Returns the information on whether the Warpwire Activity Module
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function warpwire_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            if (defined('FEATURE_MOD_PURPOSE') && defined('MOD_PURPOSE_CONTENT') && $feature == FEATURE_MOD_PURPOSE) {
                // Makes the icon blue.
                return MOD_PURPOSE_CONTENT;
            }
            return null;
    }
}

/**
 * Required moodle callback.
 * Saves a new instance of the Warpwire Activity Module into the database
 *
 * @param stdClass $warpwire Submitted data from the form in mod_form.php
 * @param mod_warpwire_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted warpwire record
 */
function warpwire_add_instance(stdClass $warpwire, mod_warpwire_mod_form $mform = null) {
    global $DB;

    $warpwire->timecreated = time();

    // You may have to add extra stuff in here.

    $warpwire->id = $DB->insert_record('warpwire', $warpwire);

    return $warpwire->id;
}

/**
 * Required moodle callback.
 * Updates an instance of the Warpwire Activity Module in the database
 *
 * @param stdClass $warpwire An object from the form in mod_form.php
 * @param mod_warpwire_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function warpwire_update_instance(stdClass $warpwire, mod_warpwire_mod_form $mform = null) {
    global $DB;

    $warpwire->timemodified = time();
    $warpwire->id = $warpwire->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('warpwire', $warpwire);

    return $result;
}

/**
 * Required moodle callback.
 * Removes an instance of the Warpwire Activity Module from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function warpwire_delete_instance($id) {
    global $DB;

    if (! $warpwire = $DB->get_record('warpwire', ['id' => $id])) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('warpwire', ['id' => $warpwire->id]);

    return true;
}

/**
 * Prepares the recent activity data.
 * This is used by course/recent.php
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function warpwire_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}
