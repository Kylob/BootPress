<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_themes extends CI_Driver {

  private $dir;
  private $docs;
  private $theme = false;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $html = '';
    $this->dir = BASE_URI . 'themes/';
    $this->docs = $bp->button('md link', 'Documentation ' . $bp->icon('new-window'), array('href'=>'https://www.bootpress.org/docs/themes/', 'target'=>'_blank'));
    if (isset($params['theme'])) {
      $this->theme = $this->mktheme($params['theme']);
      $page->theme = $this->theme; // to double check the ini file
      $html .= $this->theme();
    } else {
      $html .= $this->create();
    }
    return $this->display($html);
  }
  
  public function update () {
    global $ci, $page;
    $ci->sitemap->suspend_caching(0);
  }
  
  private function mktheme ($theme, $unzip=null) {
    global $ci, $page;
    $theme = $page->seo($theme);
    if (!is_dir($this->dir . $theme)) mkdir($this->dir . $theme, 0755, true);
    if (!is_file($this->dir . $theme . '/index.tpl')) {
      if ($unzip && is_file($unzip)) {
        $ci->load->library('unzip');
        $ci->unzip->files($unzip, $this->dir . $theme, 0755);
        $ci->unzip->extract('tpl|js|css|less|scss|ttf|otf|svg|eot|woff|woff2|swf|jpg|jpeg|gif|png|ico', $ci->unzip->common_dir());
        $ci->unzip->close();
      } else {
        list($dirs, $files) = $ci->blog->folder($ci->blog->templates, 'recursive');
        foreach ($dirs as $dir) mkdir($this->dir . $theme . '/' . $dir, 0755, true);
        foreach ($files as $file) copy($ci->blog->templates . $file, $this->dir . $theme . '/' . $file);
      }
    }
    foreach (array('setup.ini', 'archives.tpl', 'authors.tpl', 'blog.tpl', 'default.tpl', 'feed.tpl', 'listings.tpl', 'tags.tpl') as $template) {
      if (!is_file($this->dir . $theme . '/' . $template)) {
        copy($ci->blog->templates . $template, $this->dir . $theme . '/' . $template);
      }
    }
    return $theme;
  }
  
  private function theme () {
    global $bp, $ci, $page;
    if ($ci->input->get('delete') == 'theme') {
      if (is_dir($this->dir . $this->theme)) {
        list($dirs, $files) = $ci->blog->folder($this->dir . $this->theme, 'recursive');
        arsort($dirs);
        foreach ($files as $file) unlink($this->dir . $this->theme . $file);
        foreach ($dirs as $dir) rmdir($this->dir . $this->theme . $dir);
        rmdir($this->dir . $this->theme);
      }
      $page->eject($page->url('admin', 'themes'));
    }
    if (($preview = $ci->input->post('preview')) && $ci->input->is_ajax_request()) {
      if ($preview == 'true') {
        $ci->sitemap->suspend_caching(60);
        $ci->session->preview_layout = $this->theme;
        $ci->session->mark_as_temp('preview_layout', 3000);
      } else { // $preview == 'false'
        unset($_SESSION['preview_layout']);
      }
      exit;
    } elseif ($ci->session->preview_layout) {
      $ci->sitemap->suspend_caching(60);
      $ci->session->preview_layout = $this->theme;
      $ci->session->mark_as_temp('preview_layout', 3000);
    }
    $media = $ci->admin->files->view('themes', $this->dir . $this->theme);
    if ($ci->input->get('image')) {
      return $this->box('default', array(
        'head with-border' => $bp->icon('image', 'fa') . ' Image',
        'body' => $media
      ));
    }
    $form = $page->plugin('Form', 'name', 'admin_theme_manage');
    $form->values($ci->admin->files->save(array(
      'setup' => array($this->dir . $this->theme . '/setup.ini', $ci->blog->templates . 'setup.ini'),
      'index' => array($this->dir . $this->theme . '/index.tpl', $ci->blog->templates . 'index.tpl')
    ), array('setup', 'index'), array($this, 'update')));
    $form->values(array(
      'preview' => ($ci->session->preview_layout) ? 'Y' : 'N',
      'action' => 'copy'
    ));
    $form->menu('preview', array('Y'=>'Preview the selected theme'));
    $form->menu('action', array(
      'copy' => '<b>Copy</b> will make a duplicate of this theme if it does not already exist',
      'rename' => '<b>Rename</b> will change the name of this theme as long as it does not already exist',
      'swap' => '<b>Swap</b> will exchange this theme with the one you want to save as long as it actually exists'
    ));
    $form->validate(
      array('preview', '', 'YN'),
      array('save', 'Save As', 'required', 'Enter the name of the theme for which you would like to Copy, Rename, or Swap.'),
      array('action', '', 'required|inarray[menu]'),
      array('setup', 'setup.ini', '', 'This file establishes the desired {$bp} class and {$blog} array of basic information.'), 
      array('index', 'index.tpl', '', 'This is the main theme file that receives the {$content} from your templates below.')
    );
    if ($form->submitted() && empty($form->errors)) {
      if (!empty($form->vars['save'])) {
        $new_theme = $page->seo($form->vars['save']);
        $exists = (is_dir($this->dir . $new_theme)) ? true : false;
        switch ($form->vars['action']) {
          case 'copy':
            if (!$exists) {
              mkdir($this->dir . $new_theme, 0755, true);
              list($dirs, $files) = $ci->blog->folder($this->dir . $this->theme, 'recursive');
              foreach ($dirs as $dir) mkdir($this->dir . $new_theme . $dir, 0755, true);
              foreach ($files as $file) copy($this->dir . $this->theme . $file, $this->dir . $new_theme . $file);
            } else {
              $form->errors['action'] = 'The theme name you are trying to <b>Save As</b> a <b>Copy</b> already exists.';
            }
            break;
          case 'rename':
            if (!$exists) {
              rename($this->dir . $this->theme, $this->dir . $new_theme);
            } else {
              $form->errors['action'] = 'You cannot <b>Rename</b> and <b>Save As</b> a theme that already exists.';
            }
            break;
          case 'swap':
            if ($exists) {
              $temp = md5($this->dir . $this->theme) . microtime();
              rename($this->dir . $this->theme, $this->dir . $temp);
              rename($this->dir . $new_theme, $this->dir . $this->theme);
              rename($this->dir . $temp, $this->dir . $new_theme);
            } else {
              $form->errors['action'] = 'The <b>Save As</b> theme you are <b>Swap</b>ping with does not exist.';
            }
        }
        if (empty($form->errors)) $page->eject($page->url('admin', 'themes', $new_theme));
      } else { // $form->vars['save'] is empty
        $this->update();
        $page->eject($form->eject);
      }
    }
    $page->plugin('jQuery', 'code', '
      $("input[name=preview]").change(function(){
        var checked = $(this).is(":checked") ? "true" : "false";
        $.post(location.href, {preview:checked});
      });
      $(".delete").click(function(){
        if (confirm("Are you sure you would like to delete this theme?")) {
          window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'theme')) . '";
        }
      });
    ');
    return $this->box('default', array(
      'head with-border' => array($bp->icon('desktop', 'fa') . ' Themes', $this->docs),
      'body' => implode('', array(
        $form->header(),
        $form->field(false,
          str_replace('class="checkbox"', 'class="checkbox pull-left"', $form->field('preview', 'checkbox', array('label'=>false))) .
          $bp->button('danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this theme'))
        ),
        $this->select(),
        $form->field('save', 'text'),
        $form->field('action', 'radio'),
        $form->submit(),
        $form->field('setup', 'textarea', array('class'=>'wyciwyg ini input-sm', 'rows'=>8, 'data-file'=>'setup.ini')),
        $form->field('index', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'rows'=>8, 'data-file'=>'index.tpl')),
        $form->close(),
        $media
      ))
    ));
  }
  
  private function create () {
    global $bp, $ci, $page;
    $form = $page->plugin('Form', 'name', 'admin_theme_create');
    $form->upload('upload', 'Upload', 'zip', array(
      'info' => 'Submit a zipped file to extract for your theme.',
      'filesize' => 10,
      'limit' => 1
    ));
    $form->validate('create', 'Create', 'required', 'Enter the name of the theme you would like to create.');
    if ($form->submitted() && empty($form->errors)) {
      $theme = $form->vars['create'];
      $unzip = (!empty($form->vars['upload'])) ? key($form->vars['upload']) : null;
      $page->eject($page->url('admin', 'themes', $this->mktheme($theme, $unzip)));
    }
    return $this->box('default', array(
      'head with-border' => array($bp->icon('desktop', 'fa') . ' Themes', $this->docs),
      'body' => implode('', array(
        $form->header(),
        $this->select(),
        $form->field('create', 'text'),
        $form->field('upload', 'file'),
        $form->submit(),
        $form->close()
      ))
    ));
  }
  
  private function select () {
    global $ci, $page;
    $form = $page->plugin('Form', 'name', 'admin_theme_select');
    $themes = array(
      $page->url('admin', 'themes') => '',
      $page->url('admin', 'themes', 'default') => 'default'
    );
    list($dirs) = $ci->blog->folder($this->dir, false, false);
    foreach ($dirs as $theme) $themes[$page->url('admin', 'themes', $theme)] = $theme;
    $form->menu('themes', $themes);
    $form->values('themes', $page->url('admin', 'themes', $this->theme));
    $form->validate('themes', ($this->theme ? 'Edit' : 'Select'), '', 'Select the theme you would like to edit.');
    $page->plugin('jQuery', 'code', '$("#' . $form->id('themes') . '").change(function(){ window.location = $(this).val(); });');
    return $form->field('themes', 'select');
  }
  
}

/* End of file Admin_themes.php */
/* Location: ./application/libraries/Admin/drivers/Admin_themes.php */