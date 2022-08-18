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

// Plugin settings.
$string['pluginname'] = 'Warpwire Plugin Configuration';
$string['modulename'] = 'Warpwire Plugin Configuration';
$string['modulenameplural'] = 'Warpwire Plugin Configurations';

$string['content_not_configured'] = 'Content cannot be displayed because the Warpwire plugin is not configured.';

$string['status_heading_label'] = 'Warpwire Status';
$string['status_heading_desc'] = '';

$string['setting_heading_label'] = 'Warpwire LTI Settings';
$string['setting_heading_desc'] = 'These settings customize the method in which your Moodle instance connects to Warpwire.<br/><br/>You may need to purge your Moodle caches after changing these settings for them to take effect.<br/><br/>';

$string['setting_heading2_label'] = 'Warpwire Site Settings';
$string['setting_heading2_desc'] = 'These settings are needed to get usage and trial information from your Warpwire site. If you don\'t know these settings, it is not necessary to fill them in.<br/><br/>You may need to purge your Moodle caches after changing these settings for them to take effect.<br/><br/>';

$string['setting_url_label'] = 'Your Warpwire site URL';
$string['setting_url_desc'] = 'The base URL of your Warpwire site - for example, "https://example.warpwire.com/". Make sure you include the "https://", as well as the trailing slash.<br /><br />';

$string['setting_lti_label'] = 'Your Warpwire LTI Launch URL';
$string['setting_lti_desc'] = 'The LTI launch URL of your Warpwire site - for example, "https://example.warpwire.com/api/lti/".  Make sure you include the "https://", as well as the trailing slash.<br/><br/>';

$string['setting_key_label'] = 'Your Warpwire Consumer Key';
$string['setting_key_desc'] = 'The provided consumer key for your Warpwire site.<br/><br/>';

$string['setting_secret_label'] = 'Your Warpwire Consumer Secret';
$string['setting_secret_desc'] = 'The provided consumer secret for your Warpwire site.<br/><br/>';

$string['setting_admin_username_label'] = 'Your Warpwire administrative username';
$string['setting_admin_username_desc'] = 'The provided administrative username for your Warpwiresite (not required).<br/><br/>';

$string['setting_admin_password_label'] = 'Your Warpwire administrative password';
$string['setting_admin_password_desc'] = 'The provided administrative password for your Warpwire site (not required).<br/><br/>';

$string['setting_externalpage_trial'] = 'Warpwire Trial';

$string['notice_already_configured'] = 'Trial has already been set up';
$string['notice_cannot_start_trial'] = 'This instance of Moodle does not support Warpwire trials';

$string['notice_reset_confirmation'] = 'Are you sure you want to reset settings? If you do, your connection to Warpwire will no longer work.';
$string['notice_reset_complete'] = 'Configuration has been reset';
$string['action_reset_confirm'] = 'Reset Settings';
$string['action_reset_cancel'] = 'Go Back';

$string['notice_invalid_action'] = 'Invalid action.';

$string['notice_setup_error'] = 'Setup request failed. Please try again or contact support.';
$string['notice_setup_error_noauth'] = 'Setup request failed. Missing Warpwire credentials. Please contact support.';
$string['notice_setup_error_noretry'] = 'Setup request failed. Please contact support.';
$string['notice_setup_error_client_exists'] = 'Warpwire site already exists. Please contact support to get setup information.';
$string['notice_setup_success'] = 'Setup request has been sent.';

$string['notice_client_identifier'] = 'Account ID: <b>{$a}</b>';
$string['notice_account_type'] = 'Account Type: <b>{$a}</b>';
$string['notice_usage_limits_trial'] = 'During your trial, you may only upload a small amount of content. If you\'d like to upgrade to a full version, please contact Moodle.us support.';
$string['notice_usage_limits'] = 'Warpwire limits the amount of content that may be added or viewed before purchasing more. If you wish to upgrade, please contact Moodle.us support.';
$string['notice_error_usage'] = 'Unable to retrieve usage information.';
$string['notice_getting_started'] = 'Your site is not configured to use Warpwire. If you have a Warpwire site already, you must enter the credentials in the settings area below. If you do not have a Warpwire site, you may start a trial by clicking "Start Trial". This will create a new Warpwire site and configure your Moodle site automatically.';
$string['notice_getting_started_no_trial'] = 'Your site is not configured to use Warpwire. If you have a Warpwire site already, you must enter the credentials in the settings area below.';

$string['action_start_trial'] = 'Start Trial';
$string['action_reset_settings'] = 'Reset Settings';

$string['setup_error_client_exists'] = '';

$string['lti_tool_description'] = 'Warpwire will record a grade as the percentage of new video content that a viewer watches.';
