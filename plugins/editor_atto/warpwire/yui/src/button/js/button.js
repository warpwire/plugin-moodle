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

/*
 * @package    atto_warpwire
 * @copyright  2016 Warpwire, Inc.  <warpwire.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_warpwire-button
 */

/**
 * Atto text editor warpwire plugin.
 *
 * @namespace M.atto_warpwire
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_warpwire').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * Add event listeners.
     *
     * @method initializer
     */
    initializer: function() {
        this.addButton({
            icon: 'e/insert_edit_warpwire',
            iconComponent: 'atto_warpwire',
            callback: this._handleWarpwire
        });
    },

    // fn arg can be an object or a function, thanks to handleEvent
    // read more about the explanation at: http://www.thecssninja.com/javascript/handleevent
    addEvt: function(el, evt, fn, bubble) {
        if ('addEventListener' in el) {
            // BBOS6 doesn't support handleEvent, catch and polyfill
            try {
                el.addEventListener(evt, fn, bubble);
            } catch (e) {
                if (typeof fn == 'object' && fn.handleEvent) {
                    el.addEventListener(evt, function(e) {
                        // Bind fn as this and set first arg as event object
                        fn.handleEvent.call(fn, e);
                    }, bubble);
                } else {
                    throw e;
                }
            }
        } else if ('attachEvent' in el) {
            // check if the callback is an object and contains handleEvent
            if (typeof fn == 'object' && fn.handleEvent) {
                el.attachEvent('on' + evt, function() {
                    // Bind fn as this
                    fn.handleEvent.call(fn);
                });
            } else {
                el.attachEvent('on' + evt, fn);
            }
        }
    },

    GetURLParameter: function(sParam, sPageURL) {
        if (typeof sPageURL == 'undefined')
            return (null);

        sPageURL = sPageURL.substring(sPageURL.indexOf("?") + 1);

        var sURLVariables = sPageURL.split('&');
        for (var i = 0; i < sURLVariables.length; i++) {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == sParam) {
                return sParameterName[1];
            }
        }

        return (null);
    },

    _handleWarpwire: function() {
        var self = this;

        self.editor = null;

        var pathArray = location.href.split('/');
        var protocol = pathArray[0];
        var host = pathArray[2];
        var url = protocol + '//' + host;

        self.addEvt(window, "message", function(ev) {
            if (ev.data.message === "deliverResult") {
                var frames = JSON.parse(ev.data.result);
                for (var i = 0; i < frames.length; i++) {
                    var imgNode = document.createElement('img');
                    imgNode.setAttribute('class', "_ww_img");

                    var img_src = frames[i]._ww_img.replace("http://", "https://");
                    var source = frames[i]._ww_src.replace("http://", "https://");

                    var sourceUrl = decodeURIComponent(source);
                    sourceUrl = sourceUrl.replace(/^\[warpwire:/, '');
                    sourceUrl = sourceUrl.replace(/]$/, '');

                    var img_width = self.GetURLParameter('width', sourceUrl);
                    if (img_width == null)
                        img_width = 400;
                    imgNode.setAttribute('width', img_width);
                    var img_height = self.GetURLParameter('height', sourceUrl);
                    if (img_height == null)
                        img_height = 400;
                    imgNode.setAttribute('height', img_height);

                    var sep = img_src.indexOf('?') == -1 ? '?' : '&';
                    img_src = img_src + sep + 'ww_code=' + source;

                    imgNode.setAttribute('src', img_src);

                    if (frames[i]) {
                        try {
                            self.get('host').insertContentAtFocusPoint(imgNode.outerHTML);
                        } catch (e) { }
                    }
                }
                ev.data.message = '';
            }
        });

        var warpwireUrl = self.get('warpwire_url');
        if (warpwireUrl == '') {
            alert('Warpwire has not been configured. Please contact your administrator.');
            return;
        }

        var child = window.open(warpwireUrl, '_wwPlugin', 'width=400, height=500');

        var leftDomain = false;
        var interval = setInterval(function() {
            try {
                if (child.document.domain === document.domain) {
                    if (leftDomain && child.document.readyState === "complete") {
                        // we're here when the child window returned to our domain
                        clearInterval(interval);
                        child.postMessage({ message: "requestResult" }, "*");
                    }
                } else {
                    // this code should never be reached,
                    // as the x-site security check throws
                    // but just in case
                    leftDomain = true;
                }
            } catch (e) {
                // we're here when the child window has been navigated away or closed
                if (child.closed) {
                    clearInterval(interval);
                    return;
                }
                // navigated to another domain
                leftDomain = true;
            }

        }, 500);

        return (true);
    }
}, {
    ATTRS: {
        warpwire_url: {
            value: '<defaultvalue>'
        }
    }
});
