<?php

class BlogAdmin extends Blog {

  public function __construct ($settings, $file='') {
    global $page;
    parent::__construct($settings);
    if (!$this->admin) $page->eject($this->blog['url']);
    if (empty($file) || (empty($this->blog['name']) && $file != 'Setup') ) {
      $url = (empty($this->blog['name'])) ? $this->blog['url'] . 'admin/setup/' : $this->blog['url'] . 'admin/blog/';
      $page->eject($url);
    }
    $page->robots = false;
    $blog = (!empty($this->blog['name'])) ? $this->blog['name'] : 'Blog';
    $page->title = 'Admin &raquo; ' . $file . ' &raquo; ' . $blog;
  }
  
  public function admin ($content=false) {
    global $page, $bp;
    $html = '';
    $html .= '<div id="adminForms">';
      $url = $this->blog['url'] . 'admin/';
      $links = array();
      if (empty($this->blog['name'])) {
        $html .= '<div class="row"><div class="col-sm-12">' . $bp->breadcrumbs(array('Blog'=>$this->blog['url'], 'Admin')) . '</div></div>';
        $links['Setup'] = $url . 'setup/';
      } else {
        $html .= '<div class="row">';
          $html .= '<div class="col-sm-12">' . $bp->breadcrumbs(array($this->blog['name'] => $this->blog['url'], 'Admin')) . '</div>';
        $html .= '</div>';
        $links['Setup'] = $url . 'setup/';
        $links['Blog'] = $url . 'blog/';
        $links['Layout'] = $url . 'layout/';
        $links['Bootstrap'] = $url . 'bootstrap/';
        $links['Images'] = $url . 'images/';
        if (is_admin(1)) $links['Code'] = $url . 'code/';
      }
      $html .= $bp->row('sm', array(
        $bp->col(2, $bp->pills($links, array('active'=>$page->url('delete', '', '?'), 'align'=>'stacked'))),
        $bp->col(10, $content)
      ));
    $html .= '</div>';
    $html .= $this->wyciwyg();
    $page->link('<style type="text/css">
      textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }
    </style>');
    return $this->layout($html, 'admin');
  }
  
  protected function save_resources_used ($save, $content) {
    $used = array();
    $this->db->query('SELECT resource_id FROM images WHERE blog_id = ?', array($save));
    while (list($id) = $this->db->fetch('row')) $used[$id] = '';
    $this->db->delete('images', 'blog_id', $save);
    $images = str_replace(array('.', '/'), array('\.', '\/'), $this->blog['img']);
    preg_match_all('/(\{\$blog\[\'img\']}|' . $images . ')([0-9]+)\.(jpe?g|gif|png|ico){1}/', $content, $matches);
    $images = array_unique($matches[2]);
    foreach ($images as $id) {
      $this->db->insert('images', array('blog_id'=>$save, 'resource_id'=>$id));
      $this->research($id);
      if (isset($used[$id])) unset($used[$id]);
    }
    foreach ($used as $id => $blank) $this->research($id);
  }
  
  protected function research ($id) { // used when searching for images
    $docid = $id;
    $this->db->query('SELECT type, parent, name, tags FROM resources WHERE id = ?', array($docid));
    list($type, $parent, $name, $tags) = $this->db->fetch('row');
    if ($parent != 0) return $this->research($parent);
    $search = array();
    $ids = array($id);
    $types = array($type);
    $this->db->query('SELECT id, type FROM resources WHERE parent = ?', array($docid));
    while (list($id, $type) = $this->db->fetch('row')) {
      $ids[] = $id;
      $types[] = $type;
    }
    $used = array();
    $this->db->query('SELECT u.blog_id, b.title 
                      FROM images AS u 
                      LEFT JOIN blog AS b ON u.blog_id = b.id 
                      WHERE u.resource_id IN (' . implode(', ', $ids) . ')');
    while (list($id, $title) = $this->db->fetch('row')) $used[] = (is_numeric($id)) ? $title : $id;
    $search[] = str_replace(array('-', '/'), ' ', $name);
    $search[] = implode(' ', $ids);
    $search[] = implode(' ', array_unique($types));
    if (!empty($used)) $search[] = implode(' ', $used);
    if (!empty($tags)) $search[] = str_replace(',', ' ', $tags);
    $search = implode(' ', $search);
    $this->db->delete('research', 'docid', $docid);
    $this->db->insert('research', array('docid'=>$docid, 'resource'=>$search));
  }
  
  protected function file_put_post ($file, $post, $remove_empty=true) { // the $_POST key
    $code = $this->code($post);
    if (empty($code) && $remove_empty) {
      if (file_exists($file)) unlink($file);
      return true;
    }
    #-- Check the PHP $output --#
    if (substr($file, -4) == '.php' && !empty($code)) {
      include_once(BASE . 'params.php');
      if (PHP_PATH != '') {
        $linter = $this->dir . 'syntax-check.php';
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
    #-- Create / Update the file --#
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
    file_put_contents($file, $code);
    return true;
  }
  
  protected function code ($post) {
    $code = (ini_get('magic_quotes_gpc') == '1') ? stripslashes($_POST[$post]) : $_POST[$post];
    return str_replace("\r\n", "\n", base64_decode(base64_encode($code)));
  }
  
  protected function wyciwyg () {
    global $page;
    $page->link(array(
      $this->url . 'js/jquery.fieldSelection.js',
      $this->url . 'js/jquery.selectRange.js'
    ));
    /*
    $page->plugin('CDN', 'link', 'zeroclipboard/1.2.3/ZeroClipboard.min.js');
      var client = new ZeroClipboard($("#clipboard"), {
        moviePath: "http://localhost/fixitman.com/plugins/CDN/jsdelivr/files/zeroclipboard/1.2.3/ZeroClipboard.swf"
      });
    */
    $page->plugin('CDN', 'link', 'ace/1.1.01/min/ace.js');
    $page->plugin('jQuery', array('plugin'=>'bootbox', 'code'=>'
    
      var editor = ace.edit("editor");
      editor.setTheme("ace/theme/tomorrow");
      editor.session.setTabSize(2);
      editor.session.setUseSoftTabs(true);
      
      $("textarea.wyciwyg").click(function(){
        var selected = $(this).getSelection();
        var line = $(this).val().substr(0, selected.end).split("\n").length;
        var col = selected.end - $(this).val().lastIndexOf("\n", selected.end - 1) - 1;
        editor.setValue($(this).val(), line);
        if ($(this).hasClass("noMarkup")) {
          $("#toolbar .markup").hide();
        } else {
          $("#toolbar .markup").show();
        }
        if ($(this).hasClass("php")) {
          editor.getSession().setMode("ace/mode/php");
        } else if ($(this).hasClass("html")) {
          editor.getSession().setMode("ace/mode/html");
        } else if ($(this).hasClass("less")) {
          editor.getSession().setMode("ace/mode/less");
        } else if ($(this).hasClass("css")) {
          editor.getSession().setMode("ace/mode/css");
        } else if ($(this).hasClass("js")) {
          editor.getSession().setMode("ace/mode/javascript");
        } else {
          editor.getSession().setMode("ace/mode/plain_text");
        }
        if ($(this).hasClass("readOnly")) {
          editor.setReadOnly(true);
          $("#toolbar button.send").hide();
          $("#toolbar .markup").hide();
        } else {
          editor.setReadOnly(false);
          $("#toolbar button.send").show();
        }
        $("#adminForms").data("scroll", $(window).scrollTop()).hide(0);
        $("#wyciwyg").data("input", $(this).attr("id"));
        $("#wyciwyg").show(10, function(){
          $(window).scrollTop(0);
          editor.resize();
          editor.focus();
          var focus = setInterval(function(){
            editor.gotoLine(line, col);
            if (editor.isRowVisible(line - 1) || editor.session.getLength() == 1) clearInterval(focus);
          }, 500);
        });
      });
      
      $("#wyciwyg").css({height:($(window).height() - 40) + "px"});
      
      $("#toolbar button[title!=\'\']").tooltip({placement:"bottom"});
      
      $("#toolbar").click(function(e){
        $("#wyciwyg").css({height:($(window).height() - 50) + "px"});
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
        var input = $("#" + $("#wyciwyg").data("input"));
        $("#wyciwyg").hide(10, function(){
          btn.text("Return");
          input.val(editor.session.getValue());
          $("#adminForms").show(10, function(){
            $(window).scrollTop($("#adminForms").data("scroll"));
            var cursor = editor.selection.getCursor();
            var lines = editor.session.getValue().split("\n");
            lines.length = cursor.row;
            var goto = lines.join("\n").length + cursor.column + 1;
            input.selectRange(goto, goto);
            editor.destroy();
          });

        });
      });
      
      $("#toolbar").on("click", "button.send", function(){
        var btn = $(this);
        if (!btn.hasClass("disabled")) {
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
        name: "clipboard",
        bindKey: {win: "Ctrl-C",  mac: "Command-C"},
        exec: function() {
          var box = bootbox.alert("<br><textarea id=\'copyandpaste\' spellcheck=\'false\' rows=\'5\' class=\'form-control input-sm\'></textarea>", function(){editor.focus();});
          $("#copyandpaste").val(editor.getCopyText()).click(function(){
            $(this).select();
          }).keyup(function(e){
            if (e.keyCode == 67) {
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
      
    '));
    $html = '';
    
    $html .= '<div id="wyciwyg" style="padding-top:5px; display:none;">';
  
      $html .= '<div id="toolbar" class="btn-toolbar">';
      
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-header"></i> <span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h1&gt;|&lt;/h1&gt;"><h1>Heading 1</h1></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h2&gt;|&lt;/h2&gt;"><h2>Heading 2</h2></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h3&gt;|&lt;/h3&gt;"><h3>Heading 3</h3></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h4&gt;|&lt;/h4&gt;"><h4>Heading 4</h4></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h5&gt;|&lt;/h5&gt;"><h5>Heading 5</h5></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h6&gt;|&lt;/h6&gt;"><h6>Heading 6</h6></a></li>';
          $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;p&gt;|&lt;/p&gt;">&para; Paragraph</button>';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;blockquote&gt;&#10;&#09;&lt;p&gt;|&lt;/p&gt;&#10;&lt;/blockquote&gt;"><b>&nbsp;&quot;&nbsp;</b> Blockquote</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-left&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-left"></i> Left Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-center&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-center"></i> Center Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-right&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-right"></i> Right Aligned</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-list"></i> List <span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ol&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ol&gt;">Ordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;unstyled&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unstyled</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;inline&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inline</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">Definition</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl class=&quot;dl-horizontal&quot;&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Horizontal</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
          
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;a href=&quot;|&quot;&gt;|&lt;/a&gt;" title="Link"><i class="glyphicon glyphicon-link"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;img src=&quot;|&quot; width=&quot;&quot; height=&quot;&quot; alt=&quot;&quot;&gt;" title="Image"><i class="glyphicon glyphicon-picture"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;table class=&quot;table&quot;&gt;&#10;&#09;&lt;thead&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;th&gt;|&lt;/th&gt;&#10;&#09;&#09;&#09;&lt;th&gt;&lt;/th&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/thead&gt;&#10;&#09;&lt;tbody&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/tbody&gt;&#10;&lt;/table&gt;" title="Table"><i class="glyphicon glyphicon-th-large"></i></button>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;strong&gt;|&lt;/strong&gt;" title="Bold"><i class="glyphicon glyphicon-bold"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;em&gt;|&lt;/em&gt;" title="Italic"><i class="glyphicon glyphicon-italic"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;u&gt;|&lt;/u&gt;" title="Underline"><u><b>U</b></u></button>';
        $html .= '</div>';
        
        $html .= '<div class="text-right" style="margin-bottom:10px;">';
          $html .= '<button class="btn btn-link btn-xs return" title="Click to Return">Return</button>';
          $html .= '<button class="btn btn-primary btn-xs send">Save Changes</button>';
          $html .= '<div class="btn-group btn-group-xs pull-right" style="margin-left:5px;">';
            $html .= '<button class="btn btn-default increase" title="Increase Font Size"><i class="glyphicon glyphicon-plus"></i></button>';
            $html .= '<button class="btn btn-default decrease" title="Decrease Font Size"><i class="glyphicon glyphicon-minus"></i></button>';
            $html .= '<button class="btn btn-default wordwrap" title="Toggle Wordwrap"><i class="glyphicon glyphicon-transfer"></i></button>';
          $html .= '</div>';
        $html .= '</div>';
        
      $html .= '</div>'; // end #toolbar
      
      $html .= '<div id="editor" style="height:100%;"></div>';
      
    $html .= '</div>'; // end #wyciwyg
    
    
    return $html;
  }
  
}

?>