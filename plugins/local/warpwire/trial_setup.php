<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('warpwire_trial_setup');

$pageurl = new moodle_url('/local/warpwire/trial_setup.php');

$returnurl = new moodle_url('/admin/settings.php', ['section' => 'local_warpwire']);

if (!confirm_sesskey()) {
    redirect($returnurl);
}

$reset = optional_param('reset', false, PARAM_INT);

$isConfigured = get_config('local_warpwire', 'is_configured');

if ($isConfigured && $reset != 1) {
    redirect($returnurl);

    echo $OUTPUT->header();
    echo "<p>Trial has already been set up.</p>";
    echo $OUTPUT->footer();
    exit;
}

// reset all configuration to make sure we have a clean slate
set_config('is_configured', null, 'local_warpwire');
set_config('warpwire_trial_status', null, 'local_warpwire');
set_config('warpwire_trial_status_message', null, 'local_warpwire');
set_config('warpwire_lti', null, 'local_warpwire');
set_config('warpwire_key', null, 'local_warpwire');
set_config('warpwire_secret', null, 'local_warpwire');
set_config('warpwire_url', null, 'local_warpwire');
set_config('warpwire_admin_username', null, 'local_warpwire');
set_config('warpwire_admin_password', null, 'local_warpwire');

if ($reset == 1) {
    redirect($returnurl);

    echo $OUTPUT->header();
    echo "<p>Settings have been reset. You may close this window or tab.</p>";
    echo $OUTPUT->footer();
    exit;
}

$webhookUrl = 'https://moodle.testing-public.warpwire.net/webhook/client-moodleus/';
$webhookAuthKey = 't9dCEzw5QaIkvGy1';
$webhookAuthSecret = 'dDX8o6r3xghaUthG3WNNSTM90NKGuj9YLjeSeyc6t6AcBKIQWvQVLV39xspqFnSo';

$domain = parse_url($CFG->wwwroot, PHP_URL_HOST);

// TODO: this either needs to be specified and passed in as a parameter, or gathered from default course name
$shortName = 'New Site';
$longName = 'New Site';

$payload = [
    'domain' => $domain,
    'login_domain' => $domain,
    'short_name' => $shortName,
    'long_name' => $longName,
    // TODO: change to use a Moodle-specific verification
    'verification_key' => 'VERIFICATION_TEST',
    'dry_run' => false
];

\local_warpwire\utilities::errorLogLong($payload, 'WARPWIRE PAYLOAD');

try {
    $decoded = \local_warpwire\utilities::makePostRequest($webhookUrl, $payload, $webhookAuthKey, $webhookAuthSecret);
} catch(\Exception $ex) {
    \local_warpwire\utilities::errorLogLong((string)$ex, 'WARPWIRE TRIAL');

    set_config('warpwire_trial_status', 'error', 'local_warpwire');
    set_config('warpwire_trial_status_message', 'Setup request failed.', 'local_warpwire');

    echo $OUTPUT->header();
    echo "<p>Error: unable to initiate Warpwire trial. Please see error logs.</p>";
    echo $OUTPUT->footer();

    exit;
}

$task = new \local_warpwire\warpwire_status_task();
$task->set_custom_data([
    'status_url' => $decoded['status_url']
]);
$result = \core\task\manager::queue_adhoc_task($task);

set_config('warpwire_trial_status', 'queued', 'local_warpwire');
set_config('warpwire_trial_status_message', 'Setup request has been sent', 'local_warpwire');

\local_warpwire\utilities::errorLogLong('Task queued with ID: ' . $result, 'WARPWIRE TRIAL');

redirect($returnurl);

echo $OUTPUT->header();

echo "<p>Trial initiated. You may close this window or tab.</p>";

echo $OUTPUT->footer();
