<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog_pages extends CI_Driver {
  
  public function index () {
    global $ci, $page;
    $page->enforce($ci->blog->url['listings']);
    $breadcrumbs = $this->breadcrumbs();
    $count = 'SELECT COUNT(*) FROM blog WHERE published < 0';
    $query = 'SELECT id FROM blog WHERE published < 0 ORDER BY published ASC';
    $posts = $this->posts($count, $query);
    return $this->export('index', array('breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function search () {
    global $bp, $ci, $page;
    $page->enforce($ci->blog->url['listings']);
    $term = urldecode($_GET['search']);
    $breadcrumbs = $this->breadcrumbs(array('Search'=>$ci->blog->url['listings'] . '?search=' . urlencode($term)));
    if (!$bp->listings->set) $bp->listings->count($ci->sitemap->count($term, 'blog'));
    $search = $ci->sitemap->search($term, 'blog', $bp->listings->limit());
    $posts = $snippets = array();
    foreach ($search as $blog) {
      $posts[] = $blog['id'];
      $snippets[$blog['id']] = $blog['snippet'];
    }
    $posts = $this->info($posts);
    foreach ($posts as $id => $value) $posts[$id]['snippet'] = $snippets[$id];
    return $this->export('search', array('search'=>$term, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$posts));
  }
  
  public function atom () {
    global $ci, $page;
    $page->enforce($ci->blog->url['listings'] . 'atom.xml');
    $atom = $page->plugin('Feed', 'Atom', array($ci->blog->name, $ci->blog->url['listings'], array(
      'link' => array('title'=>$ci->blog->name, 'href'=>$ci->blog->url['listings'], 'rel'=>'alternate'),
      'subtitle' => $ci->blog->slogan
    )));
    $this->db->query('SELECT id FROM blog WHERE published < 0 ORDER BY published ASC LIMIT 10');
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
    global $ci, $page;
    $page->enforce($ci->blog->url['listings'] . 'rss.xml');
    if ($ci->blog->slogan != '') {
      $description = $ci->blog->slogan;
    } elseif ($ci->blog->summary != '') {
      $description = $ci->blog->summary;
    } else {
      $description = 'The latest posts from ' . $ci->blog->name;
    }
    $rss = $page->plugin('Feed', 'RSS', array($ci->blog->name, $ci->blog->url['listings'], $description, array(
      'atom:link' => array('href'=>$ci->blog->url['listings'] . 'rss.xml', 'rel'=>'self', 'type'=>'application/rss+xml')
    )));
    $this->db->query('SELECT id FROM blog WHERE published < 0 ORDER BY published ASC LIMIT 10');
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
    global $ci, $page;
    list($uri, $Y, $m, $d) = array_pad(array_values($params), 4, '');
    if (!empty($d)) {
      list($from, $to) = $this->range($Y, $m, $d);
      $page->enforce($ci->blog->url['listings'] . $uri . date('/Y/m/d', $from));
      $archive = array_combine(array('date', 'year', 'month', 'day'), explode(' ', date($from . ' Y F j', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']=>$Y, $archive['month']=>$m, $archive['day']));
    } elseif (!empty($m)) {
      list($from, $to) = $this->range($Y, $m);
      $page->enforce($ci->blog->url['listings'] . $uri . date('/Y/m', $from));
      $archive = array_combine(array('date', 'year', 'month'), explode(' ', date($from . ' Y F', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']=>$Y, $archive['month']));
    } elseif (!empty($Y)) {
      list($from, $to) = $this->range($Y);
      $page->enforce($ci->blog->url['listings'] . $uri . date('/Y', $from));
      $archive = array_combine(array('date', 'year'), explode(' ', date($from . ' Y', $from)));
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>$uri, $archive['year']));
    } else {
      $page->enforce($ci->blog->url['listings'] . 'archives');
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives'));
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
          $count = $this->db->value('SELECT COUNT(*) FROM blog WHERE published >= ? AND published <= ?', array(-$to, -$from));
          $archives[$Y]['months'][$month]['count'] = $count;
          $archives[$Y]['months'][$month]['url'] = $ci->blog->url['listings'] . 'archives/' . $Y . '/' . str_pad($m, 2, 0, STR_PAD_LEFT);
          $archives[$Y]['months'][$month]['time'] = $from;
          $total += $count;
        }
        $archives[$Y]['count'] = $total;
        $archives[$Y]['url'] = $ci->blog->url['listings'] . 'archives/' . $Y;
      }
      return $this->export('archives', array('archives'=>$archives, 'breadcrumbs'=>$breadcrumbs));
    }
    $count = 'SELECT COUNT(*) FROM blog WHERE published >= ? AND published <= ?';
    $query = 'SELECT id FROM blog WHERE published >= ? AND published <= ? ORDER BY published ASC';
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
        'WHERE b.published < 0 AND b.updated < 0 AND b.author = ?',
        'GROUP BY b.author'
      ), array($params['uri']))) $page->eject($ci->blog->url['listings'] . $uri);
      $author = $ci->blog->authors($row['uri'], $row['name']);
      $author['count'] = $row['count'];
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>$uri, $author['name']=>$author['url']));
      $count = 'SELECT COUNT(*) FROM blog WHERE published < 0 AND author = ?';
      $query = 'SELECT id FROM blog WHERE published < 0 AND updated < 0 AND author = ? ORDER BY published ASC';
      $posts = $this->posts($author['count'], $query, array($row['uri']));
      return $this->export('author', array('author'=>$author, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // all authors and no posts
      $page->enforce($ci->blog->url['listings'] . $uri);
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>$uri));
      $authors = array();
      $this->db->query(array(
        'SELECT COUNT(*) AS count, a.id, a.uri, a.author AS name',
        'FROM blog AS b',
        'INNER JOIN authors AS a ON b.author = a.uri',
        'WHERE b.published < 0 AND b.updated < 0 AND b.author != ""',
        'GROUP BY b.author',
        'ORDER BY a.author ASC'
      ));
      while ($row = $this->db->fetch('assoc')) {
        $author = $ci->blog->authors($row['uri'], $row['name']);
        $author['count'] = $row['count'];
        $authors[] = $author;
      }
      return $this->export('authors', array('authors'=>$authors, 'breadcrumbs'=>$breadcrumbs));
    }
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
        'WHERE b.published != 0 AND tags.uri = ?',
        'GROUP BY t.blog_id'
      ), array($params['uri']))) $page->eject($ci->blog->url['listings'] . $uri);
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags', $tag['name']=>$tag['uri']));
      $query = array(
        'SELECT b.id FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'WHERE b.published != 0 AND t.tag_id = ?',
        'ORDER BY b.published, b.updated ASC'
      );
      $posts = $this->posts($tag['count'], $query, array($tag['id']));
      return $this->export('tag', array('tag'=>$tag['name'], 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // search all tags and get a frequency count
      $page->enforce($ci->blog->url['listings'] . 'tags');
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags'));
      $tags = $increment = array();
      $this->db->query(array(
        'SELECT COUNT(t.blog_id), tags.uri, tags.tag',
        'FROM tagged AS t',
        'INNER JOIN blog AS b ON t.blog_id = b.id',
        'INNER JOIN tags ON t.tag_id = tags.id',
        'WHERE b.published != 0',
        'GROUP BY tags.id',
        'ORDER BY tags.tag ASC'
      ));
      while (list($count, $uri, $tag) = $this->db->fetch('row')) {
        $tags[$tag]['url'] = $ci->blog->url['listings'] . 'tags/' . $uri;
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
  
  public function category ($row) { // 'id', 'uri', 'category', and 'tags'
    global $ci, $page;
    $page->enforce($ci->blog->url['listings'] . $row['uri']);
    $breadcrumbs = $this->breadcrumbs(array($row['category'] => $row['uri']));
    $count = array(
      'SELECT COUNT(*) FROM tagged AS t',
      'INNER JOIN blog AS b ON t.blog_id = b.id',
      'WHERE b.published != 0 AND t.blog_id > 0 AND t.tag_id IN(' . $row['tags'] . ')'
    );
    $query = array(
      'SELECT b.id FROM tagged AS t',
      'INNER JOIN blog AS b ON t.blog_id = b.id',
      'WHERE b.published != 0 AND t.blog_id > 0 AND t.tag_id IN(' . $row['tags'] . ')',
      'ORDER BY b.published ASC'
    );
    $posts = $this->posts($count, $query);
    return $this->export('category', array('category'=>$row['category'], 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function post ($row) { // 'id', 'uri', and 'content'
    global $ci, $page;
    if ($row['uri'] != 'index') $page->enforce($row['uri']);
    $info = $this->info($row['id']);
    $info['content'] = $row['content'];
    unset($row); // we are done with this now
    $breadcrumbs = array($ci->blog->name => $ci->blog->url['listings']);
    $breadcrumbs[$info['title']] = $info['url'];
    if ($page->robots && $info['published'] != 0) $ci->sitemap->save('blog', $info['id'], $info['content']);
    if ($info['published']) {
      $vars = array('post'=>$info, 'breadcrumbs'=>$breadcrumbs, 'previous'=>array(), 'next'=>array(), 'similar'=>array());
      if ($previous = $this->db->row('SELECT uri, title FROM blog WHERE published > ? AND published != 0 ORDER BY published ASC LIMIT 1', array(-$info['published']))) {
        $previous['url'] = BASE_URL . $previous['uri'];
        $vars['previous'] = $previous;
      }
      if ($next = $this->db->row('SELECT uri, title FROM blog WHERE published < ? AND published != 0 ORDER BY published DESC LIMIT 1', array(-$info['published']))) {
        $next['url'] = BASE_URL . $next['uri'];
        $vars['next'] = $next;
      }
    } else {
      $vars = array('post'=>$info, 'breadcrumbs'=>$breadcrumbs);
    }
    return $this->export('post', $vars);
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
  
  private function seo_str ($seo) {
    return str_replace('-', ' ', ucwords($seo)); // str
  }
  
  private function str_seo ($string) {
    return str_replace(' ', '-', strtolower($string)); // seo
  }
  
  private function breadcrumbs ($links=array()) {
    global $ci;
    $url = $ci->blog->url['listings'];
    $breadcrumbs = array($ci->blog->name => $url);
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
    if ($ci->blog->controller != '#blog#') show_error('The Blog_pages driver may not be called outside of the controller.');
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
    $ci->blog->set($blog);
    if (is_file($ci->blog->post . $content . '.tpl')) {
      $content = $ci->blog->smarty($ci->blog->post . $content . '.tpl', $vars);
    } else {
      $content = $ci->blog->smarty($ci->blog->templates . $content . '.tpl', $vars);
    }
    return $content;
  }
  
}

/* End of file Blog_pages.php */
/* Location: ./application/libraries/Blog/drivers/Blog_pages.php */