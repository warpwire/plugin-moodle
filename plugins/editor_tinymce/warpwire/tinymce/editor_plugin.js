(function() {

  // fn arg can be an object or a function, thanks to handleEvent
  // read more about the explanation at: http://www.thecssninja.com/javascript/handleevent
  function addEvt(el, evt, fn, bubble) {
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
  }

  function GetURLParameter(sParam, sPageURL) {
    if(typeof sPageURL == 'undefined')
      return(null);

    sPageURL = sPageURL.substring(sPageURL.indexOf("?")+1);

    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
      var sParameterName = sURLVariables[i].split('=');
      if (sParameterName[0] == sParam) {
        return sParameterName[1];
      }
    }

    return(null);
  }

  tinymce.create('tinymce.plugins.warpwire', {
    init : function(editor) {
      var self = this;
      self.editor = editor;

      pathArray = location.href.split( '/' );
      protocol = pathArray[0];
      host = pathArray[2];
      url = protocol + '//' + host;

      self.editor.baseUrl = url;

      // Add Button
      self.editor.addButton( 'warpwire', {
        title: 'Warpwire',
        cmd: 'warpwire',
        image: self.editor.getParam('warpwire_img')
      });

      editor.addCommand('warpwire', function() {

        addEvt(window, "message", function(ev) {
            if (ev.data.message === "deliverResult") {
              var frames = JSON.parse(ev.data.result);
              for(var i=0; i < frames.length; i++) {
                var imgNode = document.createElement('img');

                imgNode.setAttribute('class', "_ww_img");

                var img_src = frames[i]._ww_img.replace("http://","https://");
                var source = frames[i]._ww_src.replace("http://","https://");

                var sourceUrl = decodeURIComponent(source);
                sourceUrl = sourceUrl.replace(/^\[warpwire:/,'');
                sourceUrl = sourceUrl.replace(/]$/,'');

                var img_width = GetURLParameter('width', sourceUrl);
                if(img_width == null)
                  img_width = 400;
                imgNode.setAttribute('width', img_width);
                var img_height = GetURLParameter('height', sourceUrl);
                if(img_height == null)
                  img_height = 400;         
                imgNode.setAttribute('height', img_height);

                var sep = img_src.indexOf('?') == -1 ? '?' : '&';
                img_src = img_src + sep + 'ww_code=' + source;

                imgNode.setAttribute('src', img_src);
                
                if (frames[i]) {
                  try {
                    self.editor.execCommand('mceInsertContent', false, imgNode.outerHTML);
                  } catch(e) { }
                }
              }
              ev.data.message = '';
            }
        });

        // open the warpwire plugin
        var child = window.open(self.editor.getParam('warpwire_url'),'_wwPlugin','width=400, height=500');
        // set focus to the opened window
        child.focus();

        var leftDomain = false;
        var interval = setInterval(function() {
            try {
                if (child.document.domain === document.domain)
                {
                    if (leftDomain && child.document.readyState === "complete")
                    {
                        // we're here when the child window returned to our domain
                        clearInterval(interval);
                        child.postMessage({ message: "requestResult" }, "*");
                    }
                }
                else {
                    // this code should never be reached, 
                    // as the x-site security check throws
                    // but just in case
                    leftDomain = true;
                }
            }
            catch(e) {
                // we're here when the child window has been navigated away or closed
                if (child.closed) {
                    clearInterval(interval);
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

  tinymce.PluginManager.add('warpwire', tinymce.plugins.warpwire);
})();
