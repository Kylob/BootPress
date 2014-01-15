<?php

class BlogAdminCode extends BlogAdmin {

  private $folders;
  private $plugins;
  private $file = '';
  
  public function view () {
    global $page;
    $page->access('admin', 1);
    if (isset($_GET['plugins']) && in_array($_GET['plugins'], array('bootpress', 'website'))) {
      $this->plugins = ($_GET['plugins'] == 'bootpress') ? BASE . 'plugins/' : BASE_URI . 'plugins/';
      if (isset($_GET['file']) && file_exists($this->plugins . $_GET['file']) && !is_dir($this->plugins . $_GET['file'])) {
        $this->file = $_GET['file'];
      }
      $active = ($_GET['plugins'] == 'bootpress') ? 'BootPress' : 'Website';
      $content = $this->plugins();
    } else {
      $this->folders = BASE_URI . 'code/';
      $active = 'Folders';
      $content = $this->folders();
    }
    $menu = $this->menu($active) . '<br>';
    return $this->admin($menu . $content);
  }
  
  private function menu ($active) {
    global $page, $bp;
    $url = $this->blog['url'] . 'admin/code/';
    return $bp->pills(array(
      'Folders' => $url,
      'Plugins' => array(
        'BootPress' => $url . '?plugins=bootpress',
        'Website' => $url . '?plugins=website'
      )
    ), array('active'=>$active));
  }
  
  private function plugins () {
    global $page, $bp;
    $html = '';
    #-- Edit File --#
    if (isset($_POST['wyciwyg']) && isset($_POST['field']) && $_POST['field'] == 'code') {
      if (!empty($this->file)) {
        $result = $this->file_put_post($this->plugins . $this->file, 'wyciwyg', false);
        echo ($result === true) ? 'Saved' : $result;
      } else {
        echo 'Error';
      }
      exit;
    }
    #-- Delete File --#
    if (isset($_GET['delete']) && $_GET['delete'] == 'file' && !empty($this->file)) {
      unlink($this->plugins . $this->file);
      $eject = $page->url('delete', '', array('delete', 'file'));
      $dirs = explode('/', substr($this->file, 0, strrpos($this->file, '/')));
      while (!empty($dirs)) { // if $this->file's directory is empty, then delete it
        $dir = implode('/', $dirs) . '/';
        foreach (scandir($this->plugins . $dir) as $file) {
          if ($file != '.' && $file != '..') {
            $page->eject($page->url('add', $eject, 'file', $dir));
          }
        }
        rmdir($this->plugins . $dir);
        array_pop($dirs);
      }
      $page->eject($eject);
    }
    #-- Search for Plugin's Next File to Edit --#
    if (isset($_POST['selected'])) {
      if (file_exists($this->plugins . $_POST['selected']) && is_dir($this->plugins . $_POST['selected'])) {
        echo $this->plugin_options($this->select($_POST['selected']));
      }
      exit;
    }
    $page->plugin('jQuery', array('code'=>'
      $("#selected").on("change", "select", function(){
        $(this).parent("div").nextAll("div").remove();
        var value = $(this).val();
        if (value.slice(-1) == "/") {
          $.post(window.location, {selected:value}, function(data){$("#selected div:last").after(data);}, "html");
        } else if (value != "") {
          window.location = "' . $page->url('delete', '', 'file') . '&file=" + value;
        }
      });
    '));
    #-- Create Plugins File Form --#
    $prompt = ($_GET['plugins'] == 'bootpress') ? 'BootPress' : 'Website';
    $plugins = array();
    if (file_exists($this->plugins)) {
      foreach (scandir($this->plugins) as $dir) {
        if ($dir != '.' && $dir != '..' && is_dir($this->plugins . $dir . '/')) {
          $plugins[$dir . '/'] = $dir;
        }
      }
      natcasesort($plugins);
    }
    $page->plugin('Form_Validation');
    $form = new Form('create_plugins');
    $form->required(array($prompt), false);
    $form->info(array(
      $prompt => 'Enter the name of the file you would like to create.  Only php, txt, css, less, and js files can be managed here.  eg. New_Plugin/index.php',
      'edit' => 'Select a file that you would like to edit.'
    ));
    if (!empty($this->file)) {
      $form->values(array($prompt => substr($this->file, 0, strrpos($this->file, '/') + 1)));
    } elseif (!empty($_GET['file'])) { // a directory
      $form->values(array($prompt => $_GET['file']));
    }
    $form->check(array($prompt => 'ap'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $file = $this->plugin_filter($vars[$prompt]);
      $ext = ($pos = strrpos($file, '.')) ? substr($file, $pos + 1) : false;
      if (!empty($file) && in_array($ext, array('php', 'txt', 'css', 'less', 'js'))) {
        if (!file_exists($this->plugins . $file)) {
          if (!is_dir(dirname($this->plugins . $file))) mkdir(dirname($this->plugins . $file), 0755, true);
          file_put_contents($this->plugins . $file, '');
        }
        $eject = $page->url('add', $eject, 'file', $file);
      }
      $page->eject($eject);
    }
    $html .= $form->header();
    $html .= $form->field('text', $prompt, $prompt, array('prepend'=>'Plugin', 'append'=>'<button data-loading-text="Submitting..." class="btn btn-primary" type="submit">Create</button>'));
    if (!empty($plugins)) {
      $preselect = (isset($_GET['file'])) ? $_GET['file'] : '';
      $html .= $form->label('text', 'edit', 'Edit', '<div id="selected" class="row">' . $this->plugin_options($plugins, $preselect) . '</div>');
    }
    $html .= $form->close();
    unset($form);
    if (empty($this->file)) return $html;
    #-- Edit Plugin's Code Form --#
    $page->title = implode(' &raquo; ', array_reverse(explode('/', $_GET['file'])));
    $form = new Form('edit_plugins');
    $form->required(array('file'), false);
    $form->info(array(
      'file' => 'You can change the file name and location here.  If the new file already exists elsewhere, then it will simply overwrite the old file.',
      'code' => 'Click to edit.'
    ));
    $values = array('file'=>$this->file);
    $values['code'] = addslashes(htmlspecialchars(file_get_contents($this->plugins . $this->file)));
    $form->values($values);
    $form->check(array('file'=>'ap'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $file = $this->plugin_filter($vars['file']);
      $ext = ($pos = strrpos($file, '.')) ? substr($file, $pos + 1) : false;
      if (!empty($file) && $file != $this->file && in_array($ext, array('php', 'txt', 'css', 'less', 'js'))) {
        if (!is_dir(dirname($this->plugins . $file))) mkdir(dirname($this->plugins . $file), 0755, true);
        copy($this->plugins . $this->file, $this->plugins . $file);
        unlink($this->plugins . $this->file);
        $eject = $page->url('add', $eject, 'file', $file);
      }
      $page->eject($eject);
    }
    $html .= $form->header();
    $html .= $form->field('text', 'file', 'File', array('append'=>array(
      $bp->button('link', 'Edit', array(
        'data-loading-text' => 'Submitting...',
        'type' => 'submit'
      )),
      $bp->button('link', $bp->icon('trash'), array(
        'data-url' => $page->url('add', '', 'delete', 'file'),
        'title' => 'Delete',
        'id' => 'delete'
      ))
    )));
    $ext = substr($_GET['file'], strrpos($_GET['file'], '.') + 1);
    $html .= $form->field('textarea', 'code', 'Code', array('class'=>"wyciwyg noMarkup {$ext} input-sm", 'rows'=>5, 'spellcheck'=>'false'));
    $html .= $form->close();
    unset($form);
    $page->plugin('jQuery', array('code'=>'
      $("#delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this file?")) {
          window.location = url;
        }
        return false;
      });
    '));
    return $html;
  }
  
  private function folders () {
    global $page, $bp;
    $html = '';
    if (isset($_GET['delete']) && file_exists($this->folders . $_GET['delete'] . '.php')) {
      unlink($this->folders . $_GET['delete'] . '.php');
      $smarty = $this->folders . $_GET['delete'] . '.tpl';
      if (file_exists($smarty)) unlink($smarty);
      $page->eject($page->url('delete', '', 'delete'));
    }
    if (isset($_GET['folder'])) {
      $edit = $this->folders . $_GET['folder'] . '.php';
      $smarty = $this->folders . $_GET['folder'] . '.tpl';
      if (!file_exists($edit)) $page->eject($page->url('delete', '', 'folder'));
      if (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
        switch ($_POST['field']) {
          case 'php':
            $result = $this->file_put_post($edit, 'wyciwyg', false);
            echo ($result === true) ? 'Saved' : $result;
            break;
          case 'smarty':
            $this->file_put_post($smarty, 'wyciwyg');
            echo 'Saved';
            break;
          default:
            echo 'Error';
            break;
        }
        exit;
      }
    } else {
      $edit = false;
    }
    $page->plugin('jQuery', array('code'=>'
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this folder?")) {
          window.location = url;
        }
        return false;
      });
    '));
    $page->plugin('Form_Validation');
    $form = new Form('blog_folders');
    $form->required(array('folder'), false);
    $form->info(array(
      'folder' => 'Use only lowercase letters and \' - / \'.  As long as you don\'t have another blog post or page by this name, then this folder will be directly accessible at: ' . BASE_URL . '[folder]/...  You, of course, will have to deal with the dot dot dot\'s.  Alternatively, you can create any url rule structure that you like in the .htaccess file, and direct it to the same page as your blog with the additional parameter: ?page=[folder]',
      'php' => 'If you are doing this the easy way, then the code you place here should look something like: $page->plugin(\'...\'); // where the real action takes place',
      'smarty' => 'This is an optional filter that you can run your php through, the same way we do it for this blog\'s content.  It\'s useful for when you want to separate the presentation from the logic, and let someone else make it pretty.'
    ));
    $values = array();
    if ($edit) {
      $values['folder'] = $_GET['folder'];
      $values['php'] = addslashes(htmlspecialchars(file_get_contents($edit)));
      $values['smarty'] = (file_exists($smarty)) ? addslashes(htmlspecialchars(file_get_contents($smarty))) : '';
    }
    $form->values($values);
    $form->check(array('folder'=>'ap', 'php'=>''));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $folder = $this->folder_filter($vars['folder']);
      if (empty($folder)) {
        $form->errors('folder', 'Ugh ... try again');
      } else {
        $file = $this->folders . $folder . '.php';
        if ($edit) {
          if ($file != $edit) {
            if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
            copy($edit, $file);
            unlink($edit);
            if (file_exists($smarty)) {
              copy($smarty, substr($file, 0, -4) . '.tpl');
              unlink($smarty);
            }
          }
        } elseif (!file_exists($file)) {
          $this->file_put_post($file, 'php', false);
        }
        $eject = $page->url('add', $eject, 'folder', $folder);
        $page->eject($eject);
      }
    }
    $html .= $form->header();
    $append = array('/');
    if ($edit) {
      $append[] = $bp->button('link', 'Edit', array(
        'data-loading-text' => 'Submitting...',
        'type' => 'submit'
      ));
      $append[] = $bp->button('link delete', $bp->icon('trash'), array(
        'data-url' => $page->url('add', '', 'delete', $_GET['folder']),
        'title' => 'Delete'
      ));
    } else {
      $append[] = $bp->button('primary', 'Create', array(
        'data-loading-text' => 'Submitting...',
        'type' => 'submit'
      ));
    }
    $html .= $form->field('text', 'folder', 'Folder', array('prepend'=>BASE_URL, 'append'=>$append));
    if ($edit) {
      $html .= $form->field('textarea', 'php', 'PHP', array('class'=>'wyciwyg noMarkup php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      $html .= $form->field('textarea', 'smarty', 'Smarty', array('class'=>'wyciwyg html input-sm', 'rows'=>5, 'spellcheck'=>'false'));
    } else {
      $html .= $form->field('hidden', 'php', "<?php\n\n\n\n?>");
    }
    $html .= $form->close();
    unset($form);
    $this->folder_files($folders);
    sort($folders);
    if (!empty($folders)) {
      $tb = $bp->table('class=table');
      foreach ($folders as $file) {
        $folder = substr($file, strlen($this->folders), -4);
        $tb->row();
        $tb->cell('style=vertical-align:middle;', '<strong>' . $folder . '</strong>');
        $tb->cell('', $bp->group('sm pull-right', array(
          $bp->button('default', $bp->icon('pencil'), array(
            'href' => $page->url('add', '', 'folder', $folder),
            'title' => 'Edit'
          )),
          $bp->button('default delete', $bp->icon('trash'), array(
            'data-url' => $page->url('add', '', 'delete', $folder),
            'title' => 'Delete'
          )),
          $bp->button('default', $bp->icon('share-alt'), array(
            'href' => BASE_URL . $folder . '/',
            'title' => 'View'
          ))
        )));
      }
      $html .= $tb->row()->cell('colspan=2')->close();
    }
    return $html;
  }
  
  private function folder_files (&$folders, $dir='') { // used in $this->folders() to display $this->folders
    if (empty($dir)) $dir = $this->folders;
    $empty = true;
    foreach (scandir($dir) as $file) {
      if ($file != '.' && $file != '..') {
        if (is_dir($dir . $file)) {
          $this->folder_files($folders, $dir . $file . '/');
          $empty = false;
        } elseif (preg_match('/\.(php)$/', $file)) {
          $folders[] = $dir . $file;
          $empty = false;
        }
      }
    }
    if ($empty) rmdir($dir);
  }
  
  private function folder_filter ($file) {
    $file = preg_replace('/[^a-z\-\/]/', '', strtolower($file)); // lowercase letters and - /
    $file = preg_replace('/[\-\/](?=[\-\/])/', '', $file); // no doubled up punctuation
    $file = trim($file, '/'); // no trailing (or preceding) slashes
    return trim($file, '-'); // no trailing (or preceding) dashes
  }
  
  private function plugin_filter ($file) {
    $file = preg_replace('/[^a-z._\-\/]/i', '', $file); // letters and . _ - /
    $file = preg_replace('/[._\-\/](?=[._\-\/])/', '', $file); // no doubled up punctuation
    $folders = explode('/', trim($file, '/')); // no trailing (or preceding) slashes
    $file = array_pop($folders); // keep the last chunk as is
    foreach ($folders as $key => $value) $folders[$key] = str_replace('.', '-', $value); // for folder integrity
    return (!empty($folders)) ? implode('/', $folders) . '/' . $file : $file;
  }
  
  private function plugin_options ($select, $file='') { // used in $this->plugins() for selecting code to edit
    $html = '<select name="edit[]" class="form-control"><option value="">&nbsp;</option>';
    foreach ($select as $key => $value) {
      $html .= '<option value="' . $key . '"';
      if (strpos($file, $key) !== false) {
        $html .= ' selected="selected"';
        $selected = $key;
      }
      $html .= '>' . $value . '</option>';
    }
    $html .= '</select>';
    $html = '<div class="col-sm-4 col-md-3">' . $html . '</div>';
    if (isset($selected) && is_dir($this->plugins . $selected)) {
      $html .= $this->plugin_options($this->select($selected), $file);
    }
    return $html;
  }
  
  private function select ($dir) { // without the BASE
    $dirs = $files = array();
    foreach (scandir($this->plugins . $dir) as $file) {
      if ($file != '.' && $file != '..') {
        if (is_dir($this->plugins . $dir . $file)) {
          $dirs[$dir . $file . '/'] = $file;
        } elseif (preg_match('/\.(php|txt|css|less|js)$/', $file)) {
          $files[$dir . $file] = $file;
        }
      }
    }
    return array_merge($files, $dirs);
  }
  
}

?>