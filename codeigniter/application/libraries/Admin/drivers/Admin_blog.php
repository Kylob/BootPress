<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_blog extends CI_Driver {
  
  private $tags = null;
  private $authors = null;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $bp->listings->display(20);
    $view = (isset($params['folder']) && in_array($params['folder'], array('published', 'unpublished', 'posts', 'pages', 'authors', 'categories', 'tags', 'templates', 'backup', 'restore'))) ? $params['folder'] : 'form';
    $html = $this->$view($params);
    if ($ci->input->get('image')) {
      return $this->display($this->box('default', array(
        'head with-border' => $bp->icon('image', 'fa') . ' Image',
        'body' => $html
      )));
    }
    switch ($view) {
      case 'published':
        $header = $bp->icon('search', 'fa', 'i style="margin-right:10px;"');
        $header .= ($search = $ci->input->get('search')) ? " Search for '{$search}'" : ' Published';
        break;
      case 'unpublished': $header = $bp->icon('exclamation-triangle', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'posts': $header = $bp->icon('thumb-tack', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'pages': $header = $bp->icon('file-o', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'authors': $header = $bp->icon('user', 'glyphicon', 'span style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'categories': $header = $bp->icon('share-alt', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'tags': $header = $bp->icon('tags', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'templates': $header = $bp->icon('files-o', 'fa', 'i style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'backup': $header = $bp->icon('download', 'glyphicon', 'span style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'restore': $header = $bp->icon('upload', 'glyphicon', 'span style="margin-right:10px;"') . ' ' . ucwords($view); break;
      case 'form': 
        $header = $bp->icon('pencil-square-o', 'fa', 'i style="margin-right:10px;"');
        $header .= ($edit = $ci->input->get('edit')) ? ' Edit' : ' New';
        break;
    }
    $docs = '';
    if (!in_array($view, array('authors', 'categories', 'tags', 'backup', 'restore'))) {
      $tab = ($view == 'templates') ? 'templates' : 'blog';
      $docs = $bp->button('md link', 'Documentation ' . $bp->icon('new-window'), array('href'=>'https://www.bootpress.org/docs/' . $tab . '/', 'target'=>'_blank'));
    }
    return $this->display($this->box('default', array(
      'head with-border' => array($header, $docs),
      'body' => $html,
      'foot clearfix' => $bp->listings->pagination('sm no-margin')
    )));
  }
  
  public function links () {
    global $bp, $ci, $page;
    $links = array($bp->icon('pencil-square-o', 'fa') . ' New' => $page->url($this->url, 'blog'));
    if ($unpublished = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published = 0')) {
      $links['<span class="text-danger">' . $bp->icon('exclamation-triangle', 'fa') . ' <b>Unpublished</b> ' . $bp->badge($unpublished, 'right') . '</span>'] = $page->url($this->url, 'blog/unpublished');
    }
    if ($posts = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published < 0')) {
      $links[$bp->icon('thumb-tack', 'fa') . ' Posts ' . $bp->badge($posts, 'right')] = $page->url($this->url, 'blog/posts');
    }
    if ($pages = $ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published = 1')) {
      $links[$bp->icon('file-o', 'fa') . ' Pages ' . $bp->badge($pages, 'right')] = $page->url($this->url, 'blog/pages');
    }
    if (is_admin(1)) {
      if ($posts || $pages) $links[$bp->icon('files-o', 'fa') . ' Templates'] = $page->url($this->url, 'blog/templates');
      if (count($this->set_tags()) > 0) $links[$bp->icon('tags', 'fa') . ' Tags'] = $page->url($this->url, 'blog/tags');
      if ($ci->blog->db->value('SELECT COUNT(*) FROM categories')) $links[$bp->icon('share-alt', 'fa') . ' Categories'] = $page->url($this->url, 'blog/categories');
      if (count($this->set_authors()) > 0) $links[$bp->icon('user') . ' Authors'] = $page->url($this->url, 'blog/authors');
      if ($unpublished || $posts || $pages) $links[$bp->icon('download') . ' Backup'] = $page->url($this->url, 'blog/backup');
      $links[$bp->icon('upload') . ' Restore'] = $page->url($this->url, 'blog/restore');
    }
    return $links;
  }
  
  public function update ($uri=null) {
    global $ci;
    if (!empty($uri)) {
      $ci->blog->file($uri);
      $ci->sitemap->refresh();
    } else {
      $ci->sitemap->suspend_caching(0);
    }
  }
  
  private function published ($params) {
    global $bp, $ci, $page;
    if ($search = $ci->input->post('search')) $page->eject($page->url($this->url, 'blog/published?search=' . trim($search, "'")));
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
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published != 0'));
    return $this->listings('WHERE featured <= 0 AND published != 0 ORDER BY published, updated ASC' . $bp->listings->limit());
  }
  
  private function unpublished () {
    global $ci, $page;
    $page->title = 'Unpublished Posts and Pages at ' . $ci->blog->name;
    return $this->listings('WHERE featured <= 0 AND published = 0 ORDER BY featured, published, updated ASC');
  }
  
  private function posts () {
    global $bp, $ci, $page;
    $html = '';
    $page->title = 'Published Posts at ' . $ci->blog->name;
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published < 0'));
    $html .= $this->listings('WHERE featured <= 0 AND published < 0 ORDER BY featured, published, updated ASC' . $bp->listings->limit());
    return $html;
  }
  
  private function pages () {
    global $bp, $ci, $page;
    $page->title = 'Published Pages at ' . $ci->blog->name;
    if (!$bp->listings->set) $bp->listings->count($ci->blog->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published = 1'));
    return $this->listings('WHERE featured <= 0 AND published = 1 ORDER BY featured, published, updated ASC' . $bp->listings->limit());
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
      $ci->admin->files->save(array($uri => $ci->blog->authors . $uri . '.ini'), array($uri), array($this, 'update'));
      $author = $ci->blog->authors($uri, $name);
      $label = '<a href="' . $page->url('blog', 'authors', $uri) . '">' . $author['name'] . '</a> ' . $bp->badge($count);
      if (!empty($author['thumb'])) {
        $label .= '<br>' . $bp->img($author['thumb'], 'style="margin:20px auto;"', '', '~75x75/' . $author['uri']);
        $author['thumb'] = substr($author['thumb'], strrpos($author['thumb'], '/') + 1);
      }
      unset($author['uri'], $author['url']);
      $form->values($uri, $ci->admin->files->ini($author));
      $html .= $form->field($uri, 'textarea', array('label'=>$label, 'class'=>'wyciwyg ini input-sm', 'data-file'=>$uri . '.ini', 'spellcheck'=>'false', 'rows'=>5)) . '<br>';
    }
    $html .= $form->close();
    unset($form);
    $page->plugin('jQuery', 'code', '$("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = window.location.href; });');
    return $html . $media;
  }
  
  private function categories () {
    global $bp, $ci, $page;
    $hier = $page->plugin('Hierarchy', 'categories', $ci->blog->db);
    if (($pk = $ci->input->post('pk')) && ($value = $ci->input->post('value'))) {
      $ci->blog->db->update('categories', 'id', array((int) $pk => array('category'=>html_escape($value))));
      $hier->refresh('category');
      exit;
    }
    $tree = $hier->tree(array('uri', 'category'));
    $counts = $hier->counts('blog', 'category_id');
    foreach ($tree as $id => $fields) {
      $category = array();
      $category[] = '<a class="rename-category" href="#" data-pk="' . $id . '" title="Rename Category">' . $fields['category'] . '</a>';
      $category[] = $bp->button('link', $fields['uri'] . ' ' . $bp->icon('new-window'), array('href'=>$page->url('blog', $fields['uri']), 'target'=>'_blank'));
      $category[] = $bp->badge($counts[$id]);
      array_unshift($tree[$id], '<p>' . implode(' ', $category) . '</p>');
    }
    $page->plugin('CDN', 'links', array(
      'bootstrap.editable/1.5.1/css/bootstrap-editable.min.css',
      'bootstrap.editable/1.5.1/js/bootstrap-editable.min.js'
    ));
    $page->plugin('jQuery', 'code', '
      $(".rename-category").editable({
        type: "text",
        name: "category",
        title: "Rename Category",
        url: window.location.href,
        validate: function(value) { if($.trim(value) == "") return "This field is required"; }
      });
    ');
    return $bp->lister('ul list-unstyled', $hier->lister($tree));
  }
  
  private function tags () {
    global $bp, $ci, $page;
    $this->set_tags();
    if (($pk = $ci->input->post('pk')) && ($value = $ci->input->post('value'))) {
      if (isset($this->tags[$pk])) {
        if ($page->seo($value) != $page->seo($this->tags[$pk]['name'])) {
          set_status_header(400);
          exit('The tag is misspelled.');
        } else {
          $ci->blog->db->update('tags', 'id', array($pk => array('tag' => html_escape($value))));
          exit;
        }
      }
    }
    $tags = array();
    foreach ($this->tags as $id => $fields) {
      $tags[] = $bp->col(4, '<p>' . implode(' ', array(
        '<a class="capitalize-tag" href="#" data-pk="' . $id . '" title="Edit Capitalization">' . $fields['name'] . '</a>',
        $bp->button('link', $bp->icon('new-window'), array('href'=>$page->url('blog', 'tags', $fields['uri']), 'target'=>'_blank')),
        $bp->badge($fields['count'])
      )) . '</p>');
    }
    $page->plugin('CDN', 'links', array(
      'bootstrap.editable/1.5.1/css/bootstrap-editable.min.css',
      'bootstrap.editable/1.5.1/js/bootstrap-editable.min.js'
    ));
    $page->plugin('jQuery', 'code', '
      $(".capitalize-tag").editable({
        type: "text",
        name: "tag",
        title: "Edit Capitalization",
        url: window.location.href,
        validate: function(value) { if($.trim(value) == "") return "This field is required"; }
      });
    ');
    return $bp->row('md', $tags);
  }
  
  private function templates () {
    global $bp, $ci, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'admin_blog_templates');
    $form->values($ci->admin->files->save(array(
      'blog' => array($ci->blog->post . 'blog.tpl', $ci->blog->templates . 'blog.tpl'),
      'listings' => array($ci->blog->post . 'listings.tpl', $ci->blog->templates . 'listings.tpl'),
      'tags' => array($ci->blog->post . 'tags.tpl', $ci->blog->templates . 'tags.tpl'),
      'authors' => array($ci->blog->post . 'authors.tpl', $ci->blog->templates . 'authors.tpl'),
      'archives' => array($ci->blog->post . 'archives.tpl', $ci->blog->templates . 'archives.tpl')
    ), array('blog', 'listings', 'tags', 'authors', 'archives'), array($this, 'update')));
    $form->validate(
      array('blog', 'blog.tpl'),
      array('listings', 'listings.tpl'),
      array('tags', 'tags.tpl'),
      array('authors', 'authors.tpl'),
      array('archives', 'archives.tpl')
    );
    $html .= $form->header();
    $html .= $form->field('blog', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'blog.tpl'));
    $html .= $form->field('listings', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'listings.tpl'));
    $html .= $form->field('tags', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'tags.tpl'));
    $html .= $form->field('authors', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'authors.tpl'));
    $html .= $form->field('archives', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'archives.tpl'));
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function backup () {
    global $ci, $page;
    $ci->load->library('zip');
    $ci->zip->compression_level = 9;
    if (is_dir($ci->blog->post)) $ci->zip->read_dir($ci->blog->post, false);
    if (is_dir($ci->blog->authors)) $ci->zip->read_dir($ci->blog->authors, false);
    $ci->zip->download('backup-blog-' . $page->get('domain') . '-' . date('Y-m-d') . '.zip');
  }
  
  private function restore () {
    global $ci, $page;
    $form = $page->plugin('Form', 'name', 'restore_blog');
    $html = '';
    $form->upload('upload', 'Backup', 'zip', array('filesize'=>10, 'info'=>''));
    if ($form->submitted() && empty($form->errros)) {
      if (!empty($form->vars['upload'])) {
        list($zip, $name) = each($form->vars['upload']);
        $ci->load->library('unzip');
        $ci->unzip->files($zip, BASE_URI . 'blog', 0755);
        $uris = $ci->unzip->extract_folders('authors', 'ini|jpg|jpeg|gif|png');
        $uris = $ci->unzip->extract_folders('content', 'tpl|js|css|jpg|jpeg|gif|png|ico|pdf|ttf|otf|svg|eot|woff|swf|tar|gz|tgz|zip|csv|xl|xls|xlsx|word|doc|docx|ppt|mp3|ogg|wav|mpe|mpeg|mpg|mov|qt|psd');
        $ci->unzip->close();
        unlink($zip);
        if (isset($uris['content'])) {
          foreach ($uris['content'] as $file => $location) {
            if (substr($file, -9) != 'index.tpl') continue;
            $ci->blog->file(substr($file, 0, -10));
          }
        }
      }
      $ci->blog->decache();
      $page->eject($page->url('admin', 'blog'));
    }
    $html .= $form->header();
    $html .= $form->field('upload', 'file');
    $html .= $form->submit();
    $html .= $form->close();
    return $html;
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
        'index' => array($ci->blog->post . $uri . '/index.tpl', $ci->blog->templates . 'index.tpl')
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
      $title = '<a href="' . $page->url('base', $post['uri']) . '" target="_blank">' . $post['title'] . ' ' . $bp->icon('new-window') . '</a>';
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
    }
    $select = array();
    $suffix = ($edit) ? (($slash = strrpos($post['uri'], '/')) ? substr($post['uri'], $slash) : '/' . $post['uri']) : '/';
    $ci->blog->db->query('SELECT uri FROM categories ORDER BY uri ASC');
    while (list($uri) = $ci->blog->db->fetch('row')) $select[$uri . $suffix] = $uri . $suffix;
    if (!empty($select)) {
      $default = ($edit) ? $post['uri'] : '&nbsp;';
      if ($edit && isset($select[$post['uri']])) {
        $default = '&nbsp;';
        $select[$post['uri']] .= ' (current url)';
      }
      $form->menu('category', $select, '&nbsp;');
      $form->validate('category', 'Category', '', 'You may select a category among those already in use.');
      $html .= $form->field('category', 'select');
      $page->plugin('jQuery', 'code', '
        $("select[name=category]").change(function(){
          $("input[name=uri]").focus().val($(this).val());
        });
      ');
    }
    if ($edit) {
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
        'WHERE b.featured <= 0 AND b.published != 0',
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
        'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND b.author != ""',
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
      if ($uri == 'index') $uri = '';
      $thumb = $bp->img($thumb, 'width="75" height="75"', '', '75x75/' . $seo);
      if ($published == 0) { // unpublished
        $reference = '<span class="timeago" title="' . date('c', $updated) . '">' . $updated . '</span>';
      } elseif ($published == 1) { // page
        $reference = $bp->icon('refresh', 'fa') . ' <span class="timeago" title="' . date('c', $updated) . '">' . $updated . '</span>';
      } else { // post
        $reference = $bp->icon('tack', 'fa') . ' ' . date('M j, Y', $published);
      }
      $html .= $bp->row('sm', array(
        $bp->col(1, '<p>' . $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$page->url($this->url, 'blog?edit=' . $id))) . '</p>'),
        $bp->col(11, $bp->media(array($thumb, '
          <h4><a href="' . $page->url('base', $uri) . '">' . (!empty($title) ? $title : 'Untitled') . '</a> <small class="pull-right">' . $reference . '</small></h4>
          <p><span class="text-danger"><small>' . BASE_URL . $uri . '</small></span><br>' . (isset($alt[$id]) ? $alt[$id] : $description) . '</p>
        ')))
      )) . '<br>';
    }
    if (strpos($html, 'class="timeago"')) {
      $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
      $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    }
    return $html; // . '<div class="text-center">' . $bp->listings->pagination() . '</div>';
  }
  
}

/* End of file Admin_blog.php */
/* Location: ./application/libraries/Admin/drivers/Admin_blog.php */