(function($) {
  $.fn.blueimpFileUploader = function(options) {
    var action = $(this).closest('form').attr('action');
    var field = $(this).attr('id');
    var settings = $.extend({
      'accept': '',
      'size': 0,
      'limit': 0
    }, options);
    var loading = 'data:image/gif;base64,R0lGODlhEAALAPQAAP///wAAANra2tDQ0Orq6gYGBgAAAC4uLoKCgmBgYLq6uiIiIkpKSoqKimRkZL6+viYmJgQEBE5OTubm5tjY2PT09Dg4ONzc3PLy8ra2tqCgoMrKyu7u7gAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCwAAACwAAAAAEAALAAAFLSAgjmRpnqSgCuLKAq5AEIM4zDVw03ve27ifDgfkEYe04kDIDC5zrtYKRa2WQgAh+QQJCwAAACwAAAAAEAALAAAFJGBhGAVgnqhpHIeRvsDawqns0qeN5+y967tYLyicBYE7EYkYAgAh+QQJCwAAACwAAAAAEAALAAAFNiAgjothLOOIJAkiGgxjpGKiKMkbz7SN6zIawJcDwIK9W/HISxGBzdHTuBNOmcJVCyoUlk7CEAAh+QQJCwAAACwAAAAAEAALAAAFNSAgjqQIRRFUAo3jNGIkSdHqPI8Tz3V55zuaDacDyIQ+YrBH+hWPzJFzOQQaeavWi7oqnVIhACH5BAkLAAAALAAAAAAQAAsAAAUyICCOZGme1rJY5kRRk7hI0mJSVUXJtF3iOl7tltsBZsNfUegjAY3I5sgFY55KqdX1GgIAIfkECQsAAAAsAAAAABAACwAABTcgII5kaZ4kcV2EqLJipmnZhWGXaOOitm2aXQ4g7P2Ct2ER4AMul00kj5g0Al8tADY2y6C+4FIIACH5BAkLAAAALAAAAAAQAAsAAAUvICCOZGme5ERRk6iy7qpyHCVStA3gNa/7txxwlwv2isSacYUc+l4tADQGQ1mvpBAAIfkECQsAAAAsAAAAABAACwAABS8gII5kaZ7kRFGTqLLuqnIcJVK0DeA1r/u3HHCXC/aKxJpxhRz6Xi0ANAZDWa+kEAA7AAAAAAAAAAAA';
    $(this).fileupload({
      url: action,
      dataType: 'json',
      paramName: 'blueimp'
    }).on('fileuploadsubmit', function(e,data){
      if (data.files.length != 1) {
        alert('Only 1 file may be submitted at a time');
      } else if (settings.accept != '' && !new RegExp('\.(' + settings.accept + ')$', 'i').test(data.files[0].name)) {
        alert(data.files[0].name + ' does not have a file extension of the type: ' + settings.accept.split('|').join(', '));
      } else if (settings.size > 0 && data.files[0].size > settings.size) {
        alert(data.files[0].name + ' is bigger than ' + (settings.size / 1048576) + ' MB');
      } else if (settings.limit > 0 && $('div.'+field+'Upload').length >= settings.limit) {
        alert('You may only upload ' + settings.limit + ' files');
      } else if ($('#'+id(data)).length > 0) {
        alert(data.files[0].name + ' has already been submitted');
      } else {
        var status = $('<div/>').prop('id', id(data)).addClass('alert alert-warning '+field+'Upload').css({margin:'10px 0 0', padding:'8px'});
        $('#'+field+'Messages').append(status);
        return true;
      }
      return false;
    }).on('fileuploadsend', function(e,data){
      $('#'+id(data)).html(status('Initializing', data));
    }).on('fileuploadprogress', function(e,data){
      if (data.loaded < data.total) {
        var progress = Math.round(data.loaded / data.total * 100) + '% of ' + Math.round(data.total / 1024) + ' kB';
        $('#'+id(data)).removeClass('alert-warning').addClass('alert-info').html(status('Uploading', data) + progress);
      } else {
        $('#'+id(data)).removeClass('alert-warning').addClass('alert-info').html(status('Saving', data));
      }
    }).on('fileuploadfail', function(e,data){
      alert('Error: ' + data.errorThrown + "\n\n" + data.textStatus);
      $('#'+id(data)).remove();
    }).on('fileuploaddone', function(e,data){
      if (data.result.success) {
        $('#'+id(data)).removeClass('alert-warning alert-info').addClass('alert-success').html(data.result.success);
      } else {
        $('#'+id(data)).removeClass(field+'Upload alert-warning alert-info').addClass('alert-danger').html(data.result.error);
      }
    });
    
    function id (data) {
      return field + data.files[0].name.replace(/[^a-z0-9]/gi, '');
    }
    
    function status (action, data) {
      return '<img src="'+loading+'" width="16" height="16" style="margin-right:15px;"> <strong>'+action+'</strong> <span style="margin:0 15px;">&ldquo;' + data.files[0].name.replace(/[^a-z0-9\.\-_]/gi, '') + '&rdquo;</span>';
    }
    
  };
})(jQuery);