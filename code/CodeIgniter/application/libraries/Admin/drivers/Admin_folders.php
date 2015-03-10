<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_folders extends CI_Driver {

  private $dir;
  
  public function view () {
    global $bp, $ci, $page;
    $html = '';
    $media = '';
    $this->dir = BASE_URI . 'folders/';
    if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    $form = $page->plugin('Form', 'name', 'admin_folders');
    $edit = $ci->input->get('folder');
    if ($edit && is_dir($this->dir . $edit)) {
      $form->values($ci->admin->files->save(array('index'=>$this->dir . $edit . '/index.php')));
      $media = $ci->admin->files->view('folders', $this->dir . $edit);
      if ($ci->input->get('image')) {
        return $this->display($this->box('default', array(
          'head with-border' => $bp->icon('image', 'fa') . ' Image',
          'body' => $media
        )));
      }
      if ($ci->input->get('delete') == 'folder') {
        list($dirs, $files) = $ci->blog->folder($this->dir . $edit);
        foreach ($files as $file) unlink($this->dir . $edit . $file);
        if (empty($dirs)) rmdir($this->dir . $edit);
        $page->eject($page->url('delete', '', '?'));
      }
      $form->values('uri', $edit);
    } else {
      if ($ci->input->get('delete')) $page->eject($page->url('delete', '', '?'));
      $edit = false;
    }
    $folders = array();
    list($dirs) = $ci->blog->folder($this->dir, 'recursive', false);
    foreach ($dirs as $folder) if (is_file($this->dir . $folder . '/index.php')) $folders[$folder] = $folder;
    if (!empty($folders)) $form->menu('edit', $folders, $edit ? null : '&nbsp;');
    if ($edit) $form->values(array('folder'=>$edit, 'edit'=>$edit));
    $form->validate(
      array('folder', ($edit ? 'Save As' : 'Create'), 'required', ($edit ? 'Use only lowercase letters, dashes (-), and slashes (/).' : 'Use only lowercase letters, dashes (-), and slashes (/).  The folder you create here will be directly accessible at: ' . BASE_URL . '[folder]/...  You, of course, will have to deal with the dot dot dot\'s.  Alternatively, you can create any url rule structure that you like in the .htaccess file, and direct it to the main index.php file with the additional parameter: ?page=[folder]')),
      array('edit', ($edit ? 'Edit' : 'Select'), '', 'Select a folder that you would like to edit.'),
      array('index', 'index.php', '', 'This is the main file where you can manage the content of your folder.')
    );
    #-- Submitted --#
    if ($form->submitted() && empty($form->errors)) {
      $folder = $page->seo($form->vars['folder'], 'slashes');
      if (!empty($folder)) {
        if ($edit) { // renaming
          if ($edit != $folder) {
            if (is_file($this->dir . $folder . '/index.php')) {
              $form->errors['folder'] = 'Sorry, this folder has already been taken.';
            } else {
              $path = $this->dir . $edit . '/';
              $rename = $this->dir . $folder . '/';
              list($dirs, $files) = $ci->blog->folder($path);
              if (!is_dir($rename)) mkdir($rename, 0755, true);
              foreach ($files as $file) rename($path . $file, $rename . $file);
              if (empty($dirs) && strpos($rename, $path) === false) rmdir($path);
              $form->eject = $page->url('add', $form->eject, 'folder', $folder);
            }
          }
        } else { // creating
          if (!is_dir($this->dir . $folder)) mkdir($this->dir . $folder, 0755, true);
          if (!is_file($this->dir . $folder . '/index.php')) file_put_contents($this->dir . $folder . '/index.php', '');
          $form->eject = $page->url('add', $form->eject, 'folder', $folder);
        }
      }
      if (empty($form->errors)) $page->eject($form->eject);
    }
    $html .= $form->header();
    if ($edit) {
      $delete = $bp->button('sm danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this folder', 'style'=>'margin-left:20px;'));
      $html .= '<p class="lead"><a href="' . BASE_URL . $edit . '" target="_blank">' . BASE_URL . $edit . ' ' . $bp->icon('new-window') . '</a> ' . $delete . '</p><br>';
    }
    $html .= (!empty($folders)) ? $form->field('edit', 'select') : '';
    $html .= $form->field('folder', 'text', array(
      'prepend' => BASE_URL,
      'append' => array('/', $bp->button('primary', 'Submit', array('type'=>'submit', 'data-loading-text'=>'Submitting...')))
    ));
    if ($edit) $html .= $form->field('index', 'textarea', array('class'=>'wyciwyg php input-sm', 'data-file'=>'index.php'));
    $html .= $form->close();
    $page->plugin('jQuery', 'code', '
      $(".delete").click(function(){
        if (confirm("Are you sure you would like to delete this folder?")) {
          window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'folder')) . '";
        }
      });
      $("#' . $form->id('edit') . '").change(function(){
        window.location = "' . $page->url('delete', '', '?') . '?folder=" + $(this).val();
      });
    ');
    unset($form);
    return $this->display($this->box('default', array(
      'head with-border' => array(
        $bp->icon('folder', 'fa') . ' Folders',
        $bp->button('md link', 'Documentation ' . $bp->icon('new-window'), array('href'=>'http://bootpress.org/getting-started#folders', 'target'=>'_blank'))
      ),
      'body' => $html . $media
    )));
  }
  
}

/* End of file Admin_folders.php */
/* Location: ./application/libraries/Admin/drivers/Admin_folders.php */