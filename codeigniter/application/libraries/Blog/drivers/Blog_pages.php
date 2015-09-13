<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog_pages extends CI_Driver {
  
  public function index () {
    global $ci, $page;
    $page->enforce($page->url('blog'));
    $breadcrumbs = $this->breadcrumbs();
    $posts = $this->query('listings');
    return $this->export('index', array('breadcrumbs'=>$breadcrumbs, 'posts'=>$posts));
  }
  
  public function search () {
    global $bp, $ci, $page;
    $page->enforce($page->url('blog'));
    $search = urldecode($ci->input->get('search'));
    $breadcrumbs = $this->breadcrumbs(array('Search'=>$page->url('blog') . '?search=' . urlencode($search)));
    $posts = $this->query('search', $search);
    return $this->export('search', array('search'=>$search, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$posts));
  }
  
  public function archives ($params) {
    global $ci, $page;
    list($uri, $Y, $m, $d) = array_pad(array_values($params), 4, '');
    if (!empty($d)) {
      list($from, $to) = $this->range($Y, $m, $d);
      $page->enforce($page->url('blog', $uri, date('/Y/m/d', $from)));
      $archive = array_combine(array('date', 'year', 'month', 'day'), explode(' ', date($from . ' Y F j', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']=>$Y, $archive['month']=>$m, $archive['day']=>$d));
    } elseif (!empty($m)) {
      list($from, $to) = $this->range($Y, $m);
      $page->enforce($page->url('blog', $uri, date('/Y/m', $from)));
      $archive = array_combine(array('date', 'year', 'month'), explode(' ', date($from . ' Y F', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']=>$Y, $archive['month']=>$m));
    } elseif (!empty($Y)) {
      list($from, $to) = $this->range($Y);
      $page->enforce($page->url('blog', $uri, date('/Y', $from)));
      $archive = array_combine(array('date', 'year'), explode(' ', date($from . ' Y', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']=>$Y));
    } else {
      $page->enforce($page->url('blog', $uri));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri));
      $archives = $this->query('archives');
      return $this->export('archives', array('breadcrumbs'=>$breadcrumbs, 'archives'=>$archives));
    }
    $count = 'SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published >= ? AND published <= ?';
    $query = 'SELECT id FROM blog WHERE featured <= 0 AND published >= ? AND published <= ? ORDER BY featured, published ASC';
    $posts = $this->posts($count, $query, array(-$to, -$from));
    return $this->export('archive', array('archive'=>$archive, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function authors ($params) {
    global $ci, $page;
    $uri = $params['method'];
    if (isset($params['uri'])) {
      if (!$row = $this->db->row(array(
        'SELECT COUNT(b.author) AS count, a.id, a.uri, a.author AS name',
        'FROM blog AS b',
        'INNER JOIN authors AS a ON b.author = a.uri',
        'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND b.author = ?',
        'GROUP BY b.author'
      ), array($params['uri']))) $page->eject($page->url('blog', $uri));
      $author = $ci->blog->authors($row['uri'], $row['name']);
      $author['count'] = $row['count'];
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>$uri, $author['name']=>$author['url']));
      $count = 'SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published < 0 AND updated < 0 AND author = ?';
      $query = 'SELECT id FROM blog WHERE featured <= 0 AND published < 0 AND updated < 0 AND author = ? ORDER BY featured, published ASC';
      $posts = $this->posts($author['count'], $query, array($row['uri']));
      return $this->export('author', array('author'=>$author, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // all authors and no posts
      $page->enforce($page->url('blog', $uri));
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>$uri));
      $authors = $this->query('authors');
      return $this->export('authors', array('breadcrumbs'=>$breadcrumbs, 'authors'=>$authors));
    }
  }
  
  public function category ($uri) {
    global $ci, $page;
    $page->enforce($page->url('blog', $uri));
    $hier = $page->plugin('Hierarchy', 'categories', $this->db);
    $path = $hier->path(array('uri', 'category'), array('where'=>'uri = ' . $uri));
    $tree = $hier->tree(array('uri', 'category'), array('where'=>'uri = ' . $uri));
    $counts = $hier->counts('blog', 'category_id');
    foreach ($tree as $id => $fields) $tree[$id]['count'] = $counts[$id];
    $category = array();
    $breadcrumbs = array($ci->blog->name => $page->url('blog'));
    foreach ($path as $path) {
      $category[] = $path['category'];
      $breadcrumbs[$path['category']] = $page->url('blog', $path['uri']);
    }
    $categories = array_keys($tree);
    $count = 'SELECT COUNT(*) FROM blog WHERE category_id IN(' . implode(', ', $categories) . ') AND featured <= 0 AND published != 0';
    $query = 'SELECT id FROM blog WHERE category_id IN(' . implode(', ', $categories) . ') AND featured <= 0 AND published != 0 ORDER BY featured, published ASC';
    $posts = $this->info($this->posts($count, $query));
    $categories = $this->query('categories', array('nest'=>$hier->nestify($tree), 'tree'=>$tree));
    return $this->export('category', array('category'=>$category, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$posts, 'categories'=>$categories));
  }
  
  public function tags ($params) {
    global $ci, $page;
    $uri = $params['method'];
    if (isset($params['uri'])) {
      if (!$tag = $this->db->row(array(
        'SELECT COUNT(t.blog_id) AS count, tags.id, tags.uri, tags.tag AS name',
        'FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'INNER JOIN tags on t.tag_id = tags.id',
        'WHERE b.featured <= 0 AND b.published != 0 AND tags.uri = ?',
        'GROUP BY t.blog_id'
      ), array($params['uri']))) $page->eject($page->url('blog', $uri));
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags', $tag['name']=>$tag['uri']));
      $query = array(
        'SELECT b.id FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'WHERE b.featured <= 0 AND b.published != 0 AND t.tag_id = ?',
        'ORDER BY b.featured, b.published, b.updated ASC'
      );
      $posts = $this->posts($tag['count'], $query, array($tag['id']));
      return $this->export('tag', array('tag'=>$tag['name'], 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // search all tags and get a frequency count
      $page->enforce($page->url('blog', 'tags'));
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags'));
      $tags = $this->query('tags');
      return $this->export('tags', array('breadcrumbs'=>$breadcrumbs, 'tags'=>$tags));
    }
  }
  
  public function post ($row) { // 'id', 'uri', and 'content'
    global $ci, $page;
    if ($row['uri'] != 'index') $page->enforce($row['uri']);
    $post = $this->info($row['id']);
    $breadcrumbs = array($ci->blog->name => $page->url('blog'));
    if (!empty($post['categories'])) {
      foreach ($post['categories'] as $category => $url) $breadcrumbs[$category] = $url;
    }
    $breadcrumbs[$post['title']] = $post['url'];
    if ($page->robots && $post['published'] != 0) $ci->sitemap->save('blog', $post['id'], $post['content']);
    return $this->export('blog', array('breadcrumbs'=>$breadcrumbs, 'post'=>$post));
  }
  
  public function feed () {
    global $ci, $page;
    $page->enforce($page->url('blog', 'feed.xml'));
    $posts = $this->query('listings');
    $xml = trim($this->export('feed', array('posts'=>$posts)));
    $type = substr($xml, -10);
    if (strpos($type, '</rss>')) {
      $ci->output->set_content_type('application/rss+xml');
    } elseif (strpos($type, '</feed>')) {
      $ci->output->set_content_type('application/atom+xml');
    }
    return $xml;
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
    global $bp, $ci;
    $posts = array();
    if (!$bp->listings->set) $bp->listings->count(is_int($count) ? $count : $this->db->value($count, $params));
    if (is_array($query)) $query = implode("\n", $query);
    $this->db->query($query . $bp->listings->limit(), $params);
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    return $posts;
  }
  
  private function breadcrumbs ($links=array()) {
    global $ci, $page;
    $breadcrumbs = array($ci->blog->name => $page->url('blog'));
    $url = trim($page->url('blog'), '/');
    if (!empty($links)) {
      foreach ($links as $name => $uri) {
        $url .= '/' . $uri;
        $breadcrumbs[$name] = $url;
      }
      $breadcrumbs[$name] = $name; // the last one in the set
    }
    return $breadcrumbs;
  }
  
  private function export ($blog, array $vars) {
    global $ci, $page;
    switch ($blog) {
      case 'index':
      case 'search':
      case 'archive':
      case 'author':
      case 'tag':
      case 'category':
        $template = 'listings';
        break;
      case 'archives':
      case 'authors':
      case 'tags':
      case 'blog':
      case 'feed':
        $template = $blog;
        break;
    }
    if ($file = $this->theme($template . '.tpl', $blog)) {
      $ci->sitemap->cache();
      return $ci->blog->smarty($file, $vars);
    }
  }
  
}

/* End of file Blog_pages.php */
/* Location: ./application/libraries/Blog/drivers/Blog_pages.php */