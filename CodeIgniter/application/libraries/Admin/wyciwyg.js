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
            return { start: 0, end: e.value.length, length: 0 }
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

var editor = ace.edit("editor");
editor.setTheme("ace/theme/tomorrow");
editor.setBehavioursEnabled(false);
editor.session.setTabSize(2);
editor.session.setUseSoftTabs(true);

function display_wyciwyg (classes, data, file, line, col) {
  editor.setValue(data, line);
  if (classes.hasClass("noMarkup")) {
    $("#toolbar .markup").hide();
  } else {
    $("#toolbar .markup").show();
  }
  if (classes.hasClass("readOnly")) {
    editor.setReadOnly(true);
    $("#toolbar button.send").hide();
    $("#toolbar .markup").hide();
  } else {
    editor.setReadOnly(false);
    if (classes.hasClass("noSaving")) {
      $("#toolbar button.send").hide();
    } else {
      $("#toolbar button.send").show();
    }
  }
  if (classes.hasClass("php")) {
    editor.getSession().setMode("ace/mode/php");
  } else if (classes.hasClass("tpl")) {
    editor.getSession().setMode("ace/mode/smarty");
  } else if (classes.hasClass("html")) {
    editor.getSession().setMode("ace/mode/html");
  } else if (classes.hasClass("less")) {
    editor.getSession().setMode("ace/mode/less");
  } else if (classes.hasClass("css")) {
    editor.getSession().setMode("ace/mode/css");
  } else if (classes.hasClass("js")) {
    editor.getSession().setMode("ace/mode/javascript");
  } else {
    editor.getSession().setMode("ace/mode/plain_text");
  }
  $("#adminForms").data("scroll", $(window).scrollTop()).hide(0);
  $("#wyciwyg").data("input", file);
  $("#wyciwyg").show(10, function(){
    $(window).scrollTop(0);
    editor.resize();
    editor.focus();
    var focus = setInterval(function(){
      editor.gotoLine(line, col);
      if (editor.isRowVisible(line - 1) || editor.session.getLength() == 1) clearInterval(focus);
    }, 500);
  });
}

$(document).ready(function(){
  
  $("#adminForms").on("click", "textarea.wyciwyg", function(){
    var textarea = $(this);
    var data = textarea.val();
    var file = textarea.attr("id");
    var selected = textarea.getSelection();
    var line = textarea.val().substr(0, selected.end).split("\n").length;
    var col = selected.end - textarea.val().lastIndexOf("\n", selected.end - 1) - 1;
    display_wyciwyg (textarea, data, file, line, col);
  });
  
  $("#adminForms").on("click", "a.wyciwyg", function(e){
    e.preventDefault();
    var a = $(this);
    if (typeof a.data("retrieve") === "undefined") return;
    var file = a.data("retrieve");
    var line = (typeof a.data("line") === "undefined") ? 1 : a.data("line");
    var col = (typeof a.data("col") === "undefined") ? 0 : a.data("col");
    $.post(window.location, {retrieve:file}, function(data){
      display_wyciwyg (a, data, file, line, col);
    }, "text");
  });
  
  $("#wyciwyg").css({height:($(window).height() - 10) + "px"});
  $("#editor").css({height:($("#wyciwyg").height() - 30) + "px"});
  
  $("#toolbar button[title!=\'\']").tooltip({placement:"bottom"});
  
  $("#toolbar").click(function(e){
    $("#wyciwyg").css({height:($(window).height() - 10) + "px"});
    $("#editor").css({height:($("#wyciwyg").height() - 30) + "px"});
    $("html, body").scrollTop($(document).height()-$(window).height());
    editor.resize();
  });
  
  $("#toolbar .insert").click(function(e){
    e.preventDefault();
    var value = $(this).data("value").split("|");
    var text = editor.session.getTextRange(editor.getSelectionRange());
    editor.insert(value.join(text));
    if ($(this).closest("div.btn-group").hasClass("open")) $(this).dropdown("toggle");
    return false;
  });
  
  $("#toolbar").on("click", "button.return", function(){
    var btn = $(this);
    var file = ($("#wyciwyg").data("input").indexOf(".") > 0) ? true : false;
    if (!file) var input = $("#" + $("#wyciwyg").data("input"));
    $("#wyciwyg").hide(10, function(){
      btn.text("Return");
      if (!file) input.val(editor.session.getValue());
      $("#adminForms").show(10, function(){
        $(window).scrollTop($("#adminForms").data("scroll"));
        var cursor = editor.selection.getCursor();
        var lines = editor.session.getValue().split("\n");
        lines.length = cursor.row;
        var goto = lines.join("\n").length + cursor.column + 1;
        if (!file) input.selectRange(goto, goto);
        editor.destroy();
      });
    });
  });
  
  $("#toolbar").on("click", "button.send", function(){
    var btn = $(this);
    if (!btn.hasClass("disabled") && !btn.is(":hidden")) {
      btn.addClass("disabled").removeClass("btn-primary").text("Saving ...");
      $("#toolbar button.return").hide();
      $.ajax({
        url: window.location,
        type: "POST",
        data: {wyciwyg:editor.session.getValue(), field:$("#wyciwyg").data("input")},
        complete: function (xhr) {
          btn.removeClass("disabled").addClass("btn-primary").text("Save Changes");
          if (xhr.status == 200 && xhr.responseText.toString() == "Saved") {
            $("#toolbar button.return").text("Saved").show();
          } else {
            $("#toolbar button.return").text("Error").show();
            if (xhr.status == 200) {
              alert(xhr.responseText);
            } else {
              alert("Server Error: " + xhr.status);
            }
          }
        },
        dataType: "text"
      });
    }
  });
  
  $("#toolbar .increase").click(function(){
    editor.setFontSize(Math.min(editor.getFontSize() + 1, 20));
  });
  
  $("#toolbar .decrease").click(function(){
    editor.setFontSize(Math.max(editor.getFontSize() - 1, 10));
  });
  
  $("#toolbar .wordwrap").click(function(){
    if (editor.session.getUseWrapMode()) {
      editor.session.setUseWrapMode(false);
    } else {
      editor.session.setUseWrapMode(true);
    }
  });
  
  editor.commands.addCommand({
    name: "tab",
    bindKey: {win: "Tab",  mac: "Tab"},
    exec: function() { editor.insert("\t"); },
    readOnly: false
  });
  
  editor.commands.addCommand({
    name: "save",
    bindKey: {win: "Ctrl-S",  mac: "Command-S"},
    exec: function() { $("#toolbar button.send").click(); },
    readOnly: false
  });
  
  editor.commands.addCommand({
    name: "page up",
    bindKey: {win: "Ctrl-Up",  mac: "Command-Up"},
    exec: function() { editor.gotoPageUp(); },
    readOnly: true
  });
  
  editor.commands.addCommand({
    name: "page down",
    bindKey: {win: "Ctrl-Down",  mac: "Command-Down"},
    exec: function() { editor.gotoPageDown(); },
    readOnly: true
  });
  
  editor.commands.addCommand({
    name: "indent",
    bindKey: {win: "Ctrl-Shift-I",  mac: "Command-Shift-I"},
    exec: function() { editor.blockIndent(); },
    readOnly: false
  });
  
  editor.commands.addCommand({
    name: "outdent",
    bindKey: {win: "Ctrl-Shift-O",  mac: "Command-Shift-O"},
    exec: function() { editor.blockOutdent(); },
    readOnly: false
  });
  
});