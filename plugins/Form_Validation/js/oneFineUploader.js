/*
 * requires: bootbox.js and fineuploader.js
 */

(function($) {
  $.fn.oneFineUploader = function(options) {
    var action = $(this).closest("form").attr("action");
    var field = $(this).attr("id");
    var settings = $.extend({
      "limit": 0,
      "allowedExtensions": "jpg,jpeg,gif,png",
      "sizeLimit": 0
    }, options);
    var loading = "data:gif;base64,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";
    $(this).fineUploader({
      multiple: true,
      uploaderType: "basic",
      button: $(this),
      request: { endpoint:action },
      validation: { allowedExtensions:settings.allowedExtensions.split(","), sizeLimit:settings.sizeLimit }
    }).on("submit", function(event, id, filename) {
		if (!checkUploadLimit()) return false;
		$("#" + field + "Messages").prepend('<div id="' + field + id + '" class="alert alert-warning ' + field + 'Upload" style="margin:10px 0 0; padding:8px;"></div>');
		checkUploadLimit();
    }).on("upload", function(event, id, filename) {
		filename = '<span style="margin:0 15px;">&ldquo;' + filename + "&rdquo;</span>";
		$("#" + field + id).html('<img src="' + loading + '"> ' + "<strong>Initializing</strong>" + filename);
    }).on("progress", function(event, id, filename, loaded, total) {
		filename = '<span style="margin:0 15px;">&ldquo;' + filename + "&rdquo;</span>";
		if (loaded < total) {
			percent = Math.round(loaded / total * 100) + "%";
			progress = percent + " of " + Math.round(total / 1024) + " kB";
			$("#" + field + id).removeClass("alert-warning").addClass("alert-info")
			                   .html('<img src="' + loading + '"> ' + "<strong>Uploading</strong>" + filename + progress);
		} else {
			$("#" + field + id).removeClass("alert-warning").addClass("alert-info")
			                   .html('<img src="' + loading + '"> ' + "<strong>Saving</strong>" + filename);
		}
    }).on("error", function(event, id, filename, reason) {
		alert(reason);
		$("#" + field + id).remove();
		checkUploadLimit();
    }).on("complete", function(event, id, filename, responseJSON){
		if (responseJSON.success) {
			$("#" + field + id).removeClass("alert-warning alert-info").addClass("alert-success")
			                   .html("").html($("<div/>").html(responseJSON.html).text())
			                   .append($('<input type="hidden"/>').attr("name", field+"[]").val(responseJSON.file));
		} else {
			$("#" + field + id).removeClass(field + "Upload alert-warning alert-info").addClass("alert-error")
			                   .html("").html($("<div/>").html(responseJSON.html).text());
		}
		checkUploadLimit();
    });
    
    checkUploadLimit();
    
    function checkUploadLimit () {
      var display = (settings.limit && $("div." + field + "Upload").length >= settings.limit) ? "none" : "block";
      $("#" + field + "Upload").css("display", display);
      return (display == "block") ? true : false;
    }
    
  };
})(jQuery);