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

define('LOCAL_WARPWIRE_DEFAULT_KEY', 'warpwire_key');
define('LOCAL_WARPWIRE_KEY_PARAMETER', 'warpwire_key');

define('LOCAL_WARPWIRE_DEFAULT_SECRET', 'warpwire_secret');
define('LOCAL_WARPWIRE_SECRET_PARAMETER', 'warpwire_secret');

$path = dirname(__FILE__) . '/library';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

/**
 * Main function for displaying a Warpwire media libraries
 *
 * @global stdClass $CFG global config
 * @param stdClass $user
 * @param stdClass $course
 * @param string $sectionid
 * @param string $moduleid
 */
function local_warpwire_external_content($user, $course, $sectionid, $moduleid) {
    global $CFG;
    $warpwireurl = get_config('local_warpwire', 'warpwire_url');
    if (empty($warpwireurl)) {
        echo \html_writer::tag('p', get_string('content_not_configured', 'local_warpwire'));
        return;
    }

    $warpwireltiurl = \rtrim($warpwireurl, '/') . '/api/lti/';

    $ltiurlparts = parse_url($warpwireltiurl);
    $url = required_param('url', PARAM_URL);
    $urlparts = parse_url($url);

    $hostmatch = false;

    // The allowed url list.
    $hosturls = [
        'warpwire.com',
        $ltiurlparts['host'],
    ];

    // Iterate through valid host urls, and set match if found.
    foreach ($hosturls as $hosturl) {
        if (strpos(strtolower($urlparts['host']), strtolower($hosturl)) !== false) {
            $hostmatch = true;
            break;
        }
    }

    // Host is not found in the allowed list - redirect to the provided url.
    if (!$hostmatch) {
        header('Location: '.$url);
        exit;
    }

    // User roles.
    $roles = lti_get_ims_role($user, null, $course->id, false);

    // LTI parameters.
    $params = [
        'oauth_version' => '1.0',
        'oauth_nonce' => md5(mt_rand()),
        'oauth_timestamp' => time() + 600,
        'oauth_consumer_key' => get_config('local_warpwire', 'warpwire_key'),
        'user_id' => $user->id,
        'lis_person_sourcedid' => $user->username,
        'roles' => $roles,
        'context_id' => $course->id,
        'context_label' => $course->shortname,
    ];
    if ($course->format == 'site') {
        $parms['context_type'] = 'Group';
    } else {
        $params['context_type'] = 'CourseSection';
        $params['lis_course_section_sourcedid'] = $course->idnumber;
    }

    $params['lis_course_section_sourcedid'] = $course->idnumber;
    $params['lis_person_name_given'] = $user->firstname;
    $params['lis_person_name_family'] = $user->lastname;
    $params['lis_person_name_full'] = fullname($user);
    $params['ext_user_username'] = $user->username;
    $params['lis_person_contact_email_primary'] = $user->email;
    $params['launch_presentation_locale'] = current_language();
    $params['ext_lms'] = 'moodle-2';
    $params['tool_consumer_info_product_family_code'] = 'moodle';
    $params['tool_consumer_info_version'] = strval($CFG->version);
    // Add oauth_callback to be compliant with the 1.0A spec.
    $params['oauth_callback'] = 'about:blank';
    $params['lti_version'] = 'LTI-1p0';
    $params['lti_message_type'] = 'ContentItemSelection';

    if (!empty($CFG->mod_lti_institution_name)) {
        $params['tool_consumer_instance_name'] = trim(html_to_text($CFG->mod_lti_institution_name, 0));
    } else {
        $params['tool_consumer_instance_name'] = get_site()->shortname;
    }

    $params['tool_consumer_instance_description'] = trim(html_to_text(get_site()->fullname, 0));
    $params['launch_presentation_return_url'] = $CFG->wwwroot . '/local/warpwire/html/warpwire.html';
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['custom_context_id'] = $course->id;
    $params['custom_section_id'] = $sectionid;
    $params['custom_module_id'] = $moduleid;
    $params['custom_plugin_info'] = '';

    if (($ltiurlparts['host'] != $urlparts['host'])
        || ($ltiurlparts['path'] != $urlparts['path'])
        || (!empty($urlparts['query']))
    ) {
        $params['returnContext'] = $url;
    }

    // Build the OAuth signature.
    $sig = local_warpwire_build_signature('POST', $warpwireltiurl, $params, get_config('local_warpwire', 'warpwire_secret'));

    $params['oauth_signature'] = $sig;

    // Build the form to submit LTI credentials.
    $content = '<html><head></head><body><form id="warpwire_lti_post"
    method="POST" enctype="application/x-www-form-urlencoded" action="'.$warpwireltiurl.'">'.PHP_EOL;
    foreach ($params as $key => $value) {
        $content .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
    }
    $content .= '<div id="warpwire_display_submit"><p>Please press the Submit button to continue.</p>';
    $content .= '<p><input type="submit" value="Submit"></p></div>';
    $content .= '</form>';
    $content .= '
    <script>
      (function(){
        var warpwireDisplaySection = document.getElementById("warpwire_display_submit");
        if( (warpwireDisplaySection) && (warpwireDisplaySection != null) ) {
          warpwireDisplaySection.style.display = "none";

          setTimeout(function(){
            warpwireDisplaySection.style.display = "block";
          }, 4000);
        }

        var warpwireLTIForm = document.getElementById("warpwire_lti_post");

        if( (!warpwireLTIForm) || (warpwireLTIForm == null) )
          return(false);

        warpwireLTIForm.submit();
      })();
    </script>';

    $content .= '</body></html>';

    echo($content);
    exit;
}

/**
 * Build OAuth signature
 *
 * @param string $method POST
 * @param string $url
 * @param mixed $params
 * @param string $secret
 * @return string url signature
 */
function local_warpwire_build_signature($method, $url, $params, $secret) {
    // Parse the provided url to be normalized.
    $urlparts = parse_url($url);
    $normalizedurl = $urlparts['scheme'] . "://" . $urlparts['host'] . $urlparts['path'];

    // Remove oauth_signature if present.
    // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.").
    if (isset($params['oauth_signature'])) {
        unset($params['oauth_signature']);
    }

    $signableparams = local_warpwire_build_http_query($params);

    $parts = [
        $method,
        $normalizedurl,
        $signableparams,
    ];

    $basestring = implode('&', local_warpwire_url_encode_rfc3986($parts));

    $keyparts = [
        $secret,
        "",
    ];

    $keyparts = local_warpwire_url_encode_rfc3986($keyparts);
    $key = implode('&', $keyparts);

    $computedsignature = base64_encode(hash_hmac('sha1', $basestring, $key, true));
    return $computedsignature;
}

/**
 * Builds an HTTP query string from an associative array of parameters.
 *
 * @param array $params An associative array of parameters.
 * @return string The built query string.
 */
function local_warpwire_build_http_query($params) {
    if (!$params) {
        return '';
    }

    // Urlencode both keys and values.
    $keys = local_warpwire_url_encode_rfc3986(array_keys($params));
    $values = local_warpwire_url_encode_rfc3986(array_values($params));
    $params = array_combine($keys, $values);

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1).
    uksort($params, 'strcmp');

    $pairs = [];
    foreach ($params as $parameter => $value) {
        if (is_array($value)) {
            // If two or more parameters share the same name, they are sorted by their value.
            // Ref: Spec: 9.1.1 (1).
            natsort($value);
            foreach ($value as $duplicatevalue) {
                $pairs[] = $parameter . '=' . $duplicatevalue;
            }
        } else {
            $pairs[] = $parameter . '=' . $value;
        }
    }
    // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61).
    // Each name-value pair is separated by an '&' character (ASCII code 38).
    return implode('&', $pairs);
}

/**
 * URL-encodes a string or an array of strings according to RFC 3986.
 *
 * This function encodes a string or an array of strings to be used in a URL query string.
 * It replaces spaces with '+' and ensures that the tilde character '~' is encoded as '%7E'.
 *
 * @param mixed $input The input to be URL-encoded. This can be a string or an array of strings.
 * @return mixed The URL-encoded input.
 */
function local_warpwire_url_encode_rfc3986($input) {
    if (is_array($input)) {
        return array_map('local_warpwire_url_encode_rfc3986', $input);
    } else if (is_scalar($input)) {
        return str_replace(
            '+',
            ' ',
            str_replace('%7E', '~', rawurlencode($input))
        );
    } else {
        return '';
    }
}

/**
 * Reset all Warpwire configurations.
 *
 * @return void
 */
function local_warpwire_reset_configuration() {
    set_config('setup_status', null, 'local_warpwire');
    set_config('setup_status_message', null, 'local_warpwire');
    \local_warpwire\utilities::set_config_log('warpwire_url', null);
    \local_warpwire\utilities::set_config_log('warpwire_key', null);
    \local_warpwire\utilities::set_config_log('warpwire_secret', null);
    \local_warpwire\utilities::set_config_log('warpwire_admin_username', null);
    \local_warpwire\utilities::set_config_log('warpwire_admin_password', null);
    set_config('warpwire_auth_token', null);
}

/**
 * Sets up a Warpwire trial for the moodle site and queues an adhoc task to
 * check the setup status of the trial.
 *
 * @global stdClass $CFG config
 * @return void
 */
function local_warpwire_setup_trial() {
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

        $decoded = \local_warpwire\utilities::make_post_request(
            $warpwirewebhookurl,
            $payload,
            $warpwirewebhookauthkey,
            $warpwirewebhookauthsecret,
        );
    } catch (\Exception $ex) {

        set_config('setup_status', 'error', 'local_warpwire');

        if (strstr($ex->getMessage(), 'Client already exists')) {
            set_config('setup_status_message', get_string('notice_setup_error_client_exists', 'local_warpwire'), 'local_warpwire');
            local_warpwire_redirect_and_exit(get_string('notice_setup_error_client_exists', 'local_warpwire'));
        } else {
            set_config('setup_status_message', get_string('notice_setup_error', 'local_warpwire'), 'local_warpwire');
            local_warpwire_redirect_and_exit(get_string('notice_setup_error', 'local_warpwire'));
        }
    }

    try {
        $task = new \local_warpwire\check_trial_setup_task();
        $task->set_custom_data([
            'attempt_count' => 1,
            'status_url' => $decoded['status_url'],
        ]);
        \core\task\manager::queue_adhoc_task($task);

        set_config('setup_status', 'queued', 'local_warpwire');
        set_config('setup_status_message', get_string('notice_setup_success', 'local_warpwire'), 'local_warpwire');

    } catch (\Exception $ex) {

        set_config('setup_status', 'error', 'local_warpwire');
        set_config('setup_status_message', get_string('notice_setup_error_noretry', 'local_warpwire'), 'local_warpwire');

        local_warpwire_redirect_and_exit(get_string('notice_setup_error_noretry', 'local_warpwire'));
    }

    local_warpwire_redirect_and_exit(get_string('notice_setup_success', 'local_warpwire'));
}

/**
 * Redirects the user to a specified URL and displays a message before exiting the script.
 *
 * @global object $OUTPUT An object that handles output operations.
 * @global string $returnurl The URL to redirect the user to.
 * @param string $message The message to display to the user before redirecting.
 * @return void
 */
function local_warpwire_redirect_and_exit($message) {
    global $OUTPUT, $returnurl;

    redirect($returnurl);

    echo $OUTPUT->header();
    echo \html_writer::tag('p', $message);
    echo $OUTPUT->footer();

    exit;
}
