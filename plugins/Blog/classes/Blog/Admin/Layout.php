<?php

class BlogAdminLayout extends BlogAdmin {

  public function view () {
    global $page;
    $fields = array('formHeader', 'formContent', 'formSidebar', 'formFooter', 'formLayout'); // so we don't get html #id collissions
    if (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
      if ($_POST['field'] == 'php' && is_admin(1)) {
        $result = $this->file_put_post($this->dir . 'layout.php', 'wyciwyg');
        if ($result === true) {
          $content = (file_exists($this->dir . 'layout.php')) ? $page->outreach($this->dir . 'layout.php', array('blog'=>$this->blog)) : '';
          $this->save_resources_used('php', $content);
          echo 'Saved';
        } else {
          echo $result;
        }
        exit;
      } elseif (in_array($_POST['field'], $fields)) {
        $name = strtolower(substr($_POST['field'], 4));
        $template = $this->code('wyciwyg');
        $this->save_resources_used($name, $template);
        $values = array();
        $values[] = array($name, $template);
        $values[] = array('updated', time());
        $this->db->statement('INSERT OR REPLACE INTO templates (name, template) VALUES (?, ?)', $values, 'insert');
        echo 'Saved';
        exit;
      }
      echo 'Error';
      exit;
    }
    $html = '';
    $page->plugin('Form_Validation');
    $form = new Form('templates_form');
    $values = array();
    foreach ($fields as $field) $values[$field] = $this->templates(strtolower(substr($field, 4)));
    $values['php'] = (file_exists($this->dir . 'layout.php')) ? addslashes(htmlspecialchars(file_get_contents($this->dir . 'layout.php'))) : '';
    $info = array();
    $info['formHeader'] = 'This will be delivered to your layout as a {$header} variable.';
    if ($values['formContent'] == file_get_contents($this->uri . 'files/content.tpl')) {
      $info['formContent'] = 'This is the default template that will be used to create the {$content} of your blog. You can customize it to suit your taste.';
    } else {
      $info['formContent'] = 'This is a custom template that is used to create the {$content} of your blog.  You can always revert to the default by erasing everything, click save, then refresh the page.';
    }
    $info['formSidebar'] = 'This will be delivered to your layout as a {$sidebar} variable.';
    $info['formFooter'] = 'This will be delivered to your layout as a {$footer} variable.';
    if ($values['formLayout'] == file_get_contents($this->uri . 'files/layout.tpl')) {
      $info['formLayout'] = 'This is the default layout for all of your web pages.';
    } else {
      $info['formLayout'] = 'This is a custom layout that is used for all of your web pages.  To revert back to the default: delete, save, and refresh.';
    }
    $info['php'] = 'The php code that you $export will be available to everything above (except content) as a $php variable.  This code is processed after the content is delivered, and only if this layout is used.  The only variable imported is \'img\'.';
    $form->info($info);
    $form->values($values);
    list($vars, $errors, $eject) = $form->validate(); // we're only doing this for the tooltips this method activates
    $html .= $form->header();
    foreach (array_keys($values) as $name) {
      if ($name == 'php') {
        if (is_admin(1)) {
          $html .= $form->field('textarea', $name, 'PHP', array('class'=>'wyciwyg noMarkup php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
        } elseif (file_exists($this->dir . 'layout.php')) {
          $html .= $form->field('textarea', $name, 'PHP', array('class'=>'wyciwyg noMarkup readOnly php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
        }
      } else {
        $html .= $form->field('textarea', $name, substr($name, 4), array('class'=>'wyciwyg html input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      }
    }
    $html .= $form->close();
    unset($form);
    return $this->admin($html);
  }
  
}

?>