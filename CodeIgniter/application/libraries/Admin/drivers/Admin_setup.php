<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_setup extends CI_Driver {

  private $tags = array();
  
  public function view () {
    global $page, $bp;
    $html = '';
    if ($this->blog->get('name') == '') return $this->admin($this->setup());
    $this->blog->db->query('SELECT COUNT(b.blog_id), t.id, t.url, t.tag
                            FROM tagged AS b
                            INNER JOIN tags AS t ON b.tag_id = t.id
                            GROUP BY t.id
                            ORDER BY t.url ASC');
    while (list($count, $id, $url, $tag) = $this->blog->db->fetch('row')) {
      $this->tags[$id]['count'] = $count;
      $this->tags[$id]['name'] = $tag;
      $this->tags[$id]['url'] = $url;
    }
    $url = BASE_URL . ADMIN . '/setup';
    $links = array();
    $links['Authors'] = $page->url('add', $url, 'edit', 'authors');
    if (!empty($this->tags)) {
      $links['Categories'] = $page->url('add', $url, 'edit', 'categories');
      $links['Tags'] = $page->url('add', $url, 'edit', 'tags');
    }
    $html .= $bp->pills($links, array('align'=>'horizontal', 'active'=>$page->url('delete', '', 'id')));
    $html .= '<br>';
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
    global $ci, $page;
    $html = '';
    $edit = ($this->blog->get('name') == '') ? false : true;
    $form = $page->plugin('Form', 'name', 'setup_blog');
    $form->values(array('name'=>$this->blog->get('name'), 'slogan'=>$this->blog->get('slogan'), 'summary'=>$this->blog->get('summary')));
    $form->validate('name', 'Blog', 'required', 'The name of your blog.');
    $form->validate('slogan', 'Slogan', '', 'An optional tagline.');
    $form->validate('summary', 'Summary', '', 'What your blog is all about.');
    if ($form->submitted() && empty($form->errors)) {
      if ($this->blog->get('name') == '') $form->eject = BASE_URL . ADMIN . '/pages'; // This will only happen once
      $this->blog->db->settings('name', $form->vars['name']);
      $this->blog->db->settings('slogan', $form->vars['slogan']);
      $this->blog->db->settings('summary', $form->vars['summary']);
      $page->eject($form->eject);
    }
    if ($edit) $html .= $this->blog->thumbs->form('blog', 0);
    $html .= $form->header();
    $html .= $form->label_field('name', 'text', array('maxlength'=>100));
    $html .= $form->label_field('slogan', 'text', array('maxlength'=>100));
    $html .= $form->label_field('summary', 'textarea', array('rows'=>3));
    $html .= $form->submit($edit ? 'Edit' : 'Submit');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function authors () {
    global $bp, $page;
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
      $this->blog->db->update('blog', 'author_id', array($_GET['delete'] => array('author_id' => 0)));
      $this->blog->db->delete('authors', 'id', $_GET['delete']);
      $page->eject($page->url('delete', '', array('delete', 'id')));
    }
    $edit = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $this->blog->db->row('SELECT author, summary FROM authors WHERE id = ?', array($_GET['id'])) : false;
    $html = '';
    $form = $page->plugin('Form', 'name', 'setup_authors');
    if ($edit) $form->values($edit);
    $form->validate('author', 'Author', 'required', 'The authors name.');
    $form->validate('summary', 'Summary', '', 'Kudos you would like to extend.');
    if ($form->submitted() && empty($form->errors)) {
      $form->vars['url'] = $page->seo($form->vars['author']);
      if ($edit) {
        $this->blog->db->update('authors', 'id', array($_GET['id'] => $form->vars));
        $form->eject = $page->url('delete', $form->eject, 'id');
      } else {
        $id = $this->blog->db->insert('authors', $form->vars);
        $form->eject = $page->url('add', $form->eject, 'id', $id);
      }
      $page->eject($form->eject);
    }
    if ($edit) $html .= $this->blog->thumbs->form('authors', $_GET['id']);
    $html .= $form->header();
    $html .= $form->label_field('author', 'text', array('maxlength'=>100));
    $html .= $form->label_field('summary', 'textarea', array('rows'=>3));
    $html .= $form->submit($edit ? 'Edit' : 'Submit');
    $html .= $form->close();
    unset($form);
    $this->blog->db->query('SELECT id, url, author, summary FROM authors ORDER BY url ASC');
    while (list($id, $url, $author, $summary) = $this->blog->db->fetch('row')) {
      $thumb = $bp->img($this->blog->thumbs->url('authors', $id), 'width="75" class="pull-right" style="margin-left:10px;"');
      $html .= $bp->media(array(
        $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'id', $id))) . $thumb,
        '<h4><a href="' . BASE_URL . 'authors/' . $url . '/">' . $author . '</a></h4><p>' . $summary . '</p>',
        $bp->button('link delete', $bp->icon('trash'), array('data-url'=>$page->url('add', '', 'delete', $id), 'title'=>'Delete'))
      ));
    }
    $page->plugin('jQuery', 'code', '
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this author?")) {
          window.location = url;
        }
      });
    ');
    return $html;
  }
  
  private function categories () {
    global $bp, $page;
    $html = '';
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
      $this->blog->db->delete('categories', 'id', $_GET['delete']);
      $page->eject($page->url('delete', '', array('delete', 'id', 'submitted')));
    }
    $form = $page->plugin('Form', 'name', 'setup_categories');
    $edit = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $this->blog->db->row('SELECT category, tags FROM categories WHERE id = ?', array($_GET['id'])) : false;
    if ($edit) {
      $edit['tags[]'] = explode(',', $edit['tags']);
      $form->values($edit);
    }
    $tags = array();
    foreach ($this->tags as $id => $tag) $tags[$id] = '(' . $tag['count'] . ') ' . $tag['name'];
    $form->menu('tags[]', $tags);
    $form->validate('category', 'Category', 'required', 'The categories name.');
    $form->validate('tags[]', 'Tags', 'required|inarray[menu]', 'The tags that will be used to determine what posts and pages should be listed under this category.');
    if ($form->submitted() && empty($form->errors)) {
      $form->vars['url'] = $page->seo($form->vars['category']);
      $form->vars['tags'] = implode(',', $form->vars['tags']);
      if ($edit) {
        $this->blog->db->update('categories', 'id', array($_GET['id'] => $form->vars));
      } else {
        $this->blog->db->insert('categories', $form->vars);
      }
      $page->eject($page->url('delete', $form->eject, 'id'));
    }
    $html .= $form->header();
    $html .= $form->label_field('category', 'text', array('maxlength'=>100));
    if (empty($this->tags)) {
      $html .= $form->label('Tags', '<p class="help-block">You have no tags from which to select from.</p>');
    } else {
      $html .= $form->label_field('tags[]', 'select');
    }
    $html .= $form->submit($edit ? 'Edit' : 'Submit');
    $html .= $form->close();
    unset($form);
    $this->blog->db->query('SELECT id, url, category, tags FROM categories ORDER BY url ASC');
    $rows = $this->blog->db->fetch('row', 'all');
    foreach ($rows as $row) {
      list($id, $url, $category, $tags) = $row;
      $html .= $bp->media(array(
        $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'id', $id))),
        '<h4><a href="' . BASE_URL . $url . '/">' . $category . '</a></h4><p>' . implode(', ', $this->blog->tagged($tags)) . '</p>',
        $bp->button('link delete', $bp->icon('trash'), array('data-url'=>$page->url('add', '', 'delete', $id), 'title'=>'Delete'))
      ));
    }
    $page->plugin('jQuery', 'code', '
      $("button.delete").click(function(){
        var url = $(this).data("url");
        if (confirm("Are you sure you would like to delete this category?")) {
          window.location = url;
        }
      });
    ');
    return $html;
  }
  
  private function tags () {
    global $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'setup_tags');
    $form->validate('tags[]', 'Tags');
    if ($form->submitted() && empty($form->errors)) {
      foreach ($form->vars['tags'] as $id => $tag) {
        if (!empty($tag) && isset($this->tags[$id]) && strtolower($this->tags[$id]['name']) == strtolower($tag)) {
          $this->blog->db->query('UPDATE tags SET tag = ? WHERE id = ? AND url = ?', array($tag, $id, $this->tags[$id]['url']));
        }
      }
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $html .= '<p class="text-center">This form is to help you standardize the capitalization of your tags.<br>The only way to delete or change them is by editing the applicable pages and posts.<br>It is not necessary to re-enter every value.  Only the ones you wish to update.</p>';
    foreach ($this->tags as $id => $tag) {
      $html .= $form->label('<a href="' . BASE_URL . 'tags/' . $tag['url'] . '">' . $tag['name'] . '</a>', $form->field('tags[' . $id . ']', 'text', array('prepend'=>'<span class="badge">' . $tag['count'] . '</span>')));
    }
    $html .= $form->submit('Edit');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
}

/* End of file Admin_setup.php */
/* Location: ./application/libraries/Admin/drivers/Admin_setup.php */