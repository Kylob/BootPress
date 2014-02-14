<?php

class BlogAdminBlog extends BlogAdmin {

  private $edit = false;
  
  public function view () {
    global $page, $bp;
    $this->edit = (isset($_GET['edit']) && $this->db->value('SELECT id FROM blog WHERE id = ?', array($_GET['edit']))) ? $_GET['edit'] : false;
    if ($this->edit && isset($_POST['wyciwyg']) && isset($_POST['field'])) {
      if ($_POST['field'] == 'php' && is_admin(1)) {
        $result = $this->file_put_post($this->dir . 'plugins/' . $this->edit . '.php', 'wyciwyg');
        if ($result === true) {
          $this->update_post_search($this->edit);
          echo 'Saved';
        } else {
          echo $result;
        }
        exit;
      } elseif ($_POST['field'] == 'post') {
        $post = $this->code('wyciwyg');
        $result = $this->smarty('blog', $post, 'testing');
        if ($result === true) {
          $this->db->update('blog', array('post'=>$post), 'id', $this->edit);
          $this->update_post_search($this->edit);
          echo 'Saved';
        } else {
          echo $result;
        }
        exit;
      }
      echo 'Error';
      exit;
    }
    if (isset($_POST['search'])) {
      $eject = (!empty($_POST['search'])) ? $page->url('add', '', 'search', $_POST['search']) : $page->url('delete', '', 'search');
      $page->eject($eject);
    }
    $menu = $this->menu();
    $search = '';
    $blog = '';
    #-- Determine Method --#
    if (isset($_GET['view']) && ($_GET['view'] == 'published' || $_GET['view'] == 'unpublished')) {
      $page->title = 'View ' . ucwords($_GET['view']) . ' Posts and Pages at ' . $this->blog['name'];
      if ($_GET['view'] == 'published') $search = $bp->search(array('post'=>$page->url()));
      $blog .= $this->listings($_GET['view']);
    } else {
      $blog .= $this->form();
    }
    #-- Deliver Content --#
    $html = $bp->row('sm', array(
      $bp->col(8, $menu),
      $bp->col(4, $search)
    ));
    $html .= '<br>' . $blog;
    return $this->admin($html);
  }
  
  private function menu () {
    global $page, $bp;
    $url = $this->blog['url'] . 'admin/blog/';
    $published = $bp->badge($this->db->value('SELECT COUNT(*) FROM blog WHERE page >= 0 AND published != 0'));
    $unpublished = $bp->badge($this->db->value('SELECT COUNT(*) FROM blog WHERE page >= 0 AND published = 0'));
    return $bp->pills(array(
      'New' => $url,
      'Published ' . $published => $page->url('add', $url, 'view', 'published'),
      'Unpublished ' . $unpublished => $page->url('add', $url, 'view', 'unpublished')
    ), array('align'=>'horizontal', 'active'=>$page->url('delete', '', 'search')));
  }
  
  private function listings ($type) {
    $published = ($type == 'published') ? 'published != 0' : 'published = 0';
    if (isset($_GET['search'])) {
      $count = 'SELECT COUNT(*)
                FROM blog AS b
                INNER JOIN search AS s ON s.docid = b.id
                WHERE b.page >= 0 AND b.' . $published . ' and s.search MATCH ?';
      $query = 'SELECT b.id, b.url, b.title, b.summary, ABS(b.published)
                FROM blog AS b
                INNER JOIN search AS s ON s.docid = b.id
                WHERE b.page >= 0 AND b.' . $published . ' and s.search MATCH ? ORDER BY b.published ASC';
      $params = array($_GET['search']);
    } else {
      $count = 'SELECT COUNT(*) FROM blog WHERE page >= 0 AND ' . $published;
      $query = 'SELECT id, url, title, summary, ABS(published) FROM blog WHERE page >= 0 AND ' . $published . ' ORDER BY published ASC';
      $params = array();
    }
    return '<h3' . ucwords($type) . '</h3>' . $this->blog($count, $query, $params);
  }
  
  private function blog ($count, $query, $params=array()) {
    global $page, $bp;
    $html = '';
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    if (!$list->count()) $list->count($this->db->value($count, $params));
    $this->db->query($query . $list->limit(), $params);
    while (list($id, $url, $title, $summary, $published) = $this->db->fetch('row')) {
      $html .= $bp->media(array(
        $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>$this->blog['url'] . 'admin/blog/?edit=' . $id)),
        '<h4><a href="' . $this->blog['url'] . $url . '/">' . $title . '</a></h4><p>' . $summary . '</p>',
        date('j M Y @ g:i a', $published)
      ));
    }
    $html .= '<div class="text-center">' . $list->pagination() . '</div>';
    return $html;
  }
  
  private function form () {
    global $page, $bp;
    $html = '';
    if ($this->edit && isset($_GET['delete']) && $_GET['delete'] == 'post') {
      $this->delete($this->edit);
      $page->eject($page->url('delete', '', '?'));
    }
    $page->plugin('Form_Validation');
    $form = new Form('blog_entry');
    $form->required(array('title', 'summary'), false);
    $form->info(array(
      'page' => 'Check this box if you would like to distinguish this page from other posts, and take it out of the loop so to speak.  It will not be posted in any listing pages, rss or atom feeds, and will only ever be linked to at this site if you do so manually yourself.  The only exception is that it will still be included among search results, and in your sitemap.xml.',
      'author_id' => 'Select an author\'s profile to give credit where credit is due.',
      'url' => 'A unique path to this post that should never change once it has been published.<br />If you leave it blank then we will insert our own recommended seo path.',
      'title' => 'An attention grabbing headline.',
      'summary' => 'Encourages a reader to continue with the rest of what you have to say.<br />No markup or newlines are allowed.',
      'post' => 'Your article that may include some html code.',
      'php' => 'Anything fancy that you would like to accomplish with this page.  The code that you $export will be available above as a $php variable.  The only variable imported is \'img\'.',
      'tags' => 'Keywords that a user would search to find what they are looking for.',
      'published' => 'Check here when your content is ready for the primetime.'
    ));
    $values = array();
    if ($this->edit) {
      $this->db->query('SELECT * FROM blog WHERE id = ?', array($this->edit));
      $values = $this->db->fetch('assoc');
      $values['tags'] = $this->tagged($values['tags']);
      $values['page'] = ($values['page'] > 0) ? 'Y' : 'N';
      $values['published'] = ($values['published'] < 0) ? 'Y' : 'N';
      $values['php'] = (file_exists($this->dir . 'plugins/' . $this->edit . '.php')) ? addslashes(htmlspecialchars(file_get_contents($this->dir . 'plugins/' . $this->edit . '.php'))) : '';
    }
    $form->values($values);
    $form->check(array('page'=>'YN', 'author_id'=>'int', 'url'=>'', 'title'=>'', 'summary'=>'', 'tags'=>'an', 'post'=>'', 'published'=>'YN'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $blog = array();
      if ($vars['page'] == 'N') {
        $blog['page'] = 0;
        $blog['author_id'] = $vars['author_id'];
      } else {
        $blog['page'] = 1;
        $blog['author_id'] = 0;
      }
      $blog['url'] = trim($this->seo($vars, $values), '/');
      $blog['title'] = $vars['title'];
      $blog['summary'] = $vars['summary'];
      $tags = array();
      foreach ($vars['tags'] as $tag) {
        if ($row = $this->db->row('SELECT id, tag FROM tags WHERE tag = ?', array($tag))) {
          $tags[$row['id']] = $row['tag'];
        } else {
          $tags[$this->db->insert('tags', array('tag'=>$tag))] = $tag;
        }
      }
      $blog['tags'] = implode(',', array_keys($tags));
      $blog['post'] = $this->code('post');
      $previously = ($this->edit && $values['published'] == 'Y') ? true : false;
      if ($previously && $vars['published'] == 'N') $blog['published'] = 0;
      if (!$previously && $vars['published'] == 'Y') $blog['published'] = -time();
      $blog['updated'] = -time();
      if ($this->edit) {
        $this->db->update('blog', $blog, 'id', $this->edit);
        $id = $this->edit;
      } else {
        $id = $this->db->insert('blog', $blog);
        $eject = $page->url('add', $eject, 'edit', $id);
      }
      $this->db->delete('tagged', 'blog_id', $id);
      if ($vars['published'] == 'Y' && !empty($tags)) {
        $insert = array();
        foreach ($tags as $tid => $tag) $insert[] = array('blog_id'=>$id, 'tag_id'=>$tid);
        $this->db->insert('tagged', $insert);
      }
      $this->update_post_search($id);
      $page->eject($eject);
    }
    $vars = array();
    if ($this->edit) {
      $html .= '<p class="lead">';
        $field = ($values['page'] == 'Y') ? 'Page' : 'Post';
        $html .= 'Edit: <a href="' . $this->blog['url'] . $values['url'] . '/">' . $values['title'] . '</a>';
        $html .= $bp->button('danger pull-right delete', $bp->icon('trash') . ' Delete');
      $html .= '</p><hr>';
      $page->plugin('jQuery', array('code'=>'
        $(".delete").click(function(){
          if (confirm("Are you sure you would like to delete this ' . strtolower($field) . '?")) {
            window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'post')) . '";
          }
        });
      '));
    }
    $html .= $form->header($vars);
    #-- Page --#
    $html .= $form->field('checkbox', 'page', 'Page', array('Y'=>''));
    $page->plugin('jQuery', array('code'=>'
      if ($("#page").is(":checked")) $("#authors").hide();
      $("#page").change(function(){
        if ($(this).is(":checked")) {
          $("#authors").hide();
        } else {
          $("#authors").show();
        }
      });
    '));
    #-- Author --#
    $authors = array();
    $this->db->query('SELECT id, author FROM authors ORDER BY author ASC');
    while (list($id, $author) = $this->db->fetch('row')) $authors[$id] = $author;
    if (!empty($authors)) $html .= '<div id="authors">' . $form->field('select', 'author_id', 'Author', $authors) . '</div>';
    #-- URL --#
    if (!empty($values)) { // we don't offer this option until they have submitted something
      $html .= $form->field('text', 'url', 'URL', array('prepend'=>'/', 'append'=>'/', 'maxlength'=>150));
    }
    #-- Title --#
    $html .= $form->field('text', 'title', 'Title', array('maxlength'=>100));
    #-- Summary --#
    $html .= $form->field('textarea', 'summary', 'Summary', array('rows'=>2));
    #-- Tags --#
    $html .= $form->field('tags', 'tags', 'Tags', array('limit'=>'5'));
    #-- Post (Content) --#
    $html .= $form->field('textarea', 'post', 'Post', array('class'=>'wyciwyg html input-sm', 'rows'=>5, 'spellcheck'=>'false'));
    #-- PHP --#
    if ($this->edit) {
      if (is_admin(1)) {
        $html .= $form->field('textarea', 'php', 'PHP', array('class'=>'wyciwyg noMarkup php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      } elseif (file_exists($this->dir . 'plugins/' . $this->edit . '.php')) {
        $html .= $form->field('textarea', 'php', 'PHP', array('class'=>'wyciwyg noMarkup readOnly php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      }
    }
    #-- Published --#
    if (!isset($values['published']) || $values['published'] == 'N') {
      $html .= $form->field('checkbox', 'published', 'Publish', array('Y'=>''));
    } else { // otherwise we put it under deleted
      $html .= $form->field('checkbox', 'published', 'Published', array('Y'=>'(Uncheck To Change Your Mind)'));
    }
    #-- Submit --#
    if ($this->edit) {
      $html .= $form->buttons('Save Changes');
    } else {
      $html .= $form->buttons('Submit');
      $page->plugin('jQuery', array('code'=>'
        $("#wyciwyg button.send").hide();
      '));
    }
    $html .= $form->close();
    return $html;
  }
  
  private function seo ($vars, $values) {
    if (!empty($vars['url']) && $vars['url'] == $values['url']) return $values['url']; // no changes were made
    if (!empty($vars['url'])) { // a user-submitted url path
      $path = preg_replace('/[^a-z0-9\-\/]/', '', str_replace(array(' ', '_'), '-', strtolower($vars['url'])));
      $path = preg_replace('/[\-\/](?=[\-\/])/', '', $path);
      $path = trim($path, '/');
      $path = trim($path, '-');
    } else {
      $filter = new Validation;
      $path = $filter->seo($vars['title']);
      unset($filter);
    }
    $id = (isset($values['id'])) ? $values['id'] : 0;
    if (!empty($path) && !$this->db->value('SELECT id FROM blog WHERE url = ? AND id != ?', array($path, $id))) return $path;
    $increment = 1;
    if (!empty($path)) $path .= '-';
    while ($this->db->value('SELECT id FROM blog WHERE url = ?', array($path . $increment))) $increment++;
    return $path . $increment;
  }
  
  private function update_post_search ($id) {
    global $page;
    #-- Establish Vars --#
    $blog = $this->blog;
    $blog['user'] = false;
    $blog['admin'] = false;
    $vars = array('blog'=>$blog);
    if (file_exists($this->dir . 'plugins/' . $id . '.php')) {
      $vars['php'] = $page->outreach($this->dir . 'plugins/' . $id . '.php', array('img'=>$this->blog['img']));
    }
    #-- Update Post --#
    $this->db->delete('search', 'docid', $id);
    $row = $this->db->row('SELECT * FROM blog WHERE id = ?', array($id));
    if (empty($row)) return;
    $post = $this->smarty($vars, $row['post']);
    $this->save_resources_used($id, $post);
    if ($row['published']) {
      $search = array();
      $search['docid'] = $id;
      $search['url'] = $row['url'];
      $search['title'] = $row['title'];
      $search['summary'] = $row['summary'];
      $search['post'] = strip_tags($post);
      $search['tags'] = implode(' ', $this->tagged($row['tags']));
      $this->db->insert('search', $search);
    }
  }
  
  private function delete ($id) {
    $this->db->delete('search', 'docid', $id);
    $this->db->delete('blog', 'id', $id);
    $this->db->delete('tagged', 'blog_id', $id);
    $resources = array();
    $this->db->query('SELECT resource_id FROM images WHERE blog_id = ?', array($id));
    while(list($resource) = $this->db->fetch('row')) $resources[] = $resource;
    $this->db->delete('images', 'blog_id', $id);
    foreach ($resources as $id) $this->research($id);
    if (file_exists($this->dir . 'plugins/' . $id . '.php')) unlink($this->dir . 'plugins/' . $id . '.php');
  }
  
}

?>