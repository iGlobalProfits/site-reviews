var glsr_render_recaptcha=function(){var r,e;[].forEach.call(document.querySelectorAll(".glsr-submit-review-form"),function(c){e=c.querySelector(".glsr-recaptcha-holder"),e&&(e.innerHTML="",r=grecaptcha.render(e,{callback:function(r){GLSR.submitForm(r)},"expired-callback":function(){grecaptcha.reset(r)}},!0),e.setAttribute("data-id",r))})};