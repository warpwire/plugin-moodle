<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tiny_warpwire;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;

/**
 * Tiny Warpwire Plugin plugin for Moodle.
 *
 * @package     tiny_warpwire
 * @copyright   2025 Cadmium warpwire-support@gocadmium.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_configuration, plugin_with_buttons {

    /**
     * Get a list of the buttons provided by this plugin.
     *
     * @return array
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_warpwire/plugin',
        ];
    }

    /**
     * Pass options from the PHP to the JavaScript API of the plugin.
     * The local url is used to actually perform the LTI launch.
     * The warpwire url is used by the editor to verify the origin of
     * the data return from the LTI launch back to the editor.
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param ?\editor_tiny\editor $editor = null
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array {
        global $CFG, $COURSE;

        $warpwireurl = get_config('local_warpwire', 'warpwire_url');
        if (empty($warpwireurl)) {
            return ['warpwire_local_url' => $CFG->wwwroot . '/local/warpwire/html/setup.html'];
        }

        $ltiurl = \rtrim($warpwireurl, '/') . '/api/lti/';

        // Build the query params to pass.
        $urlparamsquery = http_build_query(['mode' => 'plugin'], '', '&');

        $urlparts = parse_url($ltiurl . '?' . $urlparamsquery);

        $parameters = [];
        if (!empty($urlparts['query'])) {
            parse_str($urlparts['query'], $parameters);
        }
        $urlparts['query'] = http_build_query($parameters, '', '&');

        $url = $urlparts['scheme'].'://'.$urlparts['host'].$urlparts['path'].'?'.$urlparts['query'];

        $parts = [
            'url' => $url,
            'course_id' => $COURSE->id,
        ];

        $partsstring = http_build_query($parts, '', '&');

        $url = $CFG->wwwroot . '/local/warpwire/?' .$partsstring;

        return([
            'warpwire_local_url' => $url,
            'warpwire_url' => $warpwireurl,
        ]);
    }
}
