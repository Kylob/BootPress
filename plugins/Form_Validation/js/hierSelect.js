(function($) {
  $.fn.hierSelect = function(select, options) {
    $(this).change(function() {
      var id = $(this).val();
      var hier = $("select[name='" + select + "']");
      var preselect = hier.val();
      hier.each(function(){
        if (id != "") {
          hier.children().remove();
          $.each(options[id], function(key,value){
            if (typeof value === "object") {
              var optgroup = $("<optgroup />", {label:key});
              $.each(value, function(key,value){
                var option = $("<option />").val(key).html(value);
                if (preselect == key) option.attr("selected", "selected");
                optgroup.append(option);
              });
              hier.append(optgroup);
            } else {
              var option = $("<option />").val(key).html(value);
              if (preselect == key) option.attr("selected", "selected");
              hier.append(option);
            }
          });
        } // end if id
      }); // end each hier
    }); // end this change
  };
})(jQuery);
