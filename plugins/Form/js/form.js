jQuery.validator.addMethod("numeric", function(value, element) {
	return this.optional(element) || /^-?[0-9]*\.?[0-9]+$/.test(value);
}, "Please enter a valid number with no commas.");  

jQuery.validator.addMethod("integer", function(value, element) {
	return this.optional(element) || /^-?[0-9]+$/.test(value);
}, "Please enter an integer without any commas.");  

jQuery.validator.addMethod("decimal", function(value, element) {
	return this.optional(element) || /^-?[0-9]+\.[0-9]+$/.test(value);
}, "Please enter a decimal number with no commas.");  

jQuery.validator.addMethod("alpha", function(value, element) {
	return this.optional(element) || /^[a-z\s]+$/i.test(value);
}, "Letters only please."); 

jQuery.validator.addMethod("alphanumeric", function(value, element) {
	return this.optional(element) || /^[a-z0-9\s]+$/i.test(value);
}, "Letters and numbers only please.");  

jQuery.validator.addMethod("base64", function(value, element) {
	return this.optional(element) || /^[a-z0-9\/\+=]+$/i.test(value);
}, "Please enter a valid base64 string.");

jQuery.validator.addMethod("ip", function(value, element, param) {
	if (param == "v4") {
		return this.optional(element) || /^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/i.test(value);
	} else if (param == "v6") {
		return this.optional(element) || /^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i.test(value);
	} else {
		return this.optional(element) || ($.validator.methods["ip"].call(this, value, element, "v4")) || ($.validator.methods["ip"].call(this, value, element, "v6"));
	}
}, jQuery.validator.format("Please enter a valid IP{0} address."));

jQuery.validator.addMethod("regex", function(value, element, regexp) {
	return this.optional(element) || regexp.test(value);
}, jQuery.validator.format("Enter the correct format please."));

jQuery.validator.addMethod("nowhitespace", function(value, element) {
	return this.optional(element) || /^\S+$/i.test(value);
}, "No white space please.");  

jQuery.validator.addMethod("inarray", function(value, element, array) {
        var inarray = true;
	var haystack = "," + array + ",";
	if (typeof value === "string") value = value.split(",");
        for (var key in value) if (haystack.indexOf("," + value[key] + ",") < 0) inarray = false;
	return this.optional(element) || inarray;
}, "Please make a valid selection.");  

function highlight (element, errorClass, validClass) {
  $(element).closest("div.form-group").addClass(errorClass).removeClass(validClass).find("p.validation").show();
}

function unhighlight (element, errorClass, validClass) {
  $(element).closest("div.form-group").removeClass(errorClass).addClass(validClass).find("p.validation").text("").hide();
}

function errorPlacement (error, element) {
  console.log(error);
  $(element).closest("div.form-group").find("p.validation").html(error);
}

function submitHandler (form, event) {
  event.preventDefault();
  $(form).find("button[type=submit]").button("loading");
  form.submit();
}