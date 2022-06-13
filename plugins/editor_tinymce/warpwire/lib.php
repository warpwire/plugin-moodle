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

class tinymce_warpwire extends editor_tinymce_plugin
{
    /** @var array list of buttons defined by this plugin */
    protected $buttons = array('warpwire');

    protected function update_init_params(array &$params, context $context,
        array $options = null) {
        global $CFG, $COURSE;

        $filters = filter_get_active_in_context($context);
        $enabled  = array_key_exists('warpwire', $filters) || array_key_exists('filter/warpwire', $filters);

        // If warpwire filter is disabled, do not add button.
        if (!$enabled || !\local_warpwire\utilities::isConfigured()) {
            return;
        }

        // build the query params to pass
        $url_params_query = http_build_query(array('mode' => 'plugin'), '', '&');

        $warpwireUrl = get_config('local_warpwire', 'warpwire_lti');
        $url_parts = parse_url($warpwireUrl . '?' . $url_params_query);

        $parameters = array();
        if(!empty($url_parts['query']))
            parse_str($url_parts['query'], $parameters);

        $url_parts['query'] = http_build_query($parameters, '', '&');

        $url = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].'?'.$url_parts['query'];

        $parts = array(
            'url' => $url,
            'course_id' => $COURSE->id
        );

        $partsString = http_build_query($parts, '', '&');

        $url = $CFG->wwwroot . '/local/warpwire/?' .$partsString;

        $params = $params + array(
            'warpwire_url' => $url,
            'warpwire_img' => $CFG->wwwroot . '/local/warpwire/pix/icon.gif'
        );

        $numrows = $this->count_button_rows($params);
        $this->add_button_after($params, $numrows, '|,warpwire');

        // Add JS file, which uses default name.
        $this->add_js_plugin($params);
    }

    /**
     * Counts the number of rows in TinyMCE editor (row numbering starts with 1)
     * Re-implementation of {@link lib/editor/tinymce/classes/plugin.php} in
     * Moodle v2.6+
     *
     * @override
     * @param array $params TinyMCE init parameters array
     * @return int the maximum existing row number
     */
    protected function count_button_rows(array &$params) {
        $maxrow = 1;
        foreach ($params as $key => $value) {
            if (preg_match('/^theme_advanced_buttons(\d+)$/', $key, $matches) &&
                    (int)$matches[1] > $maxrow) {
                $maxrow = (int)$matches[1];
            }
        }
        return $maxrow;
    }
}
