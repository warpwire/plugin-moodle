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

$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_warpwire']);

if (!confirm_sesskey()) {
    redirect($returnurl);
}

$action = optional_param('action', false, PARAM_TEXT);
$isconfigured = \local_warpwire\utilities::isConfigured();
$canstarttrial = \local_warpwire\utilities::canStartTrial();

switch($action) {
    case 'setup':
        if ($isconfigured) {
            redirectandexit(get_string('notice_already_configured', 'local_warpwire'));
        } else if (!$canstarttrial) {
            redirectandexit(get_string('notice_cannot_start_trial', 'local_warpwire'));
        }

        resetconfiguration();
        setuptrial();
        break;
    default:
        redirectandexit(get_string('notice_invalid_action', 'local_warpwire'));
        break;
}

function resetconfiguration() {
    // Reset all configuration to make sure we have a clean slate.
    set_config('setup_status', null, 'local_warpwire');
    set_config('setup_status_message', null, 'local_warpwire');
    \local_warpwire\utilities::setConfigLog('warpwire_url', null);
    \local_warpwire\utilities::setConfigLog('warpwire_key', null);
    \local_warpwire\utilities::setConfigLog('warpwire_secret', null);
    \local_warpwire\utilities::setConfigLog('warpwire_admin_username', null);
    \local_warpwire\utilities::setConfigLog('warpwire_admin_password', null);
    set_config('warpwire_auth_token', null);
}

function setuptrial() {
    global $CFG;

    try {
        $warpwirewebhookurl = $CFG->warpwireWebhookUrl;
        $warpwirewebhookauthkey = $CFG->warpwireWebhookAuthKey;
        $warpwirewebhookauthsecret = $CFG->warpwireWebhookAuthSecret;

        $domain = parse_url($CFG->wwwroot, PHP_URL_HOST);

        $site = get_site();
        $shortname = $site->fullname;
        $longname = $site->fullname;

        $payload = [
            'domain' => $domain,
            'login_domain' => $domain,
            'short_name' => $shortname,
            'long_name' => $longname,
            'dry_run' => false,
        ];

        \local_warpwire\utilities::errorLogLong($payload, 'WARPWIRE TRIAL SETUP');

        $decoded = \local_warpwire\utilities::makePostRequest($warpwirewebhookurl, $payload,
        $warpwirewebhookauthkey, $warpwirewebhookauthsecret);
    } catch (\Exception $ex) {
        \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE TRIAL SETUP');

        set_config('setup_status', 'error', 'local_warpwire');

        if (strstr($ex->getMessage(), 'Client already exists')) {
            set_config('setup_status_message', get_string('notice_setup_error_client_exists', 'local_warpwire'), 'local_warpwire');
            redirectandexit(get_string('notice_setup_error_client_exists', 'local_warpwire'));
        } else {
            set_config('setup_status_message', get_string('notice_setup_error', 'local_warpwire'), 'local_warpwire');
            redirectandexit(get_string('notice_setup_error', 'local_warpwire'));
        }
    }

    try {
        $task = new \local_warpwire\setup_status_task();
        $task->set_custom_data([
            'status_url' => $decoded['status_url'],
        ]);
        $result = \core\task\manager::queue_adhoc_task($task);

        set_config('setup_status', 'queued', 'local_warpwire');
        set_config('setup_status_message', get_string('notice_setup_success', 'local_warpwire'), 'local_warpwire');

        \local_warpwire\utilities::errorLogLong('Task queued with ID: ' . $result, 'WARPWIRE TRIAL SETUP');
    } catch (\Exception $ex) {
        \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE TRIAL SETUP');

        set_config('setup_status', 'error', 'local_warpwire');
        set_config('setup_status_message', get_string('notice_setup_error_noretry', 'local_warpwire'), 'local_warpwire');

        redirectandexit(get_string('notice_setup_error_noretry', 'local_warpwire'));
    }

    redirectandexit(get_string('notice_setup_success', 'local_warpwire'));
}

function redirectandexit($message) {
    global $OUTPUT, $returnurl;

    redirect($returnurl);

    echo $OUTPUT->header();
    echo \html_writer::tag('p', $message);
    echo $OUTPUT->footer();

    exit;
}
