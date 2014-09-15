<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_pages extends CI_Driver {

  public function view () {
    global $bp, $ci, $page;
    $ci->load->helper('date');
    $search = '';
    if (isset($_POST['search'])) {
      $eject = (!empty($_POST['search'])) ? $page->url('add', '', 'search', $_POST['search']) : $page->url('delete', '', 'search');
      $page->eject($eject);
    } elseif (isset($_GET['view']) && ($_GET['view'] == 'published' || $_GET['view'] == 'unpublished')) {
      $page->title = 'View ' . ucwords($_GET['view']) . ' Posts and Pages at ' . $this->blog->get('name');
      if ($_GET['view'] == 'published') $search = $bp->search(array('post'=>$page->url()));
      $blog = $this->listings($_GET['view']);
    } else {
      $blog = $this->form();
    }
    #-- Deliver Content --#
    $html = $bp->row('sm', array(
      $bp->col(8, $this->menu()),
      $bp->col(4, $search)
    ));
    $html .= '<br>' . $blog;
    return $this->admin($html);
  }
  
  private function menu () {
    global $page, $bp;
    $url = BASE_URL . ADMIN . '/pages';
    $published = $bp->badge($this->blog->db->value('SELECT COUNT(*) FROM blog WHERE page >= 0 AND published != 0'));
    $unpublished = $bp->badge($this->blog->db->value('SELECT COUNT(*) FROM blog WHERE page >= 0 AND published = 0'));
    return $bp->pills(array(
      'New' => $url,
      'Published ' . $published => $page->url('add', $url, 'view', 'published'),
      'Unpublished ' . $unpublished => $page->url('add', $url, 'view', 'unpublished')
    ), array('align'=>'horizontal', 'active'=>$page->url('delete', '', 'search')));
  }
  
  private function listings ($type) {
    global $bp, $ci, $page;
    $html = '';
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    $posts = array();
    if ($type == 'published' && ($term = $ci->input->get('search'))) {
      if (!$list->count()) $list->count($ci->sitemap->count($term, 'blog'));
      $search = $ci->sitemap->search($term, 'blog', $list->limit());
      foreach ($search as $blog) $posts[] = $blog['id'];
      if (empty($posts)) return $html;
      $where = 'id IN (' . implode(', ', $posts) . ')';
    } else {
      $where = ($type == 'published') ? 'page >= 0 AND published != 0' : 'page >= 0 AND published = 0';
      if (!$list->count()) $list->count($this->blog->db->value('SELECT COUNT(*) FROM blog WHERE ' . $where));
    }
    $order = ($type == 'published') ? 'published' : 'updated';
    $this->blog->db->query('SELECT id, url, title, summary, ABS(updated) FROM blog WHERE ' . $where . ' ORDER BY ' . $order . ' ASC' . $list->limit());
    while (list($id, $url, $title, $summary, $updated) = $this->blog->db->fetch('row')) {
      $thumb = $bp->img($this->blog->thumbs->url('blog', $id), 'width="75" class="pull-right" style="margin-left:10px;"');
      $html .= $bp->media(array(
        $bp->button('xs warning', $bp->icon('pencil') . ' edit', array('href'=>BASE_URL . ADMIN . '/pages?edit=' . $id)) . $thumb,
        '<h4><a href="' . BASE_URL . $url . '/">' . $title . '</a></h4><p>' . $summary . '</p>',
        '<span class="timeago" title="' . date('c', $updated) . '">' . $updated . '</span>'
      ));
    }
    $html .= '<div class="text-center">' . $list->pagination() . '</div>';
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    return $html;
  }
  
  private function form () {
    global $bp, $ci, $page;
    $form = $page->plugin('Form', 'name', 'blog_entry');
    $form->id('post');
    $form->id('php');
    $edit = $ci->input->get('edit');
    if ($edit && ($uri = $this->blog->db->value('SELECT url FROM blog WHERE id = ?', array($edit)))) {
      $ci->load->library('sitemap');
      $ci->sitemap->modify('uri', $uri);
      if (isset($_GET['delete']) && $_GET['delete'] == 'post') {
        $this->delete($edit);
        $page->eject($page->url('delete', '', '?'));
      } elseif (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
        if ($_POST['field'] == $form->id('post')) {
          $post = $this->code('wyciwyg');
          $result = $this->blog->smarty('blog', $post, 'testing');
          if ($result === true) {
            $this->blog->db->update('blog', 'id', array($edit => array('post'=>$post, 'updated'=>-time())));
            echo 'Saved';
          } else {
            echo $result;
          }
          exit;
        } elseif ($_POST['field'] == $form->id('php') && is_admin(1)) {
          $result = $this->file_put_post(BASE_URI . 'blog/plugins/' . $edit . '.php', 'wyciwyg');
          if ($result === true) {
            $this->blog->db->update('blog', 'id', array($edit => array('updated' => -time())));
            echo 'Saved';
          } else {
            echo $result;
          }
          exit;
        }
        echo 'Error';
        exit;
      }
    } else {
      $edit = false;
    }
    $html = '';
    #-- Menus --#
    $form->menu('page', array('Y'=>''));
    $authors = array();
    $this->blog->db->query('SELECT id, author FROM authors ORDER BY author ASC');
    while (list($id, $author) = $this->blog->db->fetch('row')) $authors[$id] = $author;
    $form->menu('author_id', $authors, '&nbsp;');
    $form->menu('published', array('Y' => $form->values('published') == 'Y' ? '(Uncheck To Change Your Mind)' : ''));
    #-- Values --#
    $values = array();
    if ($edit) {
      $this->blog->db->query('SELECT * FROM blog WHERE id = ?', array($edit));
      $values = $this->blog->db->fetch('assoc');
      $values['tags'] = implode(',', $this->blog->tagged($values['tags']));
      $values['page'] = ($values['page'] > 0) ? 'Y' : 'N';
      $values['published'] = ($values['published'] < 0) ? 'Y' : 'N';
      $values['php'] = (file_exists(BASE_URI . 'blog/plugins/' . $edit . '.php')) ? file_get_contents(BASE_URI . 'blog/plugins/' . $edit . '.php') : '';
    }
    $form->values($values);
    #-- Validation --#
    $form->validate('page', 'Page', 'yes_no', 'Check this box if you would like to distinguish this page from other posts, and take it out of the loop so to speak.  It will not be posted in any listing pages, rss or atom feeds, and will only ever be linked to at this site if you do so manually yourself.  The only exception is that it will still be included among search results, and in your sitemap.xml.');
    $form->validate('author_id', 'Author', 'inarray[menu]', 'Select an author\'s profile to give credit where credit is due.');
    $form->validate('url', 'URL', '', 'A unique path to this post that should never change once it has been published.<br />If you leave it blank then we will insert our own recommended seo path.');
    $form->validate('title', 'Title', 'required', 'An attention grabbing headline.');
    $form->validate('summary', 'Summary', 'required', 'Encourages a reader to continue with the rest of what you have to say.<br />No markup or newlines are allowed.');
    $form->validate('tags', 'Tags', '', 'Keywords that a user would search to find what they are looking for.');
    $form->validate('post', 'Post', '', 'Your article that may include some html code.');
    $form->validate('php', 'PHP', '', 'Anything fancy that you would like to accomplish with this page.  The code that you $export will be available above as a {$php} variable.');
    if ($form->values('published') == 'Y') {
      $form->validate('published', 'Published', 'yes_no', 'Uncheck if you are having second thoughts.');
    } else {
      $form->validate('published', 'Publish', 'yes_no', 'Check here when your content is ready for the primetime.');
    }
    #-- Submitted --#
    if ($form->submitted() && empty($form->errors)) {
      $blog = array();
      if ($form->vars['page'] == 'N') {
        $blog['page'] = 0;
        $blog['author_id'] = (int) $form->vars['author_id']; // if !isset then this value is NULL which we don't want
      } else {
        $blog['page'] = 1;
        $blog['author_id'] = 0;
      }
      $blog['url'] = $this->seo($form->vars, $values);
      $blog['title'] = $form->vars['title'];
      $blog['summary'] = $form->vars['summary'];
      $tags = array();
      if (!empty($form->vars['tags'])) {
        $form->vars['tags'] = array_map('trim', explode(',', $form->vars['tags']));
        foreach ($form->vars['tags'] as $tag) {
          $url = $page->seo($tag);
          if ($row = $this->blog->db->row('SELECT id, tag FROM tags WHERE url = ?', array($url))) {
            $tags[$row['id']] = $row['tag'];
          } else {
            $tags[$this->blog->db->insert('tags', array('url'=>$url, 'tag'=>$tag))] = $tag;
          }
        }
      }
      $blog['tags'] = implode(',', array_keys($tags));
      $blog['post'] = $this->code('post');
      $previously = ($edit && $values['published'] == 'Y') ? true : false;
      if ($previously && $form->vars['published'] == 'N') $blog['published'] = 0;
      if (!$previously && $form->vars['published'] == 'Y') $blog['published'] = -time();
      $blog['updated'] = -time();
      if ($edit) {
        $this->blog->db->update('blog', 'id', array($edit => $blog));
        $id = $edit;
      } else {
        $id = $this->blog->db->insert('blog', $blog);
        $form->eject = $page->url('add', $form->eject, 'edit', $id);
      }
      $this->blog->db->delete('tagged', 'blog_id', $id);
      if ($form->vars['published'] == 'Y' && !empty($tags)) {
        $insert = array();
        foreach ($tags as $tid => $tag) $insert[] = array('blog_id'=>$id, 'tag_id'=>$tid);
        $this->blog->db->insert('tagged', $insert);
      }
      $page->eject($form->eject);
    }
    #-- Form --#
    if ($edit) {
      $html .= '<p class="lead">';
        $field = ($values['page'] == 'Y') ? 'Page' : 'Post';
        $html .= $bp->icon('pencil') . ' Edit <span style="margin-left:30px;"><a href="' . BASE_URL . $values['url'] . '/">' . $values['title'] . '</a></span>';
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
    #-- Thumb --#
    if ($edit) $html .= $ci->blog->thumbs->form('blog', $edit);
    $html .= $form->header();
    #-- Page --#
    $html .= $form->label_field('page', 'checkbox');
    $page->plugin('jQuery', 'code', '
      if ($("input[name=page]").is(":checked")) $("#authors").hide();
      $("input[name=page]").change(function(){
        if ($(this).is(":checked")) {
          $("#authors").hide();
        } else {
          $("#authors").show();
        }
      });
    ');
    #-- Author --#
    if (!empty($authors)) $html .= '<div id="authors">' . $form->label_field('author_id', 'select') . '</div>';
    #-- URL --#
    if (!empty($values)) { // we don't offer this option until they have submitted something
      $args = array('prepend'=>'/', 'maxlength'=>150);
      if ($ci->config->item('url_suffix') != '') $args['append'] = $ci->config->item('url_suffix');
      $html .= $form->label_field('url', 'text', $args);
    }
    #-- Title --#
    $html .= $form->label_field('title', 'text', array('maxlength'=>100));
    #-- Summary --#
    $html .= $form->label_field('summary', 'textarea', array('rows'=>2));
    #-- Tags --#
    $html .= $form->label_field('tags', 'tags');
    #-- Post (Content) --#
    $args = array('class'=>'wyciwyg tpl input-sm', 'rows'=>5, 'spellcheck'=>'false');
    if (!$edit) $args['class'] .= ' noSaving';
    $result = $this->blog->smarty('blog', $form->values('post'), 'testing');
    if ($result !== true) $form->errors['post'] = $result;
    $html .= $form->label_field('post', 'textarea', $args);
    #-- PHP --#
    if ($edit) {
      if (is_admin(1)) {
        $html .= $form->label_field('php', 'textarea', array('class'=>'wyciwyg noMarkup php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      } elseif (file_exists(BASE_URI . 'blog/plugins/' . $edit . '.php')) {
        $html .= $form->label_field('php', 'textarea', array('class'=>'wyciwyg noMarkup readOnly php input-sm', 'rows'=>5, 'spellcheck'=>'false'));
      }
    }
    #-- Published --#
    $html .= $form->label_field('published', 'checkbox');
    #-- Wrap Up --#
    $html .= $form->submit($edit ? 'Save Changes' : 'Submit');
    $html .= $form->close();
    return $html;
  }
  
  private function seo ($vars, $values) {
    global $page;
    if (!empty($vars['url']) && $vars['url'] == $values['url']) return $values['url']; // no changes were made
    $url = (!empty($vars['url'])) ? $page->seo($vars['url'], 'slashes') : $page->seo($vars['title'], 'slashes');
    $id = (isset($values['id'])) ? $values['id'] : 0;
    if (!empty($url) && !$this->blog->db->value('SELECT id FROM blog WHERE url = ? AND id != ?', array($url, $id))) return $url;
    $increment = 1;
    if (!empty($url)) $url .= '-';
    while ($this->blog->db->value('SELECT id FROM blog WHERE url = ?', array($url . $increment))) $increment++;
    return $url . $increment;
  }
  
  private function delete ($id) {
    global $ci;
    $ci->sitemap->modify('blog', $id, 'delete');
    $this->blog->db->delete('blog', 'id', $id);
    $this->blog->db->delete('tagged', 'blog_id', $id);
    if (file_exists(BASE_URI . 'blog/plugins/' . $id . '.php')) unlink(BASE_URI . 'blog/plugins/' . $id . '.php');
  }
  
}

/* End of file Admin_pages.php */
/* Location: ./application/libraries/Admin/drivers/Admin_pages.php */