(function($) {
  $.fn.blueimpFileUploader = function(options) {
    var action = $(this).closest('form').attr('action');
    var field = $(this).attr('id');
    var settings = $.extend({
      'accept': '',
      'size': 0,
      'limit': 0
    }, options);
    var loading = 'data:gif;base64,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';
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
      return '<img src="'+loading+'"> <strong>'+action+'</strong> <span style="margin:0 15px;">&ldquo;' + data.files[0].name.replace(/[^a-z0-9\.\-_]/gi, '') + '&rdquo;</span>';
    }
    
  };
})(jQuery);