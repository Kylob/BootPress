<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_blog extends CI_Driver {
  
  private $tags = null;
  private $authors = null;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $view = (isset($params['folder']) && in_array($params['folder'], array('published', 'unpublished', 'posts', 'pages', 'authors', 'categories', 'tags', 'templates'))) ? $params['folder'] : 'form';
    $html = $this->$view($params);
    if ($ci->input->get('image')) return $this->display($html);
    switch ($view) {
      case 'published':
        if ($search = $ci->input->get('search')) {
          $header = "Search for '{$search}'";
          break;
        }
      case 'form': $header = ($edit = $ci->input->get('edit')) ? 'Edit' : 'New'; break;
      default: $header = ucwords($view); break;
    }
    $page->link('<style>.nav.nav-tabs > li { white-space: nowrap; }</style>');
    $menu = array();
    if ($unpublished = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published = 0')) {
      $menu['<span class="text-danger"><b>Unpublished</b> ' . $bp->badge($unpublished) . '</span>'] = BASE_URL . ADMIN . '/blog/unpublished';
    }
    if ($posts = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published < 0')) {
      $menu[$bp->icon('thumb-tack', 'fa') . ' Posts ' . $bp->badge($posts)] = BASE_URL . ADMIN . '/blog/posts';
    }
    if ($pages = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published = 1')) {
      $menu[$bp->icon('file', 'fa') . ' Pages ' . $bp->badge($pages)] = BASE_URL . ADMIN . '/blog/pages';
    }
    if (is_admin(1)) {
      if (count($this->set_authors()) > 0) $menu['Authors'] = BASE_URL . ADMIN . '/blog/authors';
      if (count($this->set_tags()) > 0) {
        $menu['Categories'] = BASE_URL . ADMIN . '/blog/categories';
        $menu['Tags'] = BASE_URL . ADMIN . '/blog/tags';
      }
      if ($posts || $pages) $menu['Templates'] = BASE_URL . ADMIN . '/blog/templates';
    }
    $options = array('active'=>'url');
    if (count($menu > 2)) $options['align'] = 'justified';
    $menu = (!empty($menu)) ? $bp->tabs($menu, $options) : '';
    $docs = '';
    if (!in_array($view, array('authors', 'categories', 'tags'))) {
      $tab = ($view == 'templates') ? 'templates' : 'blog';
      $docs = $bp->button('sm info pull-right', 'Documentation ' . $bp->icon('new-window'), array('href'=>'http://bootpress.org/getting-started#' . $tab, 'target'=>'_blank'));
    }
    $header = '<div class="page-header"><p class="lead">' . $bp->icon('globe', 'fa') . ' ' . $header . ' ' . $docs . '</p></div>';
    return $this->display($menu . $header . $html);
  }
  
  private function published ($params) {
    global $bp, $ci, $page;
    if ($search = $ci->input->post('search')) $page->eject($page->url('add', BASE_URL . ADMIN . '/blog/published', 'search', trim($search, "'")));
    $bp->listings->display(20);
    if ($search = $ci->input->get('search')) {
      $page->title = 'Search Published Posts and Pages at ' . $ci->blog->name;
      if (!$bp->listings->set) $bp->listings->count($ci->sitemap->count($search, 'blog'));
      $search = $ci->sitemap->search($search, 'blog', $bp->listings->limit(), array(1, 2, .5, 1, .25));
      $posts = $alt = array();
      foreach ($search as $blog) {
        $posts[] = $blog['id'];
        $alt[$blog['id']] = $blog['snippet'];
      }
      return (!empty($posts)) ? $this->listings('WHERE id IN (' . implode(', ', $posts) . ')' . $ci->blog->db->order_in('id', $posts), $alt) : '';
    }
    $page->title = 'Published Posts and Pages at ' . $ci->blog->name;
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published = 1'));
    return $this->listings('WHERE published != 0 ORDER BY published, updated ASC' . $bp->listings->limit());
  }
  
  private function unpublished () {
    global $ci, $page;
    $page->title = 'Unpublished Posts and Pages at ' . $ci->blog->name;
    return $this->listings('WHERE published = 0 ORDER BY published, updated ASC');
  }
  
  private function posts () {
    global $bp, $ci, $page;
    $html = '';
    $page->title = 'Published Posts at ' . $ci->blog->name;
    $bp->listings->display(20);
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published < 0'));
    $html .= $this->listings('WHERE published < 0 ORDER BY published, updated ASC' . $bp->listings->limit());
    return $html;
  }
  
  private function pages () {
    global $bp, $ci, $page;
    $page->title = 'Published Pages at ' . $ci->blog->name;
    $bp->listings->display(20);
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE published = 1'));
    return $this->listings('WHERE published = 1 ORDER BY published, updated ASC' . $bp->listings->limit());
  }
  
  private function authors () {
    global $bp, $ci, $page;
    $html = '';
    $media = $ci->admin->files->view('authors', $ci->blog->authors);
    if ($ci->input->get('image')) return $media;
    if (!is_admin(1)) return;
    if (!is_dir($ci->blog->authors)) mkdir($ci->blog->authors, 0755, true);
    $form = $page->plugin('Form', 'name', 'admin_blog_authors');
    $html .= $form->header();
    foreach ($this->set_authors() as $row) {
      list($count, $id, $uri, $name) = $row;
      $ci->admin->files->save(array($uri => $ci->blog->authors . $uri . '.php'), array($uri));
      $author = $ci->blog->authors($uri, $name);
      $label = '<a href="' . $ci->blog->url['listings'] . 'authors/' . $uri . '/">' . $author['name'] . '</a> ' . $bp->badge($count);
      if (!empty($author['thumb'])) {
        $label .= '<br>' . $bp->img($author['thumb'], 'style="margin:20px auto;"', '', '~75x75/' . $author['uri']);
        $author['thumb'] = substr($author['thumb'], strrpos($author['thumb'], '/') + 1);
      }
      unset($author['uri'], $author['url']);
      $form->values($uri, implode("\n\n", array(
        '<?php',
        '$author = ' . var_export($author, true) . ';',
        'return $author;',
        '?>'        
      )));
      $html .= $form->field($uri, 'textarea', array('label'=>$label, 'class'=>'wyciwyg php input-sm', 'data-file'=>$uri . '.php', 'spellcheck'=>'false')) . '<br>';
    }
    $html .= $form->close();
    unset($form);
    $page->plugin('jQuery', 'code', '$("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = window.location.href; });');
    return $html . $media;
  }
  
  private function categories () {
    global $bp, $ci, $page;
    $html = '';
    if ($delete = $ci->input->get('delete')) {
      if (is_numeric($delete)) $ci->blog->db->delete('categories', 'id', (int) $delete);
      $page->eject($page->url('delete', '', '?'));
    }
    $form = $page->plugin('Form', 'name', 'admin_blog_categories');
    if ($edit = $ci->input->get('id')) {
      if ($edit = $ci->blog->db->row('SELECT category, tags FROM categories WHERE id = ?', array($edit))) {
        $edit['tags[]'] = explode(',', $edit['tags']);
        $form->values($edit);
      } else {
        $page->eject($page->url('delete', '', 'id'));
      }
    }
    $tags = array();
    foreach ($this->set_tags() as $id => $tag) $tags[$id] = '(' . $tag['count'] . ') ' . $tag['name'];
    $form->menu('tags[]', $tags);
    $form->validate(
      array('category', 'Category', 'required', 'Enter the categories name.'),
      array('tags[]', 'Tags', 'required|inarray[menu]', 'Select the tags that will be used to determine what posts and pages should be listed under this category.')
    );
    if ($form->submitted() && empty($form->errors)) {
      $categories = array();
      $categories['uri'] = $page->seo($form->vars['category']);
      $categories['category'] = $form->vars['category'];
      $categories['tags'] = implode(',', $form->vars['tags']);
      if ($edit) {
        $ci->blog->db->update('categories', 'id', array($edit['id'] => $categories));
      } else {
        $ci->blog->db->insert('categories', $categories);
      }
      $page->eject($page->url('delete', $form->eject, '?'));
    }
    $html .= $form->header();
    $html .= $form->field('category', 'text', array('maxlength'=>100));
    if (empty($this->tags)) {
      $html .= $form->field(false, '<p class="form-control-static">You have no tags from which to select from.</p>');
    } else {
      $html .= $form->field('tags[]', 'select');
    }
    $html .= $form->submit($edit ? 'Edit' : 'Create');
    $html .= $form->close();
    unset($form);
    $ci->blog->db->query('SELECT id, uri, category, tags FROM categories ORDER BY uri ASC');
    $categories = $ci->blog->db->fetch('assoc', 'all');
    if (empty($categories)) return $html;
    $html .= '<div class="page-header"><p class="lead">' . $bp->icon('globe', 'fa') . ' View</p></div>';
    foreach ($categories as $row) {
      list($id, $uri, $category, $tags) = array_values($row);
      $tagged = array();
      foreach (explode(',', $tags) as $key) $tagged[] = $this->tags[$key]['name'];
      $html .= $bp->row('xs', array(
        $bp->col('2 text-right', $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url('add', '', 'id', $id)))),
        $bp->col(10, $bp->media(array('', 
          '<h4><a href="' . $ci->blog->url['listings'] . $uri . '">' . $category . '</a></h4><p>' . implode(', ', $tagged) . '</p>',
          $bp->button('link delete', $bp->icon('trash'), array('data-url'=>$page->url('add', '', 'delete', $id), 'title'=>'Delete'))
        )))
      )) . '<br>';
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
    global $bp, $ci, $page;
    $html = '';
    $this->set_tags();
    $form = $page->plugin('Form', 'name', 'admin_blog_tags');
    $form->validate('tags[]', 'Tags');
    if ($form->submitted() && empty($form->errors)) {
      foreach ($form->vars['tags'] as $id => $tag) {
        if (!empty($tag) && isset($this->tags[$id]) && strtolower($this->tags[$id]['name']) == strtolower($tag)) {
          $ci->blog->db->query('UPDATE tags SET tag = ? WHERE id = ? AND uri = ?', array($tag, $id, $this->tags[$id]['uri']));
        }
      }
      $page->eject($form->eject);
    }
    $html .= '<p class="text-center">This form is to help you standardize the capitalization of your tags.<br>The only way to delete or change them is by editing the applicable pages and posts.<br>It is not necessary to re-enter every value.  Only the ones you wish to update.</p>';
    if (!empty($this->tags)) {
      $html .= $form->header();
      foreach ($this->tags as $id => $tag) {
        $html .= $form->field('tags[' . $id . ']', 'text', array(
          'label' => '<a href="' . $ci->blog->url['listings'] . 'tags/' . $tag['uri'] . '">' . $tag['name'] . '</a>',
          'prepend' => '<span class="badge">' . $tag['count'] . '</span>'
        ));
      }
      $html .= $form->submit('Edit');
      $html .= $form->close();
    }
    unset($form);
    return $html;
  }
  
  private function templates () {
    global $bp, $ci, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'admin_blog_templates');
    $form->values($ci->admin->files->save(array(
      'post' => array($ci->blog->post . 'post.tpl', $ci->blog->templates . 'post.tpl'),
      'listings' => array($ci->blog->post . 'listings.tpl', $ci->blog->templates . 'listings.tpl'),
      'tags' => array($ci->blog->post . 'tags.tpl', $ci->blog->templates . 'tags.tpl'),
      'authors' => array($ci->blog->post . 'authors.tpl', $ci->blog->templates . 'authors.tpl'),
      'archives' => array($ci->blog->post . 'archives.tpl', $ci->blog->templates . 'archives.tpl')
    ), array('post', 'listings', 'tags', 'authors', 'archives')));
    $form->validate(
      array('post', 'post.tpl'),
      array('listings', 'listings.tpl'),
      array('tags', 'tags.tpl'),
      array('authors', 'authors.tpl'),
      array('archives', 'archives.tpl')
    );
    $html .= $form->header();
    $html .= $form->field('post', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'post.tpl'));
    $html .= $form->field('listings', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'listings.tpl'));
    $html .= $form->field('tags', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'tags.tpl'));
    $html .= $form->field('authors', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'authors.tpl'));
    $html .= $form->field('archives', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'archives.tpl'));
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  public function update ($uri) {
    global $ci;
    $ci->blog->file($uri);
  }
  
  private function form () {
    global $bp, $ci, $page;
    $html = '';
    $media = '';
    $form = $page->plugin('Form', 'name', 'blog_entry');
    if ($edit = $ci->input->get('edit')) {
      if (!$uri = $ci->blog->db->value('SELECT uri FROM blog WHERE id = ?', array($edit))) $page->eject($page->url('delete', '', 'edit'));
      $ci->sitemap->modify('uri', $uri);
      if ($ci->input->get('delete') == 'post') {
        $ci->blog->delete($uri);
        $page->eject($page->url('delete', '', '?'));
      }
      $form->values($ci->admin->files->save(array(
        'index' => array($ci->blog->post . $uri . '/index.tpl', $ci->blog->templates . 'blog.tpl')
      ), array('index'), array($this, 'update'), array($uri)));
      $media = $ci->admin->files->view('blog', $ci->blog->post . $uri);
      if ($ci->input->get('image')) return $media;
      $form->values('uri', $uri);
    }
    $form->validate(
      array('uri', 'URL', '', 'A unique path to this post that should never change once it has been published.'),
      array('index', 'index.tpl', '', 'This file contains the content of your page or post.')
    );
    #-- Submitted --#
    if ($form->submitted() && empty($form->errors)) {
      if ($edit) {
        $rename = $this->seo($form->vars['uri'], $uri, $edit);
        if ($rename != $uri && $ci->blog->rename($uri, $rename) === false) {
          $form->errors['uri'] = 'Sorry, this URL has already been taken.';
        }
      } else {
        $blog = $ci->blog->create($this->seo($form->vars['uri']));
        $form->eject = $page->url('add', $form->eject, 'edit', $blog['id']);
      }
      if (empty($form->errors)) $page->eject($form->eject);
    }
    #-- Form --#
    $html .= $form->header();
    $args = array('prepend'=>'/');
    if ($ci->config->item('url_suffix') != '') {
      $args['append'] = array(
        $ci->config->item('url_suffix'),
        $bp->button('primary', ($edit) ? 'Submit' : 'Create', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))
      );
    } else {
      $args['append'] = $bp->button('primary', ($edit) ? 'Submit' : 'Create', array('type'=>'submit', 'data-loading-text'=>'Submitting...'));
    }
    if ($edit) {
      $post = $ci->blog->db->row('SELECT uri, title, published FROM blog WHERE id = ?', array($edit));
      if (empty($post['title'])) $post['title'] = 'Untitled';
      $type = ($post['published'] < 0) ? 'post' : 'page';
      $title = '<a href="' . BASE_URL . $post['uri'] . '" target="_blank">' . $post['title'] . ' ' . $bp->icon('new-window') . '</a>';
      $delete = $bp->button('sm danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this ' . $type));
      $html .= '<p class="lead">' . $title . ' ' . $delete . '</p><br>';
      $page->plugin('jQuery', array('code'=>'
        $("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = window.location.href; });
        $(".delete").click(function(){
          if (confirm("Are you sure you would like to delete this ' . $type . '?")) {
            window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'post')) . '";
          }
        });
      '));
      $html .= $form->field('uri', 'text', $args);
      $html .= $form->field('index', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'index.tpl'));
    } else {
      $html .= $form->field('uri', 'text', $args);
      $html .= $form->field('index', 'hidden');
    }
    $html .= $form->close();
    return $html . $media;
  }
  
  private function seo ($new, $old='', $id=0) {
    global $ci, $page;
    if (!empty($new) && $new == $old) return $old; // no changes were made
    $uri = $page->seo($new, 'slashes');
    if (!empty($uri) && !$ci->blog->db->value('SELECT id FROM blog WHERE uri = ? AND id != ?', array($uri, $id))) return $uri;
    $increment = 1;
    if (!empty($uri)) $uri .= '-';
    while ($ci->blog->db->value('SELECT id FROM blog WHERE uri = ?', array($uri . $increment))) $increment++;
    return $uri . $increment;
  }
  
  private function set_tags () {
    global $ci;
    if (is_null($this->tags)) {
      $this->tags = array();
      $ci->blog->db->query(array(
        'SELECT COUNT(t.blog_id), tags.id, tags.uri, tags.tag',
        'FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'INNER JOIN tags ON t.tag_id = tags.id',
        'WHERE b.published != 0',
        'GROUP BY tags.id',
        'ORDER BY tags.uri ASC'
      ));
      while (list($count, $id, $uri, $tag) = $ci->blog->db->fetch('row')) {
        $this->tags[$id]['count'] = $count;
        $this->tags[$id]['name'] = $tag;
        $this->tags[$id]['uri'] = $uri;
      }
    }
    return $this->tags;
  }
  
  private function set_authors () {
    global $ci;
    if (is_null($this->authors)) {
      $ci->blog->db->query(array(
        'SELECT COUNT(*) AS count, a.id, a.uri, a.author AS name',
        'FROM blog AS b',
        'INNER JOIN authors AS a ON b.author = a.uri',
        'WHERE b.published < 0 AND b.updated < 0 AND b.author != ""',
        'GROUP BY b.author',
        'ORDER BY a.author ASC'
      ));
      $this->authors = $ci->blog->db->fetch('row', 'all');
    }
    return $this->authors;
  }
  
  private function listings ($query, $alt=array()) {
    global $bp, $ci, $page;
    $html = '';
    $ci->blog->db->query(array('SELECT id, uri, seo, title, description, thumb, ABS(published), ABS(updated) FROM blog', $query));
    while (list($id, $uri, $seo, $title, $description, $thumb, $published, $updated) = $ci->blog->db->fetch('row')) {
      $thumb = $bp->img($thumb, 'width="75" height="75"', '', '75x75/' . $seo);
      if ($published == 0) { // unpublished
        $reference = '<span class="timeago" title="' . date('c', $updated) . '">' . $updated . '</span>';
      } elseif ($published == 1) { // page
        $reference = $bp->icon('refresh', 'fa') . ' <span class="timeago" title="' . date('c', $updated) . '">' . $updated . '</span>';
      } else { // post
        $reference = $bp->icon('tack', 'fa') . ' ' . date('M j, Y', $published);
      }
      $html .= $bp->row('sm', array(
        $bp->col(1, '<p>' . $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>BASE_URL . ADMIN . '/blog?edit=' . $id)) . '</p>'),
        $bp->col(8, $bp->media(array($thumb, '<h4><a href="' . BASE_URL . $uri . '/">' . (!empty($title) ? $title : 'Untitled') . '</a></h4><p>' . (isset($alt[$id]) ? $alt[$id] : $description) . '</p>'))),
        $bp->col(3, '<p>' . $reference . '</p>')
      )) . '<br>';
    }
    if (strpos($html, 'class="timeago"')) {
      $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
      $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    }
    return $html . '<div class="text-center">' . $bp->listings->pagination() . '</div>';
  }
  
}

/* End of file Admin_blog.php */
/* Location: ./application/libraries/Admin/drivers/Admin_blog.php */