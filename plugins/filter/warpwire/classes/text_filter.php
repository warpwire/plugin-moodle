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

namespace filter_warpwire;
use DOMDocument;
defined('MOODLE_INTERNAL') || die('Invalid access');

class text_filter extends \core_filters\text_filter {
    /**
     * Main function for displaying embedded content in text.
     *
     * @global mixed $COURSE
     * @global mixed $PAGE
     * @global stdClass $CFG
     * @global mixed $USER
     * @param string $text html
     * @param array $options - not used
     */
    public function filter($text, array $options = []) {
        global $COURSE, $PAGE, $CFG, $USER;

        // If upgrade is running, skip this filter.
        // This filter relies on library functions that will throw an error if called during an upgrade.
        if (!empty($CFG->upgraderunning)) {
            return $text;
        }

        $iframetemplate = '<iframe
          width="WIDTH"
          height="HEIGHT"
          src="URL"
          frameborder="0"
          scrolling="0"
          allow="autoplay *; encrypted-media *; fullscreen *;"
          title="Warpwire Media"
          allowfullscreen></iframe>';

        // Collect information about context to send to iframe.
        $modinfo = get_fast_modinfo($COURSE);
        $sections = $modinfo->get_section_info_all();

        // Check if this is serving data to the mobile app.
        $wstoken = null;
        if (WS_SERVER) {
            require_once($CFG->dirroot . '/admin/tool/mobile/lib.php');
            $wstoken = tool_mobile_get_token($USER->id);
        }

        // Match all warpwire shortcode instances returned from plugins.
        if (preg_match_all('/<img.*?>/is', $text, $matchescode)) {
            foreach ($matchescode[0] as $ci => $code) {
                $texttoreplace = $code;

                if (preg_match('/\[warpwire:(.*)?\]/is', urldecode($code), $matchesstring)) {
                    $url = htmlspecialchars_decode($matchesstring[1], ENT_COMPAT);

                    $currentsectionid = null;
                    foreach ($sections as $section) {
                        $sectioncontent = $section->summary;
                        if (strstr($sectioncontent, urlencode($url)) !== false) {
                            $currentsectionid = $section->id;
                            break;
                        }
                    }

                    // Default width and height values for iframe.
                    $iframewidth = 480;
                    $iframeheight = 360;

                    $urlparts = parse_url($url);

                    $parameters = [];
                    if (!empty($urlparts['query'])) {
                        parse_str($urlparts['query'], $parameters);
                    }

                    $urlparts['query'] = http_build_query($parameters, '', '&');

                    $url = $urlparts['scheme'].'://'.$urlparts['host'].$urlparts['path'].'?'.$urlparts['query'];

                    $parts = [
                        'url' => $url,
                        'course_id' => $COURSE->id,
                        'module_id' => isset($PAGE->cm->id) ? $PAGE->cm->id : '',
                        'section_id' => $currentsectionid,
                    ];

                    // Append wstoken if defined.
                    if (!empty($wstoken)) {
                        $parts['wstoken'] = $wstoken->token;
                    }

                    $partsstring = http_build_query($parts, '', '&');

                    // TODO: edit here.

                    $url = $CFG->wwwroot . '/local/warpwire/?' .$partsstring;

                    if (!empty($parameters['width'])) {
                        $iframewidth = $parameters['width'];
                    }
                    if (!empty($parameters['height'])) {
                        $iframeheight = $parameters['height'];
                    }

                    if (class_exists('DOMDocument')) {
                        $doc = new DOMDocument();
                        $doc->loadHTML($code);
                        $imagetags = $doc->getElementsByTagName('img');

                        foreach ($imagetags as $tag) {
                            $iframewidth = $tag->getAttribute('width');
                            $iframeheight = $tag->getAttribute('height');
                        }
                    }

                    $patterns = ['/URL/', '/WIDTH/', '/HEIGHT/'];
                    $replace = [$url, $iframewidth, $iframeheight];
                    $iframehtml = preg_replace($patterns, $replace, $iframetemplate);

                    // Replace the shortcode with the iframe html.
                    $text = str_replace($texttoreplace, $iframehtml, $text);
                }
            }
        }

        // Match all warpwire shortcode instances manually inserted.
        if (preg_match_all('/\[warpwire(\:(.+))?( .+)?\](.+)?\/a>/isU', $text, $matchescode)) {
            foreach ($matchescode[0] as $index => $code) {
                $texttoreplace = $matchescode[0][$index];

                $url = '';
                if (!empty($matchescode[3][$index])) {
                    $url = preg_replace('/^ href=("|\')/', '', $matchescode[3][$index]);
                }

                $url = htmlspecialchars_decode($url, ENT_COMPAT);

                $currentsectionid = null;
                foreach ($sections as $section) {
                    $sectioncontent = $section->summary;
                    if (strstr($sectioncontent, urlencode($url)) !== false) {
                        $currentsectionid = $section->id;
                        break;
                    }
                }

                // Default width and height values for iframe.
                $iframewidth = 480;
                $iframeheight = 360;

                $urlparts['query'] = http_build_query($parameters, '', '&');

                $url = $urlparts['scheme'].'://'.$urlparts['host'].$urlparts['path'].'?'.$urlparts['query'];

                $parts = [
                    'url' => $url,
                    'course_id' => $COURSE->id,
                    'module_id' => isset($PAGE->cm->id) ? $PAGE->cm->id : '',
                    'section_id' => $currentsectionid,
                ];

                // Append wstoken if defined.
                if (!empty($wstoken)) {
                    $parts['wstoken'] = $wstoken->token;
                }

                $partsstring = http_build_query($parts, '', '&');

                // TODO: edit here.

                $url = $CFG->wwwroot . '/local/warpwire/?' .$partsstring;

                if (!empty($parameters['width'])) {
                    $iframewidth = $parameters['width'];
                }
                if (!empty($parameters['height'])) {
                    $iframeheight = $parameters['height'];
                }

                $patterns = ['/URL/', '/WIDTH/', '/HEIGHT/'];
                $replace = [$url, $iframewidth, $iframeheight];
                $iframehtml = preg_replace($patterns, $replace, $iframetemplate);

                // Replace the shortcode with the iframe html.
                $text = str_replace($texttoreplace, $iframehtml, $text);
            }
        }

        return $text;
    }
}
