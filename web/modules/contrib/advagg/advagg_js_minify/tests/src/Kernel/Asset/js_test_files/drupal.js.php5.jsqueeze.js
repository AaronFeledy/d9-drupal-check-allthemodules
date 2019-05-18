;window.Drupal={behaviors:{},locale:{}};(function(t,e,r){'use strict';t.throwError=function(t){setTimeout(function(){throw t},0)};t.attachBehaviors=function(r,n){r=r||document;n=n||e;var a=t.behaviors;for(var i in a){if(a.hasOwnProperty(i)&&typeof a[i].attach==='function'){try{a[i].attach(r,n)}catch(o){t.throwError(o)}}}};t.detachBehaviors=function(r,n,o){r=r||document;n=n||e;o=o||'unload';var i=t.behaviors;for(var c in i){if(i.hasOwnProperty(c)&&typeof i[c].detach==='function'){try{i[c].detach(r,n,o)}catch(a){t.throwError(a)}}}};t.checkPlain=function(t){t=t.toString().replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');return t};t.formatString=function(r,e){var o={};for(var n in e){if(e.hasOwnProperty(n)){switch(n.charAt(0)){case'@':o[n]=t.checkPlain(e[n]);break;case'!':o[n]=e[n];break;default:o[n]=t.theme('placeholder',e[n]);break}}};return t.stringReplace(r,o,null)};t.stringReplace=function(r,n,e){if(r.length===0){return r};if(!Array.isArray(e)){e=[];for(var i in n){if(n.hasOwnProperty(i)){e.push(i)}};e.sort(function(t,r){return t.length-r.length})};if(e.length===0){return r};var c=e.pop(),o=r.split(c);if(e.length){for(var a=0;a<o.length;a++){o[a]=t.stringReplace(o[a],n,e.slice(0))}};return o.join(n[c])};t.t=function(e,o,n){n=n||{};n.context=n.context||'';if(typeof r!=='undefined'&&r.strings&&r.strings[n.context]&&r.strings[n.context][e]){e=r.strings[n.context][e]};if(o){e=t.formatString(e,o)};return e};t.url=function(t){return e.path.baseUrl+e.path.pathPrefix+t};t.url.toAbsolute=function(t){var e=document.createElement('a');try{t=decodeURIComponent(t)}catch(r){};e.setAttribute('href',t);return e.cloneNode(!1).href};t.url.isLocal=function(r){var n=t.url.toAbsolute(r),i=location.protocol;if(i==='http:'&&n.indexOf('https:')===0){i='https:'};var o=i+'//'+location.host+e.path.baseUrl.slice(0,-1);try{n=decodeURIComponent(n)}catch(a){};try{o=decodeURIComponent(o)}catch(a){};return n===o||n.indexOf(o+'/')===0};t.formatPlural=function(n,o,i,a,u){a=a||{};a['@count']=n;var l=e.pluralDelimiter,h=t.t(o+l+i,a,u).split(l),c=0;if(typeof r!=='undefined'&&r.pluralFormula){c=n in r.pluralFormula?r.pluralFormula[n]:r.pluralFormula['default']}
else if(a['@count']!==1){c=1};return h[c]};t.encodePath=function(t){return window.encodeURIComponent(t).replace(/%2F/g,'/')};t.theme=function(r){var e=Array.prototype.slice.apply(arguments,[1]);if(r in t.theme){return t.theme[r].apply(this,e)}};t.theme.placeholder=function(r){return'<em class="placeholder">'+t.checkPlain(r)+'</em>'}})(Drupal,window.drupalSettings,window.drupalTranslations);