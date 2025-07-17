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

/**
 * Options helper for the Moodle tiny_warpwire plugin.
 *
 * @module      tiny_warpwire/options
 * @copyright   2025 Cadmium warpwire-support@gocadmium.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

// Helper variables for the option names.
const warpwireUrl = getPluginOptionName(pluginName, 'warpwire_url');
const warpwireLocalUrl = getPluginOptionName(pluginName, 'warpwire_local_url');

/**
 * Options registration function.
 *
 * @param {tinyMCE} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    // For each option, register it with the editor.
    // Valid type are defined in https://www.tiny.cloud/docs/tinymce/6/apis/tinymce.editoroptions/
    registerOption(warpwireUrl, {
        processor: 'string',
    });

    registerOption(warpwireLocalUrl, {
        processor: 'string',
    });
};

/**
 * Fetch the warpwire url value for this editor instance.
 *
 * @param {tinyMCE} editor The editor instance to fetch the value for
 * @returns {object} The value of the myFirstProperty option
 */
export const getWarpwireUrl = (editor) => editor.options.get(warpwireUrl);

/**
 * Fetch the warpwire local url value for this editor instance.
 * @param {tinyMCE} editor The editor instance to fetch the value for
 * @return {object} The value of the mySecondProperty option
 */
export const getWarpwireLocalUrl = (editor) => editor.options.get(warpwireLocalUrl);