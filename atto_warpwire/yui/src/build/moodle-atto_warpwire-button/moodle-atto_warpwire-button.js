YUI.add('moodle-atto_warpwire-button', function (Y, NAME) {

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
 * @package    atto_orderedlist
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_orderedlist-button
 */

/**
 * Atto text editor orderedlist plugin.
 *
 * @namespace M.atto_orderedlist
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
			icon: 'e/insert_edit_video',
			callback: this._handleWarpwire
		});
    },

	isIE: function() {
		var ua = window.navigator.userAgent;
		var msie = ua.indexOf('MSIE ');
		var trident = ua.indexOf('Trident/');

		// is internet explorer
		if (msie > 0 || trident > 0) {
			return true;
		}

		// other browser
		return false;
	},

	createCORSRequest: function(method, url){
		var xhr = new XMLHttpRequest();
		if ("withCredentials" in xhr){
			xhr.open(method, url, true);
		} else if (typeof XDomainRequest != "undefined"){
			xhr = new XDomainRequest();
			xhr.open(method, url);
		} else {
			xhr = null;
		}
		return xhr;
	},

	// fn arg can be an object or a function, thanks to handleEvent
	// read more about the explanation at: http://www.thecssninja.com/javascript/handleevent
	addEvt: function(el, evt, fn, bubble) {
		if ('addEventListener' in el) {
			// BBOS6 doesn't support handleEvent, catch and polyfill
			try {
				el.addEventListener(evt, fn, bubble);
			} catch(e) {
				if (typeof fn == 'object' && fn.handleEvent) {
					el.addEventListener(evt, function(e){
						// Bind fn as this and set first arg as event object
						fn.handleEvent.call(fn,e);
					}, bubble);
				} else {
					throw e;
				}
			}
		} else if ('attachEvent' in el) {
			// check if the callback is an object and contains handleEvent
			if (typeof fn == 'object' && fn.handleEvent) {
				el.attachEvent('on' + evt, function(){
					// Bind fn as this
					fn.handleEvent.call(fn);
				});
			} else {
				el.attachEvent('on' + evt, fn);
			}
		}
	},

	checkIEGet: function(ed, pluginId, checkGetCounter) {
		var self = this;
		self.editor = null;

		if(checkGetCounter >= 10) {
			return(false);
		}

		var xmlhttp = self.createCORSRequest("GET", ed.getParam('warpwire_url').replace(/(\/)+$/g,'')+'/api/staging/c/'+pluginId+'/o/'+pluginId);

		if (xmlhttp){
			xmlhttp.onload = function(){
				var frames = JSON.parse(xmlhttp.responseText);
				for(var i=0; i < frames.length; i++) {
					var imgNode  = new ed.dom.element('img');
					imgNode.setAttribute('class', "_ww_img");
					imgNode.setAttribute('longdesc', frames[i]._ww_src.replace("http://","https://"));
					imgNode.setAttribute('src', frames[i]._ww_img.replace("http://","https://"));

					if (frames[i]) {
						ed.execCommand('mceInsertContent', false, imgNode.$.outerHTML);
					}
				}

				return(true);
			};

			xmlhttp.onerror = function(){
				checkGetCounter = checkGetCounter + 1;
				setTimeout(self.checkIEGet(self.editor, pluginId, checkGetCounter),1000);
			};

			xmlhttp.send();
		}
	},

	_handleWarpwire : function() {
		console.log(this.addEvt);
		var self = this;
		self.editor = null;

		var	pathArray = location.href.split( '/' );
		var protocol = pathArray[0];
		var host = pathArray[2];
		var url = protocol + '//' + host;

		self.addEvt(window, "message", function(ev) {
			alert('hi');
			if (ev.data.message === "deliverResult") {
				var frames = JSON.parse(ev.data.result);
				for(var i=0; i < frames.length; i++) {
					var imgNode = document.createElement('img');
					imgNode.setAttribute('class', "_ww_img");
					imgNode.setAttribute('longdesc', frames[i]._ww_src.replace("http://","https://"));
					imgNode.setAttribute('src', frames[i]._ww_img.replace("http://","https://"));

					if (frames[i]) {
						try {
							self.editor.execCommand('mceInsertContent', false, imgNode.outerHTML);
						} catch(e) { }
					}
				}
				ev.data.message = '';
			}

			var pluginId = "";
			if(self.isIE()) {
				for (var j = 0; j < 32; j++) {
					pluginId += Math.floor(Math.random() * 16).toString(16);
				}
			} else {
				pluginId = "0";
			}

			var child = window.open(self.editor.getParam('warpwire_url').replace(/(\/)+$/g,'')+"/w/all?pl=1&showSelector=1&externalContext=drupal&pluginLaunchReturnUrl="+encodeURIComponent(self.editor.getParam('warpwire_redirct_page'))+"&pluginId="+pluginId,'_wwPlugin','width=400, height=500');
			console.log(child);

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
				} catch(e) {
					// we're here when the child window has been navigated away or closed
					if (child.closed) {
						clearInterval(interval);
						// if IE, we are using a GET method rather than a window listener
						if(self.isIE()) {
							self.checkIEGet(self.editor, pluginId, 0);
						}
						return; 
					}
					// navigated to another domain  
					leftDomain = true;
				}

			}, 500);

			return(true);
		});
	}
});

}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
