(function($) {
  $.fn.addTags = function(options) {
    options = $.extend({
      "tags": "",
      "limit": 0
    }, options);
    
    var add = options.tags.split(",");
    var field = $(this).attr("id");
    var tagged = $("#" + field + "Tagged");
    var required = ($(this).rules().required === true) ? true : false;
    if (required) $(this).rules("remove", "required");
    $(this).attr("id", "add" + field).attr("name", "add" + field);
    var tags = $("#" + "add" + field);
    if (required) tags.rules("add", "required");
    tags.keypress(function(e){
      if (e.which == 13) {
        e.preventDefault();
        $("#" + field + "Tag").focus();
        return false;
      }
    }).focusout(function(){
      if ($(this).val() == "") return;
      addTag($(this).val(), true);
      $(this).val("");
    });
    
    tagged.sortable({items:"span.label"});
    for (i=0; i<add.length; i++) addTag(add[i]);
    $("#" + field + "Tag").click(function(e){e.preventDefault();});
    
    function addTag (name, refocus) {
      name = $.trim(name).replace(/[^a-z0-9.\-\s]/gi, "");
      refocus = (typeof refocus !== "undefined") ? refocus : false;
      var formGroup = tags.closest("div.form-group");
      var count = 0;
      $("input:hidden[name^=" + field + "]").each(function(){
        count++;
        if ($(this).val().toLowerCase() == name.toLowerCase()) {
          name = "";
        }
      });
      if (name != "") {
        if (required) {
          tags.rules("remove", "required");
          formGroup.removeClass("has-error").find("p.validation").hide();
        }
        if (options.limit == 0 || count < options.limit) {
          var input = $('<input type="hidden"/>').attr("name", field + "[]").val(name);
          var remove = $("<span />").html("&times;").css({"cursor":"pointer","margin-left":"5px"}).attr("title", "Remove").click(function(){
            $(this).parent("span").remove();
            tags.closest("div.input-group").show();
            addTag("", true);
          });
          var tag = $("<span/>").addClass("label label-success").html(name).append(remove, input);
          tagged.show().append(tag);
          count += 1;
        }
      }
      if (count == 0) tagged.hide();
      if (options.limit && count > 0 && count <= options.limit) {
        formGroup.find("p.validation").html(count + " of " + options.limit).show();
        if (count == options.limit) tags.closest("div.input-group").hide();
      } else {
        formGroup.find("p.validation").hide();
      }
      if (refocus === true) setTimeout(function(){tags.focus();}, 1);
      if (required && count == 0) tags.rules("add", "required");
    }
    
    return this;
    
  };
})(jQuery);