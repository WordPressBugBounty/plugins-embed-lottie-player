(()=>{"use strict";window.copyBPlAdminShortcode=e=>{var o=document.querySelector("#bPlAdminShortcode-"+e+" input"),t=document.querySelector("#bPlAdminShortcode-"+e+" .tooltip");o.select(),o.setSelectionRange(0,30),document.execCommand("copy"),t.innerHTML=wp.i18n.__("Copied Successfully!","lottie-player"),setTimeout((()=>{t.innerHTML=wp.i18n.__("Copy To Clipboard","lottie-player")}),1500)}})();