$(window).on('load', function () {
    var performance = window.performance || window.mozPerformance || window.msPerformance || window.webkitPerformance || {};
    var timer = performance.timing || {};
    var offset = (new Date).getTimezoneOffset();
    var jan = (new Date((new Date).getFullYear(), 0, 1)).getTimezoneOffset();
    var jul = (new Date((new Date).getFullYear(), 6, 1)).getTimezoneOffset();
    var diff = Math.abs(jan - jul);
    var dst = offset < Math.max(jan, jul) ? 1 : 0;
    var timezone = offset / -60;
    if (dst) {
        timezone -= diff / 60; }
    timezone = (timezone == 0) ? "UTC" : (timezone > 0 ? "UP" : "UM") + timezone.toString().replace(/[^\d]/g, "");
    var hemisphere = (diff) ? (jan > jul ? "N" : "S") : "";
    $.ajax({
        type: "POST",
        url: location.href,
        data: {
            width: window.innerWidth,
            height: window.innerHeight,
            hemisphere: hemisphere,
            timezone: timezone,
            dst: dst,
            offset: offset * 60,
            timer: (timer) ? {
                loaded: timer.domContentLoadedEventEnd - timer.navigationStart,
                server: timer.responseEnd - timer.domainLookupStart,
                dns: timer.domainLookupEnd - timer.domainLookupStart,
                tcp: timer.connectEnd - timer.connectStart,
                request: timer.responseStart - timer.requestStart,
                response: timer.responseEnd - timer.responseStart
            } : false
        },
        cache: false,
        success: function (data) {
            $.each(data, function (key, value) {
                if (key == "css") {
                    // http://stackoverflow.com/questions/524696/how-to-create-a-style-tag-with-javascript
                    var style = document.createElement("style");
                    style.type = "text/css";
                    document.getElementsByTagName("head")[0].appendChild(style);
                    if (style.styleSheet) {
                        style.styleSheet.cssText = value;
                    } else {
                        style.appendChild(document.createTextNode(value));
                    }
                } else if (key == "javascript") {
                    // http://stackoverflow.com/questions/610995/cant-append-script-element
                    var script = document.createElement("script");
                    script.type = "text/javascript";
                    script.text = value;
                    document.body.appendChild(script);
                    document.body.removeChild(document.body.lastChild);
                } else {
                    $("<span/>").html(value).appendTo(key);
                }
            })
        },
        dataType: "json"
    });
});