<?php

class BlogAdminSetup extends BlogAdmin {

  private $tags = array();
  
  public function view () {
    global $page;
    $html = '';
    $page->plugin('Form_Validation');
    if (empty($this->blog['name'])) return $this->admin($this->setup());
    $this->db->query('SELECT COUNT(b.blog_id), t.id, t.tag
                  FROM tagged AS b
                  INNER JOIN tags AS t ON b.tag_id = t.id
                  GROUP BY t.tag
                  ORDER BY t.tag ASC');
    while (list($count, $id, $tag) = $this->db->fetch('row')) {
      $this->tags[$id]['count'] = $count;
      $this->tags[$id]['name'] = $tag;
    }
    $url = $this->blog['url'] . 'admin/setup/';
    $page->plugin('Bootstrap', 'Navigation');
    $nav = new BootstrapNavigation;
    $pills = array();
    $pills['Authors'] = $page->url('add', $url, 'edit', 'authors');
    if (!empty($this->tags)) {
      $pills['Categories'] = $page->url('add', $url, 'edit', 'categories');
      $pills['Tags'] = $page->url('add', $url, 'edit', 'tags');
    }
    $html .= $nav->menu('pills', $pills, array('align'=>'horizontal', 'active'=>$page->url('delete', '', 'id')));
    $html .= '<br>';
    unset($nav);
    $edit = (isset($_GET['edit'])) ? $_GET['edit'] : '';
    switch ($edit) {
      case 'authors':
        $html .= $this->authors();
        break;
      case 'categories':
        $html .= $this->categories();
        break;
      case 'tags':
        $html .= $this->tags();
        break;
      default:
        $html .= $this->setup();
        break;
    }
    if (!empty($next)) $html .= $next;
    return $this->admin($html);
  }
  
  private function setup () {
    global $page;
    $html = '';
    $form = new Form('setup_blog');
    $form->required(array('name'), false);
    $form->info(array(
      'name' => 'The name of your blog.',
      'slogan' => 'An optional tagline.',
      'summary' => 'What your blog is all about.'
    ));
    $form->values(array('name'=>$this->blog['name'], 'slogan'=>$this->blog['slogan'], 'summary'=>$this->blog['summary']));
    $form->check(array('name'=>'', 'slogan'=>'', 'summary'=>''));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      if (empty($this->blog['name'])) $eject = $this->blog['url'] . 'admin/blog/'; // This will only happen once
      $this->db->settings('name', $vars['name']);
      $this->db->settings('slogan', $vars['slogan']);
      $this->db->settings('summary', $vars['summary']);
      $page->eject($eject);
    }
    $html .= $form->header();
    $html .= $form->field('text', 'name', 'Blog', array('maxlength'=>100));
    $html .= $form->field('text', 'slogan', 'Slogan', array('maxlength'=>100));
    $html .= $form->field('textarea', 'summary', 'Summary', array('rows'=>3));
    $html .= $form->buttons('Submit');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function authors () {
    global $page;
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
      $this->db->update('blog', array('author_id'=>0), 'author_id', $_GET['delete']);
      $this->db->delete('authors', 'id', $_GET['delete']);
      $page->eject($page->url('delete', '', array('delete', 'id')));
    }
    $edit = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $this->db->row('SELECT author, summary FROM authors WHERE id = ?', array($_GET['id'])) : false;
    $html = '';
    $form = new Form('setup_authors');
    $form->required(array('author'), false);
    $form->info(array(
      'author' => 'The authors name.',
      'summary' => 'Kudos you would like to extend.'
    ));
    if ($edit) $form->values($edit);
    $form->check(array('author'=>'an', 'summary'=>''));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      if ($edit) {
        $this->db->update('authors', $vars, 'id', $_GET['id']);
      } else {
        $this->db->insert('authors', $vars);
      }
      $page->eject($page->url('delete', $eject, 'id'));
    }
    $html .= $form->header();
    $html .= $form->field('text', 'author', 'Name', array('maxlength'=>100));
    $html .= $form->field('textarea', 'summary', 'Summary', array('rows'=>3));
    $html .= ($edit) ? $form->buttons('Edit') : $form->buttons('Submit');
    $html .= $form->close();
    unset($form);
    $this->db->query('SELECT id, author, summary FROM authors ORDER BY author ASC');
    while (list($id, $author, $summary) = $this->db->fetch('row')) {
    
      $buttons = '<span class="btn-group btn-sm pull-right">';
        $buttons .= '<a href="' . $page->url('add', '', 'id', $id) . '" class="btn btn-default" title="Edit"><i class="glyphicon glyphicon-pencil"></i></a>';
        $buttons .= '<button class="delete btn btn-default" data-url="' . $page->url('add', '', 'delete', $id) . '" title="Delete"><i class="glyphicon glyphicon-trash"></i></button>';
        $buttons .= '<a href="' . $this->seo_url('authors', $author) . '" class="btn btn-default" title="View"><i class="glyphicon glyphicon-share-alt"></i></a>';
      $buttons .= '</span>';
      
      $html .= '<p><strong>' . $author . '</strong>' . $buttons . '<br />' . $summary . '</p>';
    }
    $page->plugin('jQuery', array('code'=>'
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this author?")) {
          window.location = url;
        }
      });
    '));
    return $html;
  }
  
  private function categories () {
    global $page;
    $html = '';
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
      $this->db->delete('categories', 'id', $_GET['delete']);
      $page->eject($page->url('delete', '', array('delete', 'id', 'submitted')));
    }
    $edit = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $this->db->row('SELECT category, tags FROM categories WHERE id = ?', array($_GET['id'])) : false;
    $form = new Form('setup_categories');
    $form->required(array('category', 'tags'), false);
    $form->info(array(
      'category' => 'The categories name.',
      'tags' => 'The tags that will be used to determine what posts and pages should be listed under this category.'
    ));
    if ($edit) {
      $edit['tags'] = explode(',', $edit['tags']);
      $form->values($edit);
    }
    $form->check(array('category'=>'an', 'tags'=>'int'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $vars['tags'] = implode(',', $vars['tags']);
      if ($edit) {
        $this->db->update('categories', $vars, 'id', $_GET['id']);
      } else {
        $this->db->insert('categories', $vars);
      }
      $page->eject($page->url('delete', $eject, 'id'));
    }
    $html .= $form->header();
    $html .= $form->field('text', 'category', 'Category', array('maxlength'=>100));
    if (empty($this->tags)) {
      $html .= $form->label('multiselect', 'tags', 'Tags', '<p class="help-block">You have no tags from which to select from.</p>');
    } else {
      $tags = array();
      foreach ($this->tags as $id => $tag) $tags[$id] = '(' . $tag['count'] . ') ' . $tag['name'];
      $html .= $form->field('multiselect', 'tags', 'Tags', $tags);
    }
    $html .= ($edit) ? $form->buttons('Edit') : $form->buttons('Submit');
    $html .= $form->close();
    unset($form);
    $this->db->query('SELECT id, category, tags FROM categories ORDER BY category ASC');
    $rows = $this->db->fetch('row', 'all');
    foreach ($rows as $row) {
      list($id, $category, $tags) = $row;
      $buttons = '<span class="btn-group btn-sm pull-right">';
        $buttons .= '<a href="' . $page->url('add', '', 'id', $id) . '" class="btn btn-default" title="Edit"><i class="glyphicon glyphicon-pencil"></i></a>';
        $buttons .= '<button class="delete btn btn-default" data-url="' . $page->url('add', '', 'delete', $id) . '" title="Delete"><i class="glyphicon glyphicon-trash"></i></button>';
        $buttons .= '<a href="' . $this->seo_url($category) . '" class="btn btn-default" title="View"><i class="glyphicon glyphicon-share-alt"></i></a>';
      $buttons .= '</span>';
      $html .= '<p><strong>' . $category . '</strong>' . $buttons . '<br />' . implode(', ', $this->tagged($tags)) . '</p>';
    }
    $page->plugin('jQuery', array('code'=>'
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this category?")) {
          window.location = url;
        }
      });
    '));
    return $html;
  }
  
  private function tags () {
    global $page;
    $html = '';
    $form = new Form('setup_tags');
    $form->check(array('tags'=>'an'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      foreach ($vars['tags'] as $id => $tag) {
        if (!empty($tag) && isset($this->tags[$id])) {
          $this->db->query('UPDATE tags SET tag = ? WHERE id = ? AND tag = ?', array($tag, $id, $tag));
        }
      }
      $page->eject($eject);
    }
    $html .= $form->header();
    $html .= '<p class="text-center">This form is to help you standardize the capitalization of your tags.<br>The only way to delete or change them is by editing the applicable pages and posts.<br>It is not necessary to re-enter every value.  Only the ones you wish to update.</p>';
    foreach ($this->tags as $id => $tag) {
      $html .= $form->field('multitext', 'tags', '<a href="' . $this->seo_url('tags', $tag['name']) . '">' . $tag['name'] . '</a>', array('prepend'=>'<span class="badge">' . $tag['count'] . '</span>'), $id);
    }
    $html .= $form->buttons('Edit');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function seo_url () {
    $paths = func_get_args();
    foreach ($paths as $key => $value) $paths[$key] = str_replace(' ', '-', strtolower($value));
    return $this->blog['url'] . implode('/', $paths) . '/';
  }
  
}

?>