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
 * Commands helper for the Moodle tiny_warpwire plugin.
 *
 * @module      tiny_warpwire/commands
 * @copyright   2025 Cadmium warpwire-support@gocadmium.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getButtonImage} from 'editor_tiny/utils';
import {get_string as getString} from 'core/str';
import {
    component,
    icon,
    buttonName,
} from './common';
import {getWarpwireLocalUrl,getWarpwireUrl} from './options';

/**
 * @param {string} sParam
 * @param {string} sPageURL
 * @returns
 */
function GetURLParameter(sParam, sPageURL) {
    if (typeof sPageURL == 'undefined') {
        return (null);
    }

    sPageURL = sPageURL.substring(sPageURL.indexOf("?") + 1);

    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }

    return (null);
}

/**
 * Handle the action for your plugin.
 * @param {TinyMCE.editor} editor The tinyMCE editor instance.
 */
const handleAction = (editor) => {
    const localWarpwireUrl = getWarpwireLocalUrl(editor);
    const warpwireUrl = getWarpwireUrl(editor);

    // The message comes back from the child window after the user has selected warpwire
    // assets to embed in the editor.
    window.addEventListener('message', function(event) {
        if (event.data.message === "deliverResult") {
            const warpwireAssets = JSON.parse(event.data.result);
            for (let i = 0; i < warpwireAssets.length; i++) {
                const imgNode = document.createElement('img');
                imgNode.setAttribute('class', "_ww_img");

                let img_src = warpwireAssets[i]._ww_img.replace("http://", "https://");
                const source = warpwireAssets[i]._ww_src.replace("http://", "https://");

                let sourceUrl = decodeURIComponent(source);
                sourceUrl = sourceUrl.replace(/^\[warpwire:/, '');
                sourceUrl = sourceUrl.replace(/]$/, '');

                let img_width = GetURLParameter('width', sourceUrl);
                if (img_width === null) {
                    img_width = 400;
                }
                imgNode.setAttribute('width', img_width);
                let img_height = GetURLParameter('height', sourceUrl);
                if (img_height === null) {
                    img_height = 400;
                }
                imgNode.setAttribute('height', img_height);

                const sep = img_src.indexOf('?') === -1 ? '?' : '&';
                img_src = img_src + sep + 'ww_code=' + source;

                imgNode.setAttribute('src', img_src);

                if (warpwireAssets[i]) {
                    try {
                        // Assets are added as an image. The warpwire filter will convert them to
                        // videos when displayed in the page.
                        editor.insertContent(imgNode.outerHTML);
                    } catch (e) { }
                }
            }
            event.data.message = '';
        }
    }, { once: true });

    const child = window.open(localWarpwireUrl, '_wwPlugin', 'width=400, height=500');

    var leftDomain = false;
    var interval = setInterval(function() {
        try {
            // Only safe to access if same-origin
            if (child.location.hostname === window.location.hostname) {
                if (leftDomain && child.document.readyState === "complete") {
                    // we're here when the child window returned to moodle domain
                    clearInterval(interval);
                    child.postMessage({ message: "requestResult" }, warpwireUrl);
                }
            } else {
                // this code should never be reached as the x-site security check throws
                // but just in case
                leftDomain = true;
            }
        } catch (e) {
            // SecurityError is expected until the child returns to moodle origin
            if (child.closed) {
                clearInterval(interval);
            }
            // navigated to another domain
            leftDomain = true;
        }
    }, 500);
};

/**
 * Get the setup function for the buttons.
 *
 * This is performed in an async function which ultimately returns the registration function as the
 * Tiny.AddOnManager.Add() function does not support async functions.
 *
 * @returns {function} The registration function to call within the Plugin.add function.
 */
export const getSetup = async() => {
    const [
        buttonImage,
        buttonText,
    ] = await Promise.all([
        getButtonImage('icon-dark', component),
        getString('buttontitle', component),
    ]);

    return (editor) => {
        // Register the Moodle SVG as an icon suitable for use as a TinyMCE toolbar button.
        editor.ui.registry.addIcon(icon, buttonImage.html);
        editor.ui.registry.addButton(buttonName, {
            icon: component,
            tooltip: buttonText,
            onAction: () => handleAction(editor),
        });

        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: buttonText,
            onAction: () => handleAction(editor),
        });
    };
};
