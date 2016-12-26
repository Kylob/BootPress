/* From: Alex Brem @ http://blog.0xab.cd - fieldSelection jQuery plugin */
(function() {
    var fieldSelection = {
        getSelection: function() {
            var e = (this.jquery) ? this[0] : this;
            return (
            ("selectionStart" in e && function() {
                var l = e.selectionEnd - e.selectionStart;
                return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
            }) ||
            (document.selection && function() {
                e.focus();
                var r = document.selection.createRange();
                if (r === null) {
                    return { start: 0, end: e.value.length, length: 0 };
                }
                var re = e.createTextRange();
                var rc = re.duplicate();
                re.moveToBookmark(r.getBookmark());
                rc.setEndPoint("EndToStart", re);
                return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
            }) ||
            function() { return null; }
            )();
        },
        replaceSelection: function() {
            var e = (typeof this.id == "function") ? this.get(0) : this;
            var text = arguments[0] || "";
            return (
            ("selectionStart" in e && function() {
                e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length); return this;
            }) ||
            (document.selection && function() {
                e.focus(); document.selection.createRange().text = text; return this;
            }) || 
            function() { e.value += text; return jQuery(e); }
            )();
        }
    };
    jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });
})();

/* From: http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area */
$.fn.selectRange = function(start, end) {
    if(!end) end = start; 
    return this.each(function() {
        if (this.setSelectionRange) {
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd("character", end);
            range.moveStart("character", start);
            range.select();
        }
    });
};

var acefile = {
    compare: '',
    changes: false,
    send: function () {
        var btn = $("#toolbar button.send");
        if (!btn.hasClass("disabled") && !btn.is(":hidden")) {
            editor.setReadOnly(true);
            acefile.changes = false;
            btn.addClass("disabled").removeClass("btn-primary").data("html", btn.html()).text("Saving ...");
            $("#toolbar button.eject").hide();
            $("#toolbar div.status").hide();
            setTimeout(function(){ acefile.save(editor.getSession().getValue()); }, 100);
        }
    },
    save: function (data) {
        $.ajax({url:window.location, type:"POST", data:{wyciwyg:data, field:$("#wyciwyg").data("input")}, complete:function(xhr){
                acefile.response(xhr.status == 200 ? xhr.responseText.toString() : "Server Error: " + xhr.status);
            }, dataType:"text"});
    },
    response: function (status) {
        var btn = $("#toolbar button.send");
        btn.removeClass("disabled").addClass("btn-primary").html(btn.data("html")).hide();
        $("#toolbar button.eject").show();
        if (status == "Saved") {
            $("#toolbar div.status").removeClass("text-danger").addClass("text-success").text("Saved").show();
        } else {
            $("#toolbar div.status").removeClass("text-success").addClass("text-danger").text("Error").show();
            bootbox.alert({
                size: "large",
                message: status,
                backdrop: false,
                callback: function(){
                    editor.focus();
                }
            });
        }
        acefile.compare = editor.getSession().getValue();
        acefile.changes = btn;
        editor.setReadOnly(false);
    }
};

var editor = ace.edit("editor");
editor.$blockScrolling = Infinity; // Try removing this in the future.
editor.setTheme("ace/theme/tomorrow");
editor.setFontSize(14);
editor.setBehavioursEnabled(false);
editor.getSession().setTabSize(4);
editor.getSession().setUseSoftTabs(true);
editor.getSession().on('change', function(e) {
    if (acefile.changes) {
        if (acefile.compare != editor.getSession().getValue()) {
            acefile.changes.show();
        } else {
            acefile.changes.hide();
        }
    }
});

function display_wyciwyg (classes, data, retrieve, file, line, col) {
    acefile.compare = data;
    $("#toolbar div.file").text(file);
    $("#toolbar div.status").hide();
    if (classes.hasClass("noMarkup")) {
        $("#toolbar .markup").hide();
    } else {
        $("#toolbar .markup").show();
    }
    $("#toolbar button.send").hide();
    if (classes.hasClass("readOnly")) {
        $("#toolbar .markup").hide();
        editor.setReadOnly(true);
        acefile.changes = false;
    } else {
        acefile.changes = $("#toolbar button.send");
        editor.setReadOnly(false);
    }
    editor.getSession().setValue(data, line);
    if (classes.hasClass("php")) {
        editor.getSession().setMode("ace/mode/php");
    } else if (classes.hasClass("ini")) {
        editor.getSession().setMode("ace/mode/ini");
    } else if (classes.hasClass("yml")) {
        editor.getSession().setMode("ace/mode/yaml");
    } else if (classes.hasClass("tpl")) {
        editor.getSession().setMode("ace/mode/smarty");
    } else if (classes.hasClass("twig")) {
        editor.getSession().setMode("ace/mode/twig");
    } else if (classes.hasClass("html")) {
        editor.getSession().setMode("ace/mode/html");
    } else if (classes.hasClass("less")) {
        editor.getSession().setMode("ace/mode/less");
    } else if (classes.hasClass("scss")) {
        editor.getSession().setMode("ace/mode/scss");
    } else if (classes.hasClass("css")) {
        editor.getSession().setMode("ace/mode/css");
    } else if (classes.hasClass("js")) {
        editor.getSession().setMode("ace/mode/javascript");
    } else if (classes.hasClass("json")) {
        editor.getSession().setMode("ace/mode/json");
    } else if (classes.hasClass("xml")) {
        editor.getSession().setMode("ace/mode/xml");
    } else if (classes.hasClass("rdf")) {
        editor.getSession().setMode("ace/mode/xml");
    } else if (classes.hasClass("rss")) {
        editor.getSession().setMode("ace/mode/xml");
    } else if (classes.hasClass("atom")) {
        editor.getSession().setMode("ace/mode/xml");
    } else {
        editor.getSession().setMode("ace/mode/plain_text");
    }
    $("#adminForms").data("scroll", $(window).scrollTop()).hide(0);
    $("#wyciwyg").data("input", retrieve);
    $("#wyciwyg").show(10, function(){
        $("html").css("background-color", "#fff");
        $(window).scrollTop(0);
        editor.resize();
        editor.focus();
        var focus = setInterval(function(){
            editor.gotoLine(line, col);
            if (editor.isRowVisible(line - 1) || editor.getSession().getLength() == 1) clearInterval(focus);
        }, 500);
    });
}

$(document).ready(function(){

    jQuery.fn.center = function () {
        this.css("position", "absolute");
        this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop()) + "px");
        this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + $(window).scrollLeft()) + "px");
        return this;
    }

    $("#adminForms").on("click", "textarea.wyciwyg", function(){
        var textarea = $(this);
        var data = textarea.val();
        var retrieve = textarea.attr("name");
        var file = textarea.data("file");
        var selected = textarea.getSelection();
        var line = textarea.val().substr(0, selected.end).split("\n").length;
        var col = selected.end - textarea.val().lastIndexOf("\n", selected.end - 1) - 1;
        display_wyciwyg (textarea, data, retrieve, file, line, col);
    });

    $("#adminForms").on("click", "a.wyciwyg", function(e){
        e.preventDefault();
        var a = $(this);
        if (typeof a.data("retrieve") === "undefined") return;
        var retrieve = a.data("retrieve");
        var file = a.data("file");
        var line = (typeof a.data("line") === "undefined") ? 1 : a.data("line");
        var col = (typeof a.data("col") === "undefined") ? 0 : a.data("col");
        $.post(window.location.href, {retrieve:retrieve}, function(data){
            display_wyciwyg (a, data, retrieve, file, line, col);
        }, "text");
    });

    $("#wyciwyg").css({height:($(window).height() - 20) + "px"});
    $("#editor").css({height:($("#wyciwyg").height() - 35) + "px"});
    $("#wyciwyg").center();

    $("#toolbar button[title!=\'\']").tooltip({placement:"bottom"});

    $("#toolbar").click(function(e){
        $("#wyciwyg").css({height:($(window).height() - 20) + "px"});
        $("#editor").css({height:($("#wyciwyg").height() - 35) + "px"});
        $("#wyciwyg").center();
        $(window).scrollTop(0);
        editor.resize();
    });

    $("#toolbar .insert").click(function(e){
        e.preventDefault();
        var value = $(this).data("value").split("|");
        var text = editor.getSession().getTextRange(editor.getSelectionRange());
        editor.insert(value.join(text));
        if ($(this).closest("div.btn-group").hasClass("open")) $(this).dropdown("toggle");
        return false;
    });

    $("#toolbar").on("click", "button.eject", function(){
        var btn = $(this);
        var file = ($("#wyciwyg").data("input").indexOf(".") > 0) ? true : false;
        if (!file) var input = $("textarea[name=" + $("#wyciwyg").data("input") + "]");
        $("#wyciwyg").hide(10, function(){
            $("html").css("background-color", "");
            if (!file) input.val(editor.getSession().getValue());
            $("#adminForms").show(10, function(){
                $(window).scrollTop($("#adminForms").data("scroll"));
                var cursor = editor.selection.getCursor();
                var lines = editor.getSession().getValue().split("\n");
                lines.length = cursor.row;
                var goto = lines.join("\n").length + cursor.column + 1;
                if (!file) input.selectRange(goto, goto);
            });
        });
    });

    $("#toolbar").on("click", "button.send", acefile.send);

    $("#toolbar .increase").click(function(){
        editor.setFontSize(Math.min(editor.getFontSize() + 1, 20));
    });

    $("#toolbar .decrease").click(function(){
        editor.setFontSize(Math.max(editor.getFontSize() - 1, 10));
    });

    $("#toolbar .wordwrap").click(function(){
        if (editor.getSession().getUseWrapMode()) {
            editor.getSession().setUseWrapMode(false);
        } else {
            editor.getSession().setUseWrapMode(true);
        }
    });

    editor.commands.addCommand({
        name: "save",
        bindKey: {win: "Ctrl-S",  mac: "Command-S"},
        exec: function() { $("#toolbar button.send").click(); },
        readOnly: false
    });

});
