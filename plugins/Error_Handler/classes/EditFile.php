<?php

class EditFile {
  
  public function page () {
    global $page;
    $html = '';
    if (!isset($_GET['file']) || !file_exists(BASE . $_GET['file'])) $page->eject($page->url('delete', '', 'file'));
    if (isset($_POST['ace'])) {
      echo $this->save(BASE . $_GET['file'], $_POST['ace']);
      exit;
    }
    $page->title = implode(' &raquo; ', array_reverse(explode('/', $_GET['file'])));
    $html .= '<div style="margin-top:5px;">';
      $html .= '<div class="text-right" style="margin-bottom:10px;">';
        $html .= '<a id="status" href="' . $page->url('delete', '', array('file', 'line')) . '" class="btn btn-link btn-xs">Return</a>';
        $html .= '<button id="save" class="btn btn-primary btn-xs">Save Changes</button>';
      $html .= '</div>';
    $html .= '</div>'; // end #toolbar
    $html .= '<pre id="editor" style="border:none;">';
      $html .= htmlspecialchars(file_get_contents(BASE . $_GET['file']));
    $html .= '</pre>';
    $ext = substr($_GET['file'], strrpos($_GET['file'], '.') + 1);
    if ($ext == 'js') $ext = 'javascript';
    $line = (isset($_GET['line']) && is_numeric($_GET['line'])) ? $_GET['line'] : 1;
    $page->plugin('Ace');
    $page->plugin('jQuery', array('plugin'=>'bootbox', 'code'=>'
      
      $("#editor").css({height:($(window).height() - 40) + "px"});
      
      var editor = ace.edit("editor");
      editor.setTheme("ace/theme/tomorrow");
      editor.session.setTabSize(2);
      editor.session.setUseSoftTabs(true);
      editor.getSession().setMode("ace/mode/' . $ext . '");
      var focus = setInterval(function(){
        editor.gotoLine(' . $line . ');
        if (editor.isRowVisible(' . ($line - 1) . ') || editor.session.getLength() == 1) clearInterval(focus);
      }, 500);
      
      $("#save").click(function(){
        var btn = $(this);
        if (!btn.hasClass("disabled")) {
          btn.addClass("disabled").removeClass("btn-primary").text("Saving ...");
          $("#status").hide();
          $.ajax({
            url: window.location,
            type: "POST",
            data: {ace:editor.session.getValue()},
            complete: function (xhr) {
              btn.removeClass("disabled").addClass("btn-primary").text("Save Changes");
              if (xhr.status == 200 && xhr.responseText.toString() == "Saved") {
                $("#status").text("Saved").show();
              } else {
                $("#status").text("Error").show();
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
      
      editor.commands.addCommand({
        name: "tab",
        bindKey: {win: "Tab",  mac: "Tab"},
        exec: function() { editor.insert("\t"); },
        readOnly: false
      });
      
      editor.commands.addCommand({
        name: "save",
        bindKey: {win: "Ctrl-S",  mac: "Command-S"},
        exec: function() { $("#save").click(); },
        readOnly: false
      });
      
      editor.commands.addCommand({
        name: "clipboard",
        bindKey: {win: "Ctrl-C",  mac: "Command-C"},
        exec: function() {
          var box = bootbox.alert("<br><textarea id=\'copyandpaste\' spellcheck=\'false\' rows=\'5\' class=\'form-control input-sm\'></textarea>", function(){editor.focus();});
          $("#copyandpaste").val(editor.getCopyText()).click(function(){
            $(this).select();
          }).keyup(function(e){
            if (e.keyCode == 67 && e.ctrlKey) {
              $(this).unbind();
              box.modal("hide");
              editor.focus();
            }
          });
        },
        readOnly: true
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
      
    '));
    return $html;
  }
  
  private function save ($file, $code) {
    $code = (ini_get('magic_quotes_gpc') == '1') ? stripslashes($code) : $code;
    $code = str_replace("\r\n", "\n", base64_decode(base64_encode($code)));
    #-- Check the PHP $output --#
    if (substr($file, -4) == '.php' && !empty($code)) {
      include_once(BASE . 'params.php');
      if (PHP_PATH != '') {
        $linter = BASE . 'syntax-check.php';
        file_put_contents($linter, $code);
        exec(PHP_PATH . ' -l ' . escapeshellarg($linter) . ' 2>&1', $output);
        unlink($linter);
        $output = trim(implode("\n", $output));
        if (!empty($output) && strpos($output, 'No syntax errors') === false) {
          $output = str_replace($linter, 'php file', $output);
          return $output; // ie. false
        }
      }
    }
    file_put_contents($file, $code);
    return 'Saved';
  }

}

?>