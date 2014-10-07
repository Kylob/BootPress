<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_php extends CI_Driver {

  private $folders = false;
  private $plugins = false;
  private $file = '';
  
  public function view () {
    global $page;
    if (isset($_GET['plugins']) && in_array($_GET['plugins'], array('bootpress', 'website'))) {
      $this->plugins = ($_GET['plugins'] == 'bootpress') ? BASE . 'plugins/' : BASE_URI . 'plugins/';
      if (isset($_GET['file']) && file_exists($this->plugins . $_GET['file']) && !is_dir($this->plugins . $_GET['file'])) {
        $this->file = $_GET['file'];
      }
      $this->selected();
      $active = ($_GET['plugins'] == 'bootpress') ? 'BootPress' : 'Website';
      $edit = $this->plugins_edit(); //  call first to save some overhead when saving code or retrieving files
      $create = $this->plugins_create();
      $content = $create . $edit;
    } else {
      $this->folders = BASE_URI . 'code/';
      $active = 'Folders';
      $edit = $this->folders_edit();
      $create = $this->folders_create();
      $content = $create . $edit;
    }
    $menu = $this->menu($active) . '<br>';
    return $this->admin($menu . $content);
  }
  
  private function menu ($active) {
    global $page, $bp;
    $url = BASE_URL . ADMIN . '/php';
    return $bp->pills(array(
      'Folders' => $url,
      'Plugins' => array(
        'BootPress' => $url . '?plugins=bootpress',
        'Website' => $url . '?plugins=website'
      )
    ), array('active'=>$active));
  }
  
  private function folders_create () {
    global $bp, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'create_folders');
    $menu = array();
    $folders = $this->folder_files();
    sort($folders);
    foreach ($folders as $file) {
      $file = substr($file, strlen($this->folders), -4);
      $menu[$file] = $file;
    }
    $form->menu('edit', $menu, '&nbsp;');
    if (isset($_GET['folder'])) $form->values('edit', $_GET['folder']);
    $form->validate(
      array('url', 'URL', 'required', 'The folder you create here will be directly accessible at: ' . BASE_URL . '[folder]/...  You, of course, will have to deal with the dot dot dot\'s.  Alternatively, you can create any url rule structure that you like in the .htaccess file, and direct it to the same page as your blog with the additional parameter: ?page=[folder]'),
      array('edit', 'Edit', 'default[]', 'Select a folder that you would like to edit.')
    );
    if ($form->submitted() && empty($form->errors)) {
      $folder = $this->folder_filter($form->vars['url']);
      $file = $this->folders . $folder . '.php';
      if (empty($folder)) $form->errors['url'] = 'Ugh ... try again';
      if (file_exists($file)) $form->errors['url'] = 'This folder already exists';
      if (empty($form->errors)) {
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        file_put_contents($file, "<?php\n\n\n\n?>");
        $page->eject($page->url('add', $form->eject, 'folder', $folder));
      }
    }
    $html .= $form->header();
    $html .= $form->field('url', 'text', array('prepend'=>BASE_URL, 'append'=>array('/', $bp->button('primary', 'Create', array('type'=>'Submit', 'data-loading-text'=>'Submitting...')))));
    $html .= $form->field('edit', !empty($menu) ? 'select' : 'hidden');
    $html .= $form->close();
    $page->plugin('jQuery', 'code', '
      $("#' . $form->id('edit') . '").change(function(){
        $(this).parent("div").nextAll("div").remove();
        var value = $(this).val();
        if (value != "") window.location = "' . $page->url('delete', '', '?') . '?folder=" + value;
      });
    ');
    unset($form);
    return $html;
  }
  
  private function folders_edit () {
    global $bp, $page;
    $html = '';
    if (!isset($_GET['folder'])) return $html;
    if (!file_exists($this->folders . $_GET['folder'] . '.php')) $page->eject($page->url('delete', '', 'folder'));
    $form = $page->plugin('Form', 'name', 'edit_folders');
    $fields = array_flip($form->id(array('php', 'smarty')));
    $code = $this->folders . $_GET['folder'] . '.php';
    $smarty = $this->folders . $_GET['folder'] . '.tpl';
    if (isset($_POST['wyciwyg']) && isset($_POST['field']) && isset($fields[$_POST['field']])) {
      switch ($fields[$_POST['field']]) {
        case 'php':
          $result = $this->file_put_post($code, 'wyciwyg', false);
          echo ($result === true) ? 'Saved' : $result;
          break;
        case 'smarty':
          $result = $this->file_put_post($smarty, 'wyciwyg');
          echo ($result === true) ? 'Saved' : $result;
          break;
        default:
          echo 'Error';
          break;
      }
      exit;
    }
    if (isset($_GET['delete']) && $_GET['delete'] == 'folder') {
      unlink($code);
      if (file_exists($smarty)) unlink($smarty);
      $page->eject($page->url('delete', '', array('delete', 'folder')));
    }
    $page->plugin('jQuery', 'code', '
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this folder?")) {
          window.location = url;
        }
        return false;
      });
    ');
    $form->values(array(
      'folder' => $_GET['folder'],
      'php' => file_get_contents($code),
      'smarty' => (file_exists($smarty)) ? file_get_contents($smarty) : ''
    ));
    $form->validate('folder', 'Folder', 'required', 'Use only lowercase letters and \' - / \'.');
    $form->validate('php', 'PHP', '', 'If you are doing this the easy way, then the code you place here should look something like: $page->plugin(\'...\'); // where the real action takes place');
    $form->validate('smarty', 'Smarty', '', 'This is an optional filter that you can run your php through, the same way we do it for this blog\'s content.  It\'s useful for when you want to separate the presentation from the logic, and let someone else make it pretty.');
    if ($form->submitted()) {
      $folder = $this->folder_filter($form->vars['folder']);
      $file = $this->folders . $folder . '.php';
      if (empty($folder)) $form->errors['folder'] = 'Ugh ... try again';
      if ($_GET['folder'] != $folder && file_exists($file)) $form->errors['folder'] = 'This folder already exists';
      if (empty($form->errors)) {
        if ($_GET['folder'] != $folder) {
          if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
          rename($code, $file);
          if (file_exists($smarty)) rename($smarty, $this->folders . $folder . '.tpl');
        }
        $page->eject($page->url('add', $form->eject, 'folder', $folder));
      }
    }
    $html .= $form->header();
    $html .= $form->fieldset('<a href="' . BASE_URL . $_GET['folder'] . '/" title="View Page">' . BASE_URL . $_GET['folder'] . '/</a>',
      $form->field('folder', 'text', array(
        'prepend' => $bp->button('link delete', $bp->icon('trash'), array('data-url'=>$page->url('add', '', 'delete', 'folder'), 'title'=>'Delete')),
        'append' => $bp->button('warning', 'Edit', array('type'=>'Submit', 'data-loading-text'=>'Submitting...'))
      )),
      $form->field('php', 'textarea', array('class'=>'wyciwyg noMarkup php input-sm', 'rows'=>5, 'spellcheck'=>'false')),
      $form->field('smarty', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'rows'=>5, 'spellcheck'=>'false'))
    );
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function plugins_edit () {
    global $bp, $page;
    if (empty($this->file)) return '';
    $form = $page->plugin('Form', 'name', 'edit_plugins');
    $fields = array_flip($form->id(array('code')));
    $html = '';
    #-- Edit File --#
    if (isset($_POST['wyciwyg']) && isset($_POST['field']) && isset($fields[$_POST['field']]) && $fields[$_POST['field']] == 'code') {
      if (!empty($this->file)) {
        $result = $this->file_put_post($this->plugins . $this->file, 'wyciwyg', false);
        echo ($result === true) ? 'Saved' : $result;
      } else {
        echo 'Error';
      }
      exit;
    }
    #-- Delete File --#
    if (isset($_GET['delete']) && $_GET['delete'] == 'file') {
      $eject = $page->url('delete', '', array('delete', 'file'));
      if (!empty($this->file)) {
        unlink($this->plugins . $this->file);
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
      }
      $page->eject($eject);
    }
    $page->title = $_GET['file'] . ' &raquo; ' . ucwords($_GET['plugins']) . ' Plugin';
    $values = array('file'=>$this->file);
    $values['code'] = file_get_contents($this->plugins . $this->file);
    $form->values($values);
    $file = '<a class="pull-left" href="' . $page->url() . '" title="Refresh Page">' . $bp->icon('refresh') . '</a> File';
    $form->validate('file', $file, 'required', 'You can change the file name and location here.  If the new file already exists elsewhere, then it will simply overwrite the old file.');
    $form->validate('code', 'Code', '', 'Click to edit.');
    if ($form->submitted() && empty($form->errors)) {
      $file = $this->plugin_filter($form->vars['file']);
      $ext = ($pos = strrpos($file, '.')) ? substr($file, $pos + 1) : false;
      if (!empty($file) && $file != $this->file && in_array($ext, array('php', 'txt', 'css', 'less', 'js'))) {
        if (!is_dir(dirname($this->plugins . $file))) mkdir(dirname($this->plugins . $file), 0755, true);
        copy($this->plugins . $this->file, $this->plugins . $file);
        unlink($this->plugins . $this->file);
        $form->eject = $page->url('add', $form->eject, 'file', $file);
      }
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $delete = $bp->button('link', $bp->icon('trash'), array('id'=>'delete', 'data-url'=>$page->url('add', '', 'delete', 'file'), 'title'=>'Delete File'));
    $edit = $bp->button('warning', 'Edit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'));
    $html .= $form->field('file', 'text', array('prepend'=>$delete, 'append'=>$edit));
    $ext = substr($_GET['file'], strrpos($_GET['file'], '.') + 1);
    $html .= $form->field('code', 'textarea', array('class'=>"wyciwyg noMarkup {$ext} input-sm", 'rows'=>5, 'spellcheck'=>'false'));
    $html .= $form->close();
    unset($form);
    $page->plugin('jQuery', 'code', '
      $("#delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this file?")) {
          window.location = url;
        }
        return false;
      });
    ');
    return $html;
  }
  
  private function plugins_create () {
    global $bp, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'create_plugins');
    $prompt = ($_GET['plugins'] == 'bootpress') ? 'BootPress' : 'Website';
    if (!empty($this->file)) {
      $form->values($prompt, substr($this->file, 0, strrpos($this->file, '/') + 1));
    } elseif (!empty($_GET['file'])) { // a directory
      $form->values($prompt, $_GET['file']);
    }
    $form->validate($prompt, $prompt, 'required', 'Enter the name of the file you would like to create.  Only php, txt, css, less, and js files can be managed here.  eg. New_Plugin/index.php');
    $form->validate('edit', 'Edit', '', 'Select a file that you would like to edit.');
    if ($form->submitted()) {
      $file = $this->plugin_filter($form->vars[$prompt]);
      $ext = ($pos = strrpos($file, '.')) ? substr($file, $pos + 1) : false;
      if (empty($file)) $form->errors[$prompt] = 'Ugh ... try again';
      if (!in_array($ext, array('php', 'txt', 'css', 'less', 'js'))) $form->errors[$prompt] = 'The file extension must be one of: php, txt, css, less, js';
      if (file_exists($this->plugins . $file)) $form->errors[$prompt] = 'This file already exists';
      if (empty($form->errors)) {
        if (!is_dir(dirname($this->plugins . $file))) mkdir(dirname($this->plugins . $file), 0755, true);
        file_put_contents($this->plugins . $file, '');
        $form->eject = $page->url('add', $form->eject, 'file', $file);
        $page->eject($form->eject);
      }
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $create = $bp->button('primary', 'Create', array('type'=>'submit', 'data-loading-text'=>'Submitting'));
    $html .= $form->field($prompt, 'text', array('prepend'=>'Plugin', 'append'=>$create));
    if (file_exists($this->plugins)) {
      $plugins = array();
      foreach (scandir($this->plugins) as $dir) if ($dir != '.' && $dir != '..' && is_dir($this->plugins . $dir . '/')) $plugins[$dir . '/'] = $dir;
      if (!empty($plugins)) {
        natcasesort($plugins);
        $preselect = (isset($_GET['file'])) ? $_GET['file'] : '';
        $html .= $form->field('edit', '<div id="selected" class="row">' . $this->plugin_options($plugins, $preselect) . '</div>');
      }
    }
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function selected () {
    global $page;
    if (isset($_POST['selected'])) {
      if (file_exists($this->plugins . $_POST['selected']) && is_dir($this->plugins . $_POST['selected'])) {
        echo $this->plugin_options($this->select($_POST['selected']));
      }
      exit;
    }
    $page->plugin('jQuery', 'code', '
      $("#selected").on("change", "select", function(){
        $(this).parent("div").nextAll("div").remove();
        var value = $(this).val();
        if (value.slice(-1) == "/") {
          $.post(window.location, {selected:value}, function(data){$("#selected div:last").after(data);}, "html");
        } else if (value != "") {
          window.location = "' . $page->url('delete', '', 'file') . '&file=" + value;
        }
      });
    ');
  }
  
  private function folder_files ($dir='') {
    if (empty($dir)) $dir = $this->folders;
    $empty = true;
    $folders = array();
    if (!file_exists($dir)) return array();
    foreach (scandir($dir) as $file) {
      if ($file != '.' && $file != '..') {
        if (is_dir($dir . $file)) {
          $folders = array_merge($folders, $this->folder_files($dir . $file . '/'));
          $empty = false;
        } elseif (preg_match('/\.(php)$/', $file)) {
          $folders[] = $dir . $file;
          $empty = false;
        }
      }
    }
    if ($empty) rmdir($dir);
    return $folders;
  }
  
  private function folder_filter ($file) {
    $file = preg_replace('/[^0-9a-z\-\/]/', '', strtolower($file)); // lowercase alphanumeric - /
    $file = preg_replace('/[\-\/](?=[\-\/])/', '', $file); // no doubled up punctuation
    $file = trim($file, '/'); // no trailing (or preceding) slashes
    return trim($file, '-'); // no trailing (or preceding) dashes
  }
  
  private function plugin_filter ($file) {
    $file = preg_replace('/[^0-9a-z._\-\/]/i', '', $file); // alphanumeric . _ - /
    $file = preg_replace('/[._\-\/](?=[._\-\/])/', '', $file); // no doubled up punctuation
    $folders = explode('/', trim($file, '/')); // no trailing (or preceding) slashes
    $file = array_pop($folders); // keep the last chunk as is
    foreach ($folders as $key => $value) $folders[$key] = str_replace('.', '-', $value); // for folder integrity
    return (!empty($folders)) ? implode('/', $folders) . '/' . $file : $file;
  }
  
  private function plugin_options ($select, $file='') {
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
  
  private function select ($dir) {
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
    return array_merge($dirs, $files);
  }
  
}

/* End of file Admin_php.php */
/* Location: ./application/libraries/Admin/drivers/Admin_php.php */