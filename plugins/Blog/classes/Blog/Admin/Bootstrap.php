<?php

class BlogAdminBootstrap extends BlogAdmin {

  public function view () {
    global $page, $bp;
    if (isset($_GET['preview']) && $_GET['preview'] == 'changes') {
      return $this->layout($page->plugin('Bootstrap', 'preview'), 'admin');
    }
    $default = file_get_contents($this->uri . 'files/variables.less');
    if (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
      switch ($_POST['field']) {
        case 'variables':
          $variables = $this->code('wyciwyg');
          if (empty($variables)) $variables = $default;
          file_put_contents($this->dir . 'temp.less', $variables);
          $page->plugin('Bootstrap', 'load', $this->dir . 'temp.less');
          rename($this->dir . 'temp.less', $this->dir . 'variables.less');
        break;
        case 'custom':
          $custom = str_replace('{$blog[\'img\']}', $this->blog['img'], $this->code('wyciwyg'));
          $this->save_resources_used('css', $custom);
          if (!empty($custom)) {
            file_put_contents($this->dir . 'custom.css', $custom);
          } elseif (file_exists($this->dir . 'custom.css')) {
            unlink($this->dir . 'custom.css');
          }
        break;
      }
      echo 'Saved';
      exit;
    }
    $variables = (file_exists($this->dir . 'variables.less')) ? file_get_contents($this->dir . 'variables.less') : $default;
    $custom = (file_exists($this->dir . 'custom.css')) ? file_get_contents($this->dir . 'custom.css') : '';
    $html = '';
    $html .= $bp->pills(array('Preview Bootstrap Theme(s)' => $page->url('add', '', 'preview', 'changes'))) . '<br>';
    $page->plugin('Form_Validation');
    $form = new Form('css_form');
    $info = array();
    if ($default == $variables) {
      $info['variables'] = 'This is the default variables.less file as utilized by the Twitter Bootstrap Framework.  You may customize any of the variables to make it yours.';
    } else {
      $info['variables'] = 'This is a custom variables.less file that is ran through Twitter Bootstrap to give your website the look and feel you desire.';
    }
    $info['custom'] = 'You can place any additional css rules here to put on those finishing touches to your layout.';
    $form->info($info);
    $form->values(array('variables'=>$variables, 'custom'=>$custom));
    $form->check(array('variables'=>'', 'custom'=>''));
    list($vars, $errors, $eject) = $form->validate();
    $html .= $form->header();
    $html .= $form->field('textarea', 'variables', 'Variables:', array('class'=>'wyciwyg noMarkup less input-sm', 'rows'=>5, 'spellcheck'=>'false'));
    $html .= $form->field('textarea', 'custom', 'Custom:', array('class'=>'wyciwyg noMarkup less input-sm', 'rows'=>5, 'spellcheck'=>'false'));
    $html .= $form->close();
    unset($form);
    return $this->admin($html);
  }
  
}

?>