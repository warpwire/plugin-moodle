<?php

/**
 * @file
 * Main file for warpwire module.
 */

require_once "../../config.php";
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

require_login();

global $USER, $COURSE;

$course = $DB->get_record('course', array('id' => $_GET['course_id']));

warpwire_external_content($USER, $course);
exit;


function warpwire_external_content($user, $course) {
  global $CFG;

  $lti_url_parts = parse_url(get_config('local_warpwire', 'warpwire_lti'));
  $url_parts = parse_url($_GET['url']);

  $host_match = false;

  // the allowed url list
  $host_urls = array(
    'warpwire.com',
    $lti_url_parts['host']
  );

  // iterate through valid host urls, and set match if found
  foreach($host_urls as $host_url) {
    if (strpos(strtolower($url_parts['host']), strtolower($host_url)) !== false) {
      $host_match = true;
      break;
    }
  }

  // host is not found in the allowed list - redirect to the provided url
  if(!$host_match) {
    header('Location: '.$_GET['url']);
    exit;
  }  

  // user roles
  $roles = lti_get_ims_role($user, null, $course->id);

  // LTI parameters
  $params = array(
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_consumer_key' => get_config('local_warpwire', 'warpwire_key'),
    'oauth_version' => '1.0',
    'oauth_timestamp' => time() + 300000,
    'oauth_nonce' => md5(rand()),
    'context_id' => $course->id,
    'context_label' => $course->shortname,
    'context_title' => $course->fullname,
    'tool_consumer_info_product_family_code' => 'moodle',
    'ext_lms' => 'moodle-2',
    'lis_person_name_family' => $user->lastname,
    'lis_person_name_full' => $user->firstname . ' ' . $user->lastname,
    'lis_person_name_given' => $user->firstname,
    'lis_person_contact_email_primary' => $user->email,
    'lis_course_section_sourcedid' => $course->idnumber,
    'lti_message_type' => 'basic-lti-launch-request',
    'lti_version' => 'LTI-1p0',
    'roles' => $roles,
    'user_id' => $user->username,
    'lis_person_sourcedid' => $user->username,
    'custom_context_id' => $course->id,
    'custom_plugin_info' => '',
    'launch_presentation_return_url' => $CFG->wwwroot . '/local/warpwire/html/warpwire.html'
  );

  if( ($lti_url_parts['host'] != $url_parts['host'])
    || ($lti_url_parts['path'] != $url_parts['path'])
    || (!empty($url_parts['query']))
  ) {
    $params['returnContext'] = $_GET['url'];
  }
  
  // build the OAuth signature
  $sig = build_signature('POST', get_config('local_warpwire', 'warpwire_lti'), $params, get_config('local_warpwire', 'warpwire_secret'));

  $params['oauth_signature'] = $sig;

  // build the form to submit LTI credentials
  $content = '<html><head></head><body><form id="warpwire_lti_post" method="POST" enctype="application/x-www-form-urlencoded" action="'.get_config('local_warpwire', 'warpwire_lti').'">'.PHP_EOL;
  foreach($params AS $key => $value) {
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

function build_signature($method, $url, $params, $secret) {

  // parse the provided url to be normalized
  $url_parts = parse_url($url);
  $normalized_url = $url_parts['scheme'] . "://" . $url_parts['host'] . $url_parts['path'];

  // Remove oauth_signature if present
  // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
  if (isset($params['oauth_signature'])) {
    unset($params['oauth_signature']);
  }

  $signable_params = build_http_query($params);

  $parts = array(
    $method,
    $normalized_url,
    $signable_params
  );

  $base_string = implode('&', urlencode_rfc3986($parts));

  $key_parts = array(
    $secret,
    ""
  );

  $key_parts = urlencode_rfc3986($key_parts);
  $key = implode('&', $key_parts);

  $computed_signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));
  return $computed_signature;
}

function build_http_query($params) {
  if (!$params) return '';

  // Urlencode both keys and values
  $keys = urlencode_rfc3986(array_keys($params));
  $values = urlencode_rfc3986(array_values($params));
  $params = array_combine($keys, $values);

  // Parameters are sorted by name, using lexicographical byte value ordering.
  // Ref: Spec: 9.1.1 (1)
  uksort($params, 'strcmp');

  $pairs = array();
  foreach ($params as $parameter => $value) {
    if (is_array($value)) {
      // If two or more parameters share the same name, they are sorted by their value
      // Ref: Spec: 9.1.1 (1)
      natsort($value);
      foreach ($value as $duplicate_value) {
        $pairs[] = $parameter . '=' . $duplicate_value;
      }
    } else {
      $pairs[] = $parameter . '=' . $value;
    }
  }
  // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
  // Each name-value pair is separated by an '&' character (ASCII code 38)
  return implode('&', $pairs);
}

function urlencode_rfc3986($input) {
  if (is_array($input)) {
    return array_map('urlencode_rfc3986', $input);
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
