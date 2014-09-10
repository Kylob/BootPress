<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog_pages extends CI_Driver {

  public function index () {
    global $page;
    $page->enforce($this->get('url'));
    $page->title = $this->get('name');
    if ($this->get('slogan') != '') $page->title .= ' - ' . $this->get('slogan');
    $page->description = ($this->get('summary') != '') ? $this->get('summary') : 'View all of the posts at ' . $this->get('name');
    $breadcrumbs = $this->breadcrumbs();
    $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published != 0';
    $query = 'SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC';
    $posts = $this->posts($count, $query);
    return $this->export('index', array('breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function search () {
    global $bp, $ci, $page;
    $page->enforce($this->get('url'));
    $term = urldecode($_GET['search']);
    $page->title = 'Search: ' . $term . ' at ' . $this->get('name');
    $page->description = 'All of the search results at ' . $this->get('name') . ' for ' . $term;
    $breadcrumbs = $this->breadcrumbs(array('Search'=>$this->get('url') . '?search=' . urlencode($term)));
    $posts = array();
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    if (!$list->count()) $list->count($ci->sitemap->count($term, 'blog'));
    $search = $ci->sitemap->search($term, 'blog', $list->limit());
    foreach ($search as $blog) $posts[] = $blog['id'];
    return $this->export('search', array('search'=>$term, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function atom () {
    global $page;
    $page->enforce($this->get('url') . 'atom.xml');
    $atom = $page->plugin('Feed', 'Atom', array($this->get('name'), $this->get('url'), array(
      'link' => array('title'=>$this->get('name'), 'href'=>$this->get('url'), 'rel'=>'alternate'),
      'subtitle' => $this->get('slogan')
    )));
    $this->db->query('SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC LIMIT 10');
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    $posts = $this->info($posts);
    foreach ($posts as $post) {
      $atom->entry($post['title'], $post['url'], $post['updated'], array(
        'link' => array('rel'=>'alternate', 'href'=>$post['url']),
        'summary' => $post['summary'],
        'published' => $post['published']
      ));
    }
    return $atom->display();
  }
  
  public function rss () {
    global $page;
    $page->enforce($this->get('url') . 'rss.xml');
    if ($this->get('slogan') != '') {
      $description = $this->get('slogan');
    } elseif ($this->get('summary') != '') {
      $description = $this->get('summary');
    } else {
      $description = 'The latest posts from ' . $this->get('name');
    }
    $rss = $page->plugin('Feed', 'RSS', array($this->get('name'), $this->get('url'), $description, array(
      'atom:link' => array('href'=>$this->get('url') . 'rss.xml', 'rel'=>'self', 'type'=>'application/rss+xml')
    )));
    $this->db->query('SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC LIMIT 10');
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    $posts = $this->info($posts);
    foreach ($posts as $post) {
      $rss->item($post['title'], array(
        'link' => $post['url'],
        'description' => $post['summary'],
        'guid' => array('isPermaLink'=>'true', 'value'=>$post['url']),
        'pubDate' => $post['published'],
        'lastBuildDate' => $post['updated']
      ));
    }
    return $rss->display();
  }
  
  public function archives ($params) {
    global $page;
    list($uri, $Y, $m, $d) = array_pad($params, 4, '');
    if (!empty($d)) {
      list($from, $to) = $this->range($Y, $m, $d);
      $page->enforce($this->get('url') . 'archives' . date('/Y/m/d', $from));
      $archive = date('F j, Y', $from);
      list($month, $day, $year) = explode(' ', $archive);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $year=>$Y, $month=>$m, date('j', $from)=>$d));
      $page->title = $archive . ' Archives at ' . $this->get('name');
      $page->description = 'All of the blog posts at ' . $this->get('name') . ' that were published on ' . $archive . '.';
    } elseif (!empty($m)) {
      list($from, $to) = $this->range($Y, $m);
      $page->enforce($this->get('url') . 'archives' . date('/Y/m', $from));
      $archive = date('F Y', $from);
      list($month, $year) = explode(' ', $archive);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $year=>$Y, $month=>$m));
      $page->title = $archive . ' Archives at ' . $this->get('name');
      $page->description = 'All of the blog posts at ' . $this->get('name') . ' that were published in ' . date('F \of Y', $from) . '.';
    } elseif (!empty($Y)) {
      list($from, $to) = $this->range($Y);
      $page->enforce($this->get('url') . 'archives' . date('/Y', $from));
      $archive = date('Y', $from);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $archive=>$Y));
      $page->title = $archive . ' Archives at ' . $this->get('name');
      $page->description = 'All of the blog posts at ' . $this->get('name') . ' that were published in the year ' . $archive . '.';
    } else {
      $page->enforce($this->get('url') . 'archives');
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives'));
      $page->title = 'The Archives at ' . $this->get('name');
      $page->description = 'All of the blog posts at ' . $this->get('name') . '.  Archived in the order they were received.';
      $first = date('Y', $this->db->value('SELECT ABS(published) FROM blog WHERE published < 0 ORDER BY published DESC LIMIT 1'));
      $last = date('Y', $this->db->value('SELECT ABS(published) FROM blog WHERE published < 0 ORDER BY published ASC LIMIT 1'));
      $years = range($last, $first); // ie. Gentiles / Jews
      $months = range(1, 12);
      $archives = array();
      foreach ($years as $Y) {
        $total = 0;
        foreach ($months as $m) {
          list($from, $to) = $this->range($Y, $m);
          $month = date('M', $from);
          $count = $this->db->value('SELECT COUNT(*) FROM blog WHERE page = 0 AND published >= ? AND published <= ?', array(-$to, -$from));
          $archives[$Y]['months'][$month]['count'] = $count;
          $archives[$Y]['months'][$month]['url'] = $this->get('url') . 'archives/' . $Y . '/' . str_pad($m, 2, 0, STR_PAD_LEFT);
          $archives[$Y]['months'][$month]['time'] = $from;
          $total += $count;
        }
        $archives[$Y]['count'] = $total;
        $archives[$Y]['url'] = $this->get('url') . 'archives/' . $Y;
      }
      return $this->export('archives', array('archives'=>$archives, 'breadcrumbs'=>$breadcrumbs));
    }
    $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published >= ? AND published <= ?';
    $query = 'SELECT id FROM blog WHERE page = 0 AND published >= ? AND published <= ? ORDER BY page, published ASC';
    $posts = $this->posts($count, $query, array(-$to, -$from));
    return $this->export('archive', array('archive'=>$archive, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function authors ($params) {
    global $ci, $page;
    $uri = array_shift($params);
    $name = array_shift($params);
    if (!empty($name)) { // an author and their posts
      $author = $this->db->row('SELECT id, url, author AS name, summary FROM authors WHERE url = ?', array($name));
      if (empty($author)) show_404();
      $page->enforce(array($this->get('url') . 'authors', $author['url']));
      $author['thumb'] = $ci->blog->thumbs->url('authors', $author['id'], $author['url']);
      $author['url'] = $this->get('url') . 'authors/' . $author['url'];
      $page->title = 'Author: ' . $author['name'] . ' at ' . $this->get('name');
      $page->description = 'All of the blog posts at ' . $this->get('name') . ' that have been submitted by ' . $author['name'] . '.';
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>'authors', $author['name']=>$author['url']));
      $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published != 0 AND author_id = ?';
      $query = 'SELECT id FROM blog WHERE page = 0 AND published != 0 AND author_id = ? ORDER BY page, published ASC';
      $posts = $this->posts($count, $query, array($author['id']));
      unset($author['id']);
      return $this->export('author', array('author'=>$author, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // all authors and no posts
      $page->enforce($this->get('url') . 'authors');
      $page->title = 'Authors at ' . $this->get('name');
      $page->description = 'View all of the authors who have submitted blog posts at ' . $this->get('name');
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>'authors'));
      $authors = array();
      $this->db->query(array(
        'SELECT COUNT(*) AS count, a.id, a.url, a.author AS name, a.summary',
        'FROM blog AS b',
        'INNER JOIN authors AS a ON b.author_id = a.id',
        'WHERE b.page = 0 AND b.published != 0 AND b.author_id > 0',
        'GROUP BY b.author_id',
        'ORDER BY name ASC'
      ));
      while ($row = $this->db->fetch('assoc')) {
        $authors[] = array(
          'count' => $row['count'],
          'url' => $this->get('url') . 'authors/' . $row['url'],
          'thumb' => $ci->blog->thumbs->url('authors', $row['id'], $row['url']),
          'name' => $row['name'], 
          'summary' => $row['summary']
        );
      }
      return $this->export('authors', array('authors'=>$authors, 'breadcrumbs'=>$breadcrumbs));
    }
  }
  
  public function tags ($params) {
    global $page;
    $uri = array_shift($params);
    $name = array_shift($params);
    if (!empty($name)) { // search for all published posts with tag $name
      $tag = $this->db->row('SELECT id, url, tag AS name FROM tags WHERE url = ?', array($name));
      if (empty($tag)) show_404();
      $page->enforce(array($this->get('url') . 'tags', $tag['url']));
      $page->title = "Posts Tagged '{$tag['name']}' at {$this->get('name')}";
      $page->description = "View all posts at {$this->get('name')} that have been tagged with '{$tag['name']}'";
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags', $tag['name']=>$tag['url']));
      $count = 'SELECT COUNT(*) FROM tagged WHERE blog_id > 0 AND tag_id = ?';
      $query = array(
        'SELECT b.id FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'WHERE t.blog_id > 0 AND t.tag_id = ?',
        'ORDER BY b.page, b.published ASC'
      );
      $posts = $this->posts($count, implode("\n", $query), array($tag['id']));
      return $this->export('tag', array('tag'=>$tag['name'], 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // search all tags and get a frequency count
      $page->enforce($this->get('url') . 'tags');
      $page->title = 'Tag Cloud at ' . $this->get('name');
      $page->description = 'View the most frequently used tags at ' . $this->get('name');
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags'));
      $tags = $increment = array();
      $this->db->query(array(
        'SELECT COUNT(b.blog_id), t.url, t.tag',
        'FROM tagged AS b',
        'INNER JOIN tags AS t ON b.tag_id = t.id',
        'GROUP BY t.id',
        'ORDER BY t.tag ASC'
      ));
      while (list($count, $url, $tag) = $this->db->fetch('row')) {
        $tags[$tag]['url'] = $this->get('url') . 'tags/' . $url;
        $tags[$tag]['count'] = $count;
        $increment[] = $count;
      }
      if (count($increment) > 0) {
        $min = min($increment);
        $increment = (max($increment) - $min) / 5;
        if ($increment == 0) $increment++;
        foreach ($tags as $tag => $links) {
          if ($links['count'] < $min + $increment) {
            $tags[$tag]['rank'] = 1;
          } elseif ($links['count'] < $min + ($increment * 2)) {
            $tags[$tag]['rank'] = 2;
          } elseif ($links['count'] < $min + ($increment * 3)) {
            $tags[$tag]['rank'] = 3;
          } elseif ($links['count'] < $min + ($increment * 4)) {
            $tags[$tag]['rank'] = 4;
          } else {
            $tags[$tag]['rank'] = 5;
          }
        }
      }
      return $this->export('tags', array('tags'=>$tags, 'breadcrumbs'=>$breadcrumbs));
    }
  }
  
  public function category ($row) { // must include id, url, category, and tags
    global $page;
    extract($row);
    $page->enforce($this->get('url') . $url);
    $page->title = $category . ' at ' . $this->get('name');
    $page->description = 'View all of the posts at ' . $this->get('name') . ' that have been categorized under ' . $category;
    $page->keywords = implode(', ', $this->tagged($tags));
    $breadcrumbs = $this->breadcrumbs(array($category=>$url));
    $count = 'SELECT COUNT(*) FROM tagged WHERE blog_id > 0 AND tag_id IN(' . $tags . ')';
    $query = 'SELECT b.id FROM tagged AS t
              INNER JOIN blog AS b ON t.blog_id = b.id
              WHERE t.blog_id > 0 AND t.tag_id IN(' . $tags . ')
              ORDER BY b.page, b.published ASC';
    $posts = $this->posts($count, $query);
    return $this->export('category', array('category'=>$category, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function post ($row) { // must include id and url
    global $ci, $page;
    extract($row);
    $page->enforce($url);
    $info = $this->info($id);
    $page->title = $info['title'];
    $page->description = $info['summary'];
    $page->keywords = implode(', ', array_keys($info['tags']));
    $breadcrumbs = array($this->get('name') => $this->get('url'));
    $breadcrumbs[$info['title']] = $info['url'];
    // we do this after the $page->title is set so that it can be changed if desired
    $vars = array('blog'=>$this->get());
    if (file_exists(BASE_URI . 'blog/plugins/' . $info['id'] . '.php')) {
      $vars['php'] = $page->outreach(BASE_URI . 'blog/plugins/' . $info['id'] . '.php');
    }
    if ($info['published'] > 0) $ci->sitemap->save('blog', $info['id'], $info['content']);
    $info['content'] = $this->smarty($vars, $info['content']);
    $post = (!$info['page']) ? true : false;
    unset($info['page']);
    if ($post) {
      if ($previous = $this->db->row('SELECT title, url FROM blog WHERE page = 0 AND published > ? AND published != 0 ORDER BY page, published ASC LIMIT 1', array(-$info['published']))) {
        $previous['url'] = BASE_URL . $previous['url'];
      }
      if ($next = $this->db->row('SELECT title, url FROM blog WHERE page = 0 AND published < ? AND published != 0 ORDER BY page, published DESC LIMIT 1', array(-$info['published']))) {
        $next['url'] = BASE_URL . $next['url'];
      }
      $posts = (!empty($info['tags'])) ? $this->info($this->similar_posts(array_keys($info['tags']), $info['id'])) : array();
      return $this->export('post', array('post'=>$info, 'breadcrumbs'=>$breadcrumbs, 'previous'=>$previous, 'next'=>$next, 'similar'=>$posts));
    } else { // a page
      return $this->export('page', array('page'=>$info, 'breadcrumbs'=>$breadcrumbs));
    }
  }
  
  private function similar_posts ($tags, $id) {
    global $ci;
    $posts= array();
    $search = $ci->sitemap->search('"' . implode('" OR "', $tags) . '"', 'blog', 10, array(0,0,0,1,0), 'AND u.id != ' . $id);
    foreach ($search as $blog) $posts[] = $blog['id'];
    return $posts;
  }
  
  private function range ($Y, $m=false, $d=false) {
    if (!empty($d)) {
      $from = mktime(0, 0, 0, (int) $m, (int) $d, (int) $Y);
      $to = mktime(23, 59, 59, (int) $m, (int) $d, (int) $Y);
    } elseif (!empty($m)) {
      $from = mktime(0, 0, 0, (int) $m, 1, (int) $Y);
      $to = mktime(23, 59, 59, (int) $m + 1, 0, (int) $Y);
    } else {
      $from = mktime(0, 0, 0, 1, 1, (int) $Y);
      $to = mktime(23, 59, 59, 1, 0, (int) $Y + 1);
    }
    return array($from, $to);
  }
  
  private function posts ($count, $query, $params=array()) {
    global $bp;
    $posts = array();
    $list = $bp->listings();
    if (!$list->display()) $list->display(20);
    if (!$list->count()) $list->count($this->db->value($count, $params));
    $this->db->query($query . $list->limit(), $params);
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    return $posts;
  }
  
  private function info ($ids) {
    global $ci;
    $single = (is_array($ids)) ? false : true;
    if (empty($ids)) return ($single) ? '' : array();
    $ids = (array) $ids;
    $fields = array('b.id', 'b.page', 'b.title', 'b.summary', 'b.url', 'b.tags');
    if ($single) $fields[] = 'b.post AS content';
    $fields[] = 'ABS(b.published) AS published';
    $fields[] = 'ABS(b.updated) AS updated';
    $fields[] = 'a.id AS author_id';
    $fields[] = 'a.url AS author_url';
    $fields[] = 'a.author AS author_name';
    $fields[] = 'a.summary AS author_summary';
    $this->db->query(array(
      'SELECT',
      implode(",\n", $fields),
      'FROM blog AS b',
      'LEFT JOIN authors AS a ON b.author_id = a.id',
      'WHERE b.id IN(' . implode(', ', $ids) . ')'
    ));
    $posts = array_flip($ids);
    $rows = $this->db->fetch('assoc', 'all');
    foreach ($rows as $row) {
      $row['page'] = ($row['page'] == 1) ? true : false;
      $row['thumb'] = $ci->blog->thumbs->url('blog', $row['id'], $row['url']);
      $row['url'] = BASE_URL . $row['url'];
      $row['tags'] = (!empty($row['tags'])) ? array_flip($this->tagged($row['tags'], 'url')) : array();
      foreach ($row['tags'] as $tag => $url) $row['tags'][$tag] = $this->get('url') . 'tags/' . $url;
      if (!$row['page']) {
        $row['author'] = array();
        if (!empty($row['author_id'])) {
          $row['author']['url'] = $this->get('url') . 'authors/' . $row['author_url'];
          $row['author']['thumb'] = $ci->blog->thumbs->url('authors', $row['author_id'], $row['author_url']);
          $row['author']['name'] = $row['author_name'];
          $row['author']['summary'] = $row['author_summary'];
        }
        $row['archive'] = (!empty($row['published'])) ? $this->get('url') . 'archives/' . date('Y/m/d', $row['published']) : '';
      }
      unset($row['author_id'], $row['author_url'], $row['author_name'], $row['author_summary']);
      $posts[$row['id']] = $row;
    }
    return ($single) ? array_shift($posts) : $posts;
  }
  
  private function seo_str ($seo) {
    return str_replace('-', ' ', ucwords($seo)); // str
  }
  
  private function str_seo ($string) {
    return str_replace(' ', '-', strtolower($string)); // seo
  }
  
  private function breadcrumbs ($links=array()) {
    $url = $this->get('url');
    $breadcrumbs = array($this->get('name') => $url);
    if (!empty($links)) {
      foreach ($links as $key => $value) {
        $url .= $value . '/';
        $breadcrumbs[$key] = $url;
      }
      $breadcrumbs[$key] = $value; // the last one in the set
    }
    return $breadcrumbs;
  }
  
  private function export ($blog, $vars) {
    global $ci, $page;
    if ($this->get('page') != '#blog#') show_error('The Blog_pages driver may not be called outside of the controller.');
    $vars['blog'] = $this->get();
    $vars['blog']['page'] = $blog;
    switch ($blog) {
      case 'index':
      case 'search':
      case 'tag':
      case 'category':
      case 'author':
      case 'archive':
        $ci->sitemap->cache(.25); // 15 minutes
        $content = 'listings';
        break;
      case 'post':
      case 'page':
      case 'tags':
      case 'authors':
      case 'archives':
        $ci->sitemap->cache(); // 24 hours
        $content = $blog;
        break;
    }
    if (($preview = $ci->session->native->tempdata('preview_layout')) && is_admin(2)) $this->template = $preview;
    $content = $this->smarty($vars, $this->templates($content, $this->template));
    $post = array('blog'=>$blog);
    if ($blog == 'post' || $blog == 'page') $post['id'] = $vars[$blog]['id'];
    return $page->post($post) . $content;
  }
  
}

/* End of file Blog_pages.php */
/* Location: ./application/libraries/Blog/drivers/Blog_pages.php */