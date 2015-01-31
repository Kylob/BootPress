<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_plugins extends CI_Driver {
  
  private $plugins;
  
  public function view () {
    global $bp, $ci, $page;
    $this->plugins = BASE_URI . 'plugins/';
    if (!is_dir($this->plugins)) mkdir($this->plugins, 0755, true);
    $plugin = trim($ci->input->get('plugin'), '/');
    if (empty($plugin) || !is_dir($this->plugins . $plugin)) $plugin = false;
    $media = ($plugin) ? $ci->admin->files->view('plugins', $this->plugins . $plugin) : '';
    if ($ci->input->get('image')) return $this->display($media);
    if ($ci->input->get('delete') == 'plugin') {
      if ($plugin) {
        list($dirs, $files) = $ci->blog->folder($this->plugins . $plugin, true);
        foreach ($files as $file) unlink($this->plugins . $plugin . $file);
        foreach ($dirs as $dir) rmdir($this->plugins . $plugin . $dir);
        rmdir($this->plugins . $plugin);
      }
      $page->eject($page->url('delete', '', '?'));
    }
    $form = $page->plugin('Form', 'name', 'admin_plugins');
    list($dirs) = $ci->blog->folder($this->plugins, false, false);
    $form->menu('plugin', array_combine($dirs, $dirs), '&nbsp;');
    if ($plugin) $form->values(array('plugin'=>$plugin, 'edit'=>$plugin));
    $form->validate('plugin', 'Plugin', '', 'Select a plugin that you would like to edit.');
    if ($plugin) {
      $form->validate('edit', 'Save As', 'required', 'Enter the new name you would like for this plugin.  If it already exists then nothing will happen.');
      $form->validate('index', 'index.php', '', 'This page should $export the desired code, array, class, or whatever it is that your plugin is designed to do.');
      $form->values($ci->admin->files->save(array('index' => $this->plugins . $plugin . '/index.php')));
    } else {
      $form->validate('edit', 'Create', 'required', 'Enter the name of the plugin that you would like to create.  If it already exists then nothing will happen.');
    }
    #-- Submitted --#
    if ($form->submitted() && empty($form->errors)) {
      if (($folder = $this->plugin_filter($form->vars['edit'])) && !empty($folder)) {
        if ($plugin) {
          $newname = $this->plugins . $folder;
          if (!is_dir($newname)) rename($this->plugins . $plugin, $newname);
        } elseif (!is_dir($this->plugins . $folder)) {
          mkdir($this->plugins . $folder, 0755, true);
          file_put_contents($this->plugins . $folder . '/index.php', '');
        }
        $form->eject = $page->url('add', $form->eject, 'plugin', $folder);
      }
      $page->eject($form->eject);
    }
    $html = '';
    $delete = ($plugin) ? $bp->button('sm danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this plugin', 'style'=>'margin-left:20px;')) : '';
    $docs = $bp->button('sm info pull-right', 'Documentation ' . $bp->icon('new-window'), array('href'=>'http://bootpress.org/getting-started#plugins', 'target'=>'_blank'));
    $html .= '<div class="page-header"><p class="lead">' . $bp->icon('plug', 'fa') . ' ' . ($plugin ? 'Edit' : 'Select') . ' ' . $delete . '&nbsp;' . $docs . '</p></div>';
    $html .= $form->header();
    $html .= $form->field('plugin', 'select');
    $html .= $form->field('edit', 'text', array('append'=>$bp->button('primary', 'Submit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))));
    if ($plugin) $html .= $form->field('index', 'textarea', array('class'=>'wyciwyg php input-sm', 'data-file'=>'index.php'));
    $html .= $form->close();
    $page->plugin('jQuery', 'code', '
      $(".delete").click(function(){
        if (confirm("Are you sure you would like to delete this plugin?")) {
          window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'plugin')) . '";
        }
      });
      $("#' . $form->id('plugin') . '").on("change", function(){
        if ($(this).val() != "") window.location = "' . $page->url('delete', '', '?') . '?plugin=" + $(this).val();
      });
    ');
    unset($form);
    return $this->display($html . $media);
  }
  
  private function plugin_filter ($file) {
    $file = preg_replace('/[^0-9a-z_\-]/i', '', $file); // alphanumeric _ -
    $file = preg_replace('/[_\-](?=[_\-])/', '', $file); // no doubled up punctuation
    return trim($file, '_-');
  }
  
}

/* End of file Admin_plugins.php */
/* Location: ./application/libraries/Admin/drivers/Admin_plugins.php */