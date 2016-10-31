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
 * Atto warpwire plugin install script.
 *
 * @package    atto_warpwire
 * @copyright  2016 Warpwire  <warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add the warpwire plugin to the Atto toolbar config if the media plugin is enabled.
 *
 * @return bool
 */
function xmldb_atto_warpwire_install() {
    global $CFG;

    $toolbar = get_config('editor_atto', 'toolbar');
    $pos = stristr($toolbar, 'warpwire');

    if (!$pos) {
        // Add imagedragdrop after image plugin.
        $toolbar = preg_replace('/(.+?=.+?)media($|\s|,)/m', '$1media, warpwire$2', $toolbar, 1);
    }

    return true;
}
