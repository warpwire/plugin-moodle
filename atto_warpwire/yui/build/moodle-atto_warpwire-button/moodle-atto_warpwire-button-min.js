YUI.add("moodle-atto_warpwire-button",function(e,t){e.namespace("M.atto_warpwire").Button=e.Base.create("button",e.M.editor_atto.EditorPlugin,[],{initializer:function(){this.addButton({icon:"e/insert_edit_warpwire",iconComponent:"atto_warpwire",callback:this._handleWarpwire})},addEvt:function(e,t,n,r){if("addEventListener"in e)try{e.addEventListener(t,n,r)}catch(i){if(typeof n!="object"||!n.handleEvent)throw i;e.addEventListener(t,function(e){n.handleEvent.call(n,e)},r)}else"attachEvent"in e&&(typeof n=="object"&&n.handleEvent?e.attachEvent("on"+t,function(){n.handleEvent.call(n)}):e.attachEvent("on"+t,n))},GetURLParameter:function(e,t){if(typeof t=="undefined")return null;t=t.substring(t.indexOf("?")+1);var n=t.split("&");for(var r=0;r<n.length;r++){var i=n[r].split("=");if(i[0]==e)return i[1]}return null},_handleWarpwire:function(){var e=this;e.editor=null;var t=location.href.split("/"),n=t[0],r=t[2],i=n+"//"+r;e.addEvt(window,"message",function(t){if(t.data.message==="deliverResult"){var n=JSON.parse(t.data.result);for(var r=0;r<n.length;r++){var i=document.createElement("img");i.setAttribute("class","_ww_img");var s=n[r]._ww_img.replace("http://","https://"),o=n[r]._ww_src.replace("http://","https://"),u=decodeURIComponent(o);u=u.replace(/^\[warpwire:/,""),u=u.replace(/]$/,"");var a=e.GetURLParameter("width",u);a==null&&(a=400),i.setAttribute("width",a);var f=e.GetURLParameter("height",u);f==null&&(f=400),i.setAttribute("height",f);var l=s.indexOf("?")==-1?"?":"&";s=s+l+"ww_code="+o,i.setAttribute("src",s);if(n[r])try{e.get("host").insertContentAtFocusPoint(i.outerHTML)}catch(c){}}t.data.message=""}});var s=window.open(e.get("warpwire_url"),"_wwPlugin","width=400, height=500"),o=!1,u=setInterval(function(){try{s.document.domain===document.domain?o&&s.document.readyState==="complete"&&(clearInterval(u),s.postMessage({message:"requestResult"},"*")):o=!0}catch(e){if(s.closed){clearInterval(u);return}o=!0}},500);return!0}},{ATTRS:{warpwire_url:{value:"<defaultvalue>"}}})},"@VERSION@",{requires:["moodle-editor_atto-plugin"]});
