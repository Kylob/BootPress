/*!
 * jQuery Validation Plugin 1.11.0pre
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-validation/
 * http://docs.jquery.com/Plugins/Validation
 * https://github.com/jzaefferer/jquery-validation
 *
 * Copyright (c) 2006 - 2011 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

(function() {

	function stripHtml(value) {
		// remove html tags and space chars
		return value.replace(/<.[^<>]*?>/g, ' ').replace(/&nbsp;|&#160;/gi, ' ')
		// remove punctuation
		.replace(/[.(),;:!?%#$'"_+=\/\-]*/g,'');
	}
	jQuery.validator.addMethod("maxWords", function(value, element, params) {
		return this.optional(element) || stripHtml(value).match(/\b\w+\b/g).length <= params;
	}, jQuery.validator.format("Please enter {0} words or less."));

	jQuery.validator.addMethod("minWords", function(value, element, params) {
		return this.optional(element) || stripHtml(value).match(/\b\w+\b/g).length >= params;
	}, jQuery.validator.format("Please enter at least {0} words."));

	jQuery.validator.addMethod("rangeWords", function(value, element, params) {
		var valueStripped = stripHtml(value);
		var regex = /\b\w+\b/g;
		return this.optional(element) || valueStripped.match(regex).length >= params[0] && valueStripped.match(regex).length <= params[1];
	}, jQuery.validator.format("Please enter between {0} and {1} words."));

}());

/* Added Custom Methods: */

jQuery.validator.addMethod("alpha", function(value, element) {
	return this.optional(element) || /^[a-z\s]+$/i.test(value);
}, "Letters only please"); 

jQuery.validator.addMethod("alphanumeric", function(value, element) {
	return this.optional(element) || /^[a-z0-9\s]+$/i.test(value);
}, "Letters and numbers only please");  

jQuery.validator.addMethod("alphapunctuation", function(value, element) {
	return this.optional(element) || /^[a-z.,?!:;\-_()\[\]'\"/\s]+$/i.test(value);
}, "Letters and punctuation only please");

jQuery.validator.addMethod("alphanumericpunctuation", function(value, element) {
	return this.optional(element) || /^[a-z0-9.,?!:;\-_()\[\]'\"/\s]+$/i.test(value);
}, "Letters, numbers and punctuation only please");

jQuery.validator.addMethod("password", function(value, element) {
	return this.optional(element) || /^[\S]{6,}$/.test(value);
}, "At least 6 characters with no spaces please");

jQuery.validator.addMethod("regex", function(value, element, regexp) {
	return this.optional(element) || regexp.test(value);
});

jQuery.validator.addMethod("nomatch", function(value, element, regexp) {
	return this.optional(element) || !regexp.test(value);
});

jQuery.validator.addMethod("recaptcha", function(value, element) {
	$.ajax({type: "post",
		url: location.href,
		data: {
			recaptcha_challenge_field: function(){ return $("input#recaptcha_challenge_field").val(); },
			recaptcha_response_field: function(){ return $("input#recaptcha_response_field").val(); },
			ajax: "request"
		},
		async: false,
		dataType: "json",
		success: function(msg) {
			if (msg === true) {
				$("input#recaptcha_response_field").rules("remove");
				$("span#recaptcha_error_field").remove();
				$("div#recaptcha_widget").css("display", "none");
				unhighlight(element, this.errorClass, this.validClass);
				return true;
			} else {
				Recaptcha.reload();
				return false;
			}
		}
	});
}, "The reCAPTCHA entered was incorrect. Please try again.");

/* Added Custom Functions: */

function highlight (element, errorClass, validClass) {
  $(element).closest("div.form-group").removeClass(validClass).addClass(errorClass).find("p.validation").show();
}

function unhighlight (element, errorClass, validClass) {
  $(element).closest("div.form-group").removeClass(errorClass).addClass(validClass).find("p.validation").text("").hide();
}

function errorPlacement (error, element) {
  $(element).closest("div.form-group").find("p.validation").html(error);
}

function submitHandler (form) {
  $(form).find("button[type=submit]").button("loading");
  form.submit();
}

/* Threw away the rest: */
