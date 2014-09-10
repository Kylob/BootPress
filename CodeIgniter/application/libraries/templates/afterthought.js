(function($){
  $.fn.afterthought = function(post, template){
    var data = {};
    var content = $('body').html();
    var matches = content.match(/<!--post.*?-->/g);
    for (k in matches) {
      var len = new Number(matches[k].length);
      if (len > 0) {
        var json = JSON.parse(matches[k].substr(8, len-11));
        for (k in json) data[k] = json[k];
      }
    }
    var zones = new Array;
    zones[-12]   = 'UM12';
    zones[-11]   = 'UM11';
    zones[-10]   = 'UM10';
    zones[-9.9]  = 'UM95';
    zones[-9]    = 'UM9';
    zones[-8]    = 'UM8';
    zones[-7]    = 'UM7';
    zones[-6]    = 'UM6';
    zones[-5]    = 'UM5';
    zones[-4.5]  = 'UM45';
    zones[-4]    = 'UM4';
    zones[-3.5]  = 'UM35';
    zones[-3]    = 'UM3';
    zones[-2]    = 'UM2';
    zones[-1]    = 'UM1';
    zones[0]     = 'UTC';
    zones[1]     = 'UP1';
    zones[2]     = 'UP2';
    zones[3]     = 'UP3';
    zones[3.5]   = 'UP35';
    zones[4]     = 'UP4';
    zones[4.5]   = 'UP45';
    zones[5]     = 'UP5';
    zones[5.5]   = 'UP55';
    zones[5.75]  = 'UP575';
    zones[6]     = 'UP6';
    zones[6.5]   = 'UP65';
    zones[7]     = 'UP7';
    zones[8]     = 'UP8';
    zones[8.75]  = 'UP875';
    zones[9]     = 'UP9';
    zones[9.5]   = 'UP95';
    zones[10]    = 'UP10';
    zones[10.5]  = 'UP105';
    zones[11]    = 'UP11';
    zones[11.5]  = 'UP115';
    zones[12]    = 'UP12';
    zones[12.75] = 'UP1275';
    zones[13]    = 'UP13';
    zones[14]    = 'UP14';
    var now = new Date().getTimezoneOffset();
    var jan = new Date((new Date()).getFullYear(), 0, 1).getTimezoneOffset();
    var jul = new Date((new Date()).getFullYear(), 6, 1).getTimezoneOffset();
    var dif = Math.abs(jan - jul);
    var dst = (now < Math.max(jan, jul)) ? 1 : 0;
    var offset = now * 60;
    var timezone = now / -60;
    if (dst) timezone -= dif / 60;
    var hemisphere = '';
    if (dif) hemisphere = (jan > jul) ? 'N' : 'S';
    data['dst'] = dst;
    data['offset'] = offset;
    data['timezone'] = (typeof zones[timezone] != 'undefined') ? zones[timezone] : timezone;
    data['hemisphere'] = hemisphere;
    data['referrer'] = document.referrer;
    data['height'] = window.innerHeight;
    data['width'] = window.innerWidth;
    data[post] = template;
    $.ajax({type:'POST', url:location.href, data:data, cache:false, success:function(data){
      $.each(data, function(key,value){
        if (key == 'css') $('<style/>').html(value).appendTo('head');
        else if (key == 'javascript') eval(value);
        else $('<span/>').html(value).prependTo(key);
      });
    }, dataType:'json'});
  };
})(jQuery);