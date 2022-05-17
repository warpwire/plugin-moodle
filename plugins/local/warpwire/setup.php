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

admin_externalpage_setup('warpwire_trial');

$pageurl = new moodle_url('/local/warpwire/setup.php');

$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_warpwire']);

if (!confirm_sesskey()) {
    redirect($returnurl);
}

$action = optional_param('action', false, PARAM_TEXT);
$isConfigured = \local_warpwire\utilities::isConfigured();

switch($action) {
    case 'setup':
        if ($isConfigured) {
            redirectAndExit(get_string('notice_already_configured', 'local_warpwire'));
        }

        resetConfiguration();
        setupTrial();
        break;
    // TODO: add status check for AJAX?
    default:
        redirectAndExit(get_string('notice_invalid_action', 'local_warpwire'));
        break;
}

function resetConfiguration() {
    // reset all configuration to make sure we have a clean slate
    set_config('setup_status', null, 'local_warpwire');
    set_config('setup_status_message', null, 'local_warpwire');
    set_config('warpwire_lti', null, 'local_warpwire');
    set_config('warpwire_key', null, 'local_warpwire');
    set_config('warpwire_secret', null, 'local_warpwire');
    set_config('warpwire_url', null, 'local_warpwire');
    set_config('warpwire_admin_username', null, 'local_warpwire');
    set_config('warpwire_admin_password', null, 'local_warpwire');
    set_config('warpwire_auth_token', null, 'local_warpwire');
    set_config('warpwire_trial_status', null, 'local_warpwire');
    set_config('warpwire_trial_status_message', null, 'local_warpwire');
}

function setupTrial() {
    global $CFG;

    try {
        $webhookUrl = 'https://moodle.testing-public.warpwire.net/webhook/client-moodleus/';
        $webhookAuthKey = 't9dCEzw5QaIkvGy1';
        $webhookAuthSecret = 'dDX8o6r3xghaUthG3WNNSTM90NKGuj9YLjeSeyc6t6AcBKIQWvQVLV39xspqFnSo';

        $domain = parse_url($CFG->wwwroot, PHP_URL_HOST);

        $site = get_site();
        $shortName = $site->fullname;
        $longName = $site->fullname;

        $payload = [
            'domain' => $domain,
            'login_domain' => $domain,
            'short_name' => $shortName,
            'long_name' => $longName,
            // TODO: change to use a Moodle-specific verification
            'verification_key' => 'VERIFICATION_TEST',
            'dry_run' => false
        ];

        \local_warpwire\utilities::errorLogLong($payload, 'WARPWIRE TRIAL SETUP');

        $decoded = \local_warpwire\utilities::makePostRequest($webhookUrl, $payload, $webhookAuthKey, $webhookAuthSecret);
    } catch(\Exception $ex) {
        \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE TRIAL SETUP');

        set_config('setup_status', 'error', 'local_warpwire');

        if (strstr($ex->getMessage(), 'Client already exists')) {
            set_config('setup_status_message', 'Warpwire site already exists. Please contact support to get setup information.', 'local_warpwire');
        } else {
            set_config('setup_status_message', 'Setup request failed.', 'local_warpwire');
        }

        messageAndExit(get_string('notice_setup_error', 'local_warpwire'));
    }

    try {
        $task = new \local_warpwire\setup_status_task();
        $task->set_custom_data([
            'status_url' => $decoded['status_url']
        ]);
        $result = \core\task\manager::queue_adhoc_task($task);

        set_config('setup_status', 'queued', 'local_warpwire');
        set_config('setup_status_message', 'Setup request has been sent', 'local_warpwire');

        \local_warpwire\utilities::errorLogLong('Task queued with ID: ' . $result, 'WARPWIRE TRIAL SETUP');
    } catch(\Exception $ex) {
        \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE TRIAL SETUP');

        set_config('setup_status', 'error', 'local_warpwire');
        set_config('setup_status_message', 'Setup request failed.', 'local_warpwire');

        messageAndExit(get_string('notice_setup_error', 'local_warpwire'));
    }

    redirectAndExit(get_string('notice_setup_initiated', 'local_warpwire'));
}

function redirectAndExit($message) {
    global $OUTPUT, $returnurl;

    redirect($returnurl);

    echo $OUTPUT->header();
    echo \html_writer::tag('p', $message);
    echo $OUTPUT->footer();

    exit;
}

function messageAndExit($message) {
    global $OUTPUT;

    echo $OUTPUT->header();
    echo \html_writer::tag('p', $message);
    echo $OUTPUT->footer();

    exit;
}
