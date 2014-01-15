/*
 * requires: bootbox.js and imgAreaSelect.js and imagesLoaded.js
 */

(function($) {
  $.fn.modalCropImage = function(url, post, options) {
    var image = $(this).is("img") ? $(this) : false;
    var settings = $.extend({
      "x1": 0,
      "y1": 0,
      "x2": 0,
      "y2": 0,
      "width": 0,
      "height": 0,
      "minWidth": "300",
      "aspectRatio": "1:1"
    }, options);
    if (settings.width == 0 || settings.height == 0) return;
    var trueWidth = settings.width;
    var trueHeight = settings.height;
    var winWidth = $(window).width() - 10;
    var winHeight = $(window).height() - 150;
    var imgWidth = (trueWidth > winWidth) ? winWidth : trueWidth;
    var imgHeight = imgWidth * (trueHeight / trueWidth);
    if (imgHeight > winHeight) {
      imgHeight = winHeight;
      imgWidth = imgHeight * (trueWidth / trueHeight);
    }
    var crop = $("<img/>", {"id":"cropImage", "src":url}).css({
      "width": imgWidth,
      "height": imgHeight,
      "display": "block",
      "margin": "0 auto"
    }).imagesLoaded({
      done: function($image){
        crop.imgAreaSelect({
          zIndex: 10000,
          aspectRatio: settings.aspectRatio,
          handles: "corners",
          imageWidth: trueWidth,
          imageHeight: trueHeight,
          minWidth: settings.minWidth,
          onSelectEnd: function(img, selection){ $.extend(settings, selection); }
        });
        bootbox.dialog(crop, {"Crop Image": function(){
          if ((settings.x2 - settings.x1) >= (settings.minWidth - 10)) {
            $.post(post, settings, function(data){
              if (image !== false) image.attr("src", data);
            });
          }
          crop.imgAreaSelect({remove:true});
        }}).css({
          "max-height":$(window).height()-50, "overflow":"auto", "top":"50%",
          "margin-top":function(){return -($(this).height() / 2);}
        });
      }
    });
  };
})(jQuery);