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