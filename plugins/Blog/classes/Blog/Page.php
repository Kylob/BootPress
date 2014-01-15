<?php

class BlogPage extends Blog {

  public function archives () {
    global $page;
    list($archive, $Y, $m, $d) = explode('/', $page->get('uri') . '///');
    if (!empty($d)) {
      list($from, $to) = $this->range($Y, $m, $d);
      $page->enforce_uri('archives' . date('/Y/m/d/', $from), 301);
      $archive = date('F j, Y', $from);
      list($month, $day, $year) = explode(' ', $archive);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $year=>$Y, $month=>$m, date('j', $from)=>$d));
      $page->title = $archive . ' Archives at ' . $this->blog['name'];
      $page->description = 'All of the blog posts at ' . $this->blog['name'] . ' that were published on ' . $archive . '.';
    } elseif (!empty($m)) {
      list($from, $to) = $this->range($Y, $m);
      $page->enforce_uri('archives' . date('/Y/m/', $from), 301);
      $archive = date('F Y', $from);
      list($month, $year) = explode(' ', $archive);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $year=>$Y, $month=>$m));
      $page->title = $archive . ' Archives at ' . $this->blog['name'];
      $page->description = 'All of the blog posts at ' . $this->blog['name'] . ' that were published in ' . date('F \of Y', $from) . '.';
    } elseif (!empty($Y)) {
      list($from, $to) = $this->range($Y);
      $page->enforce_uri('archives' . date('/Y/', $from), 301);
      $archive = date('Y', $from);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives', $archive=>$Y));
      $page->title = $archive . ' Archives at ' . $this->blog['name'];
      $page->description = 'All of the blog posts at ' . $this->blog['name'] . ' that were published in the year ' . $archive . '.';
    } else {
      $page->enforce_uri('archives/', 301);
      $breadcrumbs = $this->breadcrumbs(array('Archives'=>'archives'));
      $page->title = 'The Archives at ' . $this->blog['name'];
      $page->description = 'All of the blog posts at ' . $this->blog['name'] . '.  Archived in the order they were received.';
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
          $archives[$Y]['months'][$month]['link'] = $this->blog['url'] . 'archives/' . $Y . '/' . str_pad($m, 2, 0, STR_PAD_LEFT) . '/';
          $archives[$Y]['months'][$month]['time'] = $from;
          $total += $count;
        }
        $archives[$Y]['count'] = $total;
        $archives[$Y]['link'] = $this->blog['url'] . 'archives/' . $Y . '/';
      }
      return $this->export('archives', array('archives'=>$archives, 'breadcrumbs'=>$breadcrumbs));
    }
    $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published >= ? AND published <= ?';
    $query = 'SELECT id FROM blog WHERE page = 0 AND published >= ? AND published <= ? ORDER BY page, published ASC';
    $posts = $this->posts($count, $query, array(-$to, -$from));
    return $this->export('archive', array('archive'=>$archive, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
  }
  
  public function authors () {
    global $page;
    $name = $page->next_uri('authors');
    if (!empty($name)) { // an author and their posts
      $author = $this->db->row('SELECT id, author AS name, summary FROM authors WHERE author = ?', array($this->seo_str($name)));
      if (!$author) {
        header('HTTP/1.1 404 Not Found');
        exit;
      }
      $page->enforce_uri('authors/' . $this->str_seo($author['name']) . '/', 301);
      $page->title = 'Author ' . $author['name'] . ' at ' . $this->blog['name'];
      $page->description = 'All of the blog posts at ' . $this->blog['name'] . ' that have been submitted by ' . $author['name'] . '.';
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>'authors', $author['name']=>$this->str_seo($author['name'])));
      $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published != 0 AND author_id = ?';
      $query = 'SELECT id FROM blog WHERE page = 0 AND published != 0 AND author_id = ? ORDER BY page, published ASC';
      $posts = $this->posts($count, $query, array($author['id']));
      return $this->export('author', array('author'=>$author, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // all authors and no posts
      $page->enforce_uri('authors/', 301);
      $page->title = 'Authors at ' . $this->blog['name'];
      $page->description = 'View all of the authors who have submitted blog posts at ' . $this->blog['name'];
      $breadcrumbs = $this->breadcrumbs(array('Authors'=>'authors'));
      $authors = array();
      $this->db->query('SELECT COUNT(*) AS count, a.id, a.author AS name, a.summary
        FROM blog AS b
        INNER JOIN authors AS a ON b.author_id = a.id
        WHERE b.page = 0 AND b.published != 0 AND b.author_id > 0
        GROUP BY b.author_id
        ORDER BY name ASC');
      while ($row = $this->db->fetch('assoc')) {
        $authors[$row['id']] = $row;
        $authors[$row['id']]['url'] = $this->blog['url'] . 'authors/' . $this->str_seo($row['name']) . '/';
      }
      return $this->export('authors', array('authors'=>$authors, 'breadcrumbs'=>$breadcrumbs));
    }
  }
  
  public function atom () {
    global $page;
    $page->enforce_uri('atom.xml', 301);
    $page->plugin('Feeds', 'Atom');
    $posts = array();
    $updated = 0;
    $authors = true; // whether or not all posts have authors associated with them
    $this->db->query('SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC LIMIT 10');
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    $posts = $this->info($posts);
    foreach ($posts as $post) {
      if ($post['updated'] > $updated) $updated = $post['updated'];
      if (empty($post['author'])) $authors = false;
    }
    $atom = new Atom($this->blog['name'], $this->blog['url'], $updated);
    $feed = array();
    $feed['link'] = array(
      'title' => $this->blog['name'],
      'href' => $this->blog['url'],
      'rel' => 'alternate'
    );
    if (empty($authors)) $feed['author'] = array('name'=>'Webmaster');
    if (!empty($this->blog['slogan'])) $feed['subtitle'] = $this->blog['slogan'];
    $atom->feed($feed);
    foreach ($posts as $post) {
      $entry = array();
      if (!empty($post['author'])) $entry['author'] = array('name'=>$post['author']);
      $entry['link'] = array('rel'=>'alternate', 'href'=>$post['url']);
      $entry['summary'] = $post['summary'];
      $entry['published'] = $post['published'];
      $atom->entry($post['title'], $post['url'], $post['updated'], $entry);
    }
    echo $atom->display();
    exit;
  }
  
  public function rss () {
    global $page;
    $page->enforce_uri('rss.xml', 301);
    $page->plugin('Feeds', 'RSS');
    $posts = array();
    $published = 0;
    $updated = 0;
    $this->db->query('SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC LIMIT 10');
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    $posts = $this->info($posts);
    foreach ($posts as $post) {
      if ($post['published'] > $published) $published = $post['published'];
      if ($post['updated'] > $updated) $updated = $post['updated'];
    }
    if (!empty($this->blog['slogan'])) {
      $description = $this->blog['slogan'];
    } elseif (!empty($this->blog['summary'])) {
      $description = $this->blog['summary'];
    } else {
      $description = 'The latest posts from ' . $this->blog['name'];
    }
    $rss = new RSS($this->blog['name'], $this->blog['url'], $description);
    $elements = array();
    $elements['atom:link'] = array('href'=>$this->blog['url'] . 'rss.xml', 'rel'=>'self', 'type'=>'application/rss+xml');
    $elements['pubDate'] = $published;
    $elements['lastBuildDate'] = $updated;
    $rss->channel($elements);
    foreach ($posts as $post) {
      $elements = array();
      $elements['link'] = $post['url'];
      $elements['description'] = $post['summary'];
      $elements['guid'] = array('isPermaLink'=>'true', 'value'=>$post['url']);
      $elements['pubDate'] = $post['published'];
      $rss->item($post['title'], $elements);
    }
    echo $rss->display();
    exit;
  }
  
  public function sitemap () {
    global $page;
    $page->enforce_uri('sitemap.xml', 301);
    header('Content-Type: application/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $this->db->query('SELECT url, ABS(updated) FROM blog WHERE page >= 0 AND published != 0 ORDER BY page, published ASC');
    while (list($url, $updated) = $this->db->fetch('row')) {
      echo '  <url>' . "\n";
      echo '    <loc>' . htmlspecialchars($this->blog['url'] . $url . '/') . '</loc>' . "\n";
      echo '    <lastmod>' . date('Y-m-d', $updated) . '</lastmod>' . "\n";
      echo '  </url>' . "\n";
    }
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($this->blog['url']) . '</loc>' . "\n";
    echo '  </url>' . "\n";
    echo '</urlset>' . "\n";
    exit;
  }
  
  public function tags () {
    global $page;
    $name = $page->next_uri('tags');
    if (!empty($name)) { // search for all published posts with tag $name
      $page->enforce_uri('tags/' . $this->str_seo($name) . '/', 301);
      if ($row = $this->db->row('SELECT id, tag FROM tags WHERE tag = ?', array($name))) {
        $tag = $row['tag'];
        $id = $row['id'];
      } else {
        $tag = strtolower($this->seo_str($name));
        $id = 0;
      }
      $page->title = "Posts Tagged '{$tag}' at {$this->blog['name']}";
      $page->description = "View all posts at {$this->blog['name']} that have been tagged with '{$tag}'";
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags', $tag=>$name));
      $count = 'SELECT COUNT(*) FROM tagged WHERE blog_id > 0 AND tag_id = ?';
      $query = 'SELECT b.id FROM tagged AS t
                INNER JOIN blog AS b ON t.blog_id = b.id
                WHERE t.blog_id > 0 AND t.tag_id = ?
                ORDER BY b.page, b.published ASC';
      $posts = $this->posts($count, $query, array($id));
      return $this->export('tag', array('tag'=>$tag, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } else { // search all tags and get a frequency count
      $page->enforce_uri('tags/', 301);
      $page->title = 'Tag Cloud at ' . $this->blog['name'];
      $page->description = 'View the most frequently used tags at ' . $this->blog['name'];
      $breadcrumbs = $this->breadcrumbs(array('Tags'=>'tags'));
      $tags = $increment = array();
      $this->db->query('SELECT COUNT(b.blog_id), t.tag
                        FROM tagged AS b
                        INNER JOIN tags AS t ON b.tag_id = t.id
                        GROUP BY t.tag
                        ORDER BY t.tag ASC');
      while (list($count, $tag) = $this->db->fetch('row')) {
        $tags[$tag]['url'] = $this->blog['url'] . 'tags/' . $this->str_seo($tag) . '/';
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
  
  public function view () {
    global $page, $bp;
    $uri = trim($page->get('uri'), '/');
    if ($page->next_uri() == 'users' && BASE_URL == $page->get('url')) { // ie. not a sub-blog
      $page->robots = false;
      return $this->layout($page->plugin('Users', array('forms'=>$this->blog['name'])), 'users');
    } elseif ($page->get('file') != '') { // ie. this MAY BE a sub-blog in which case $page->get('file') will be empty
      $folder = substr($page->get('url'), strlen(BASE_URL), -1);
      $template = substr($page->get('file'), 0, -4) . '.tpl';
      $content = $page->plugin('file');
      if (file_exists($template)) {
        $content = $this->smarty(array('php'=>$content), file_get_contents($template));
      }
      return $this->layout($content, $folder);
    } elseif ($category = $this->db->row('SELECT id, category, tags FROM categories WHERE category = ?', array($this->seo_str($uri)))) {
      list($id, $category, $tags) = array_values($category);
      $page->enforce_uri($this->str_seo($category) . '/', 301);
      $page->title = $category . ' at ' . $this->blog['name'];
      $page->description = 'View all of the posts at ' . $this->blog['name'] . ' that have been categorized under ' . $category;
      $page->keywords = implode(', ', $this->tagged($tags));
      $breadcrumbs = $this->breadcrumbs(array($category=>$uri));
      $count = 'SELECT COUNT(*) FROM tagged WHERE blog_id > 0 AND tag_id IN(' . $tags . ')';
      $query = 'SELECT b.id FROM tagged AS t
                INNER JOIN blog AS b ON t.blog_id = b.id
                WHERE t.blog_id > 0 AND t.tag_id IN(' . $tags . ')
                ORDER BY b.page, b.published ASC';
      $posts = $this->posts($count, $query);
      return $this->export('category', array('category'=>$category, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
    } elseif ($post = $this->db->value('SELECT id FROM blog WHERE url = ?', array($uri))) {
      $page->enforce_uri($uri . '/', 301);
      $info = $this->info($post);
      $page->title = $info['title'];
      $page->description = $info['summary'];
      $page->keywords = implode(', ', array_keys($info['tags']));
      $breadcrumbs = array($this->blog['name'] => $this->blog['url']);
      $breadcrumbs[$info['title']] = $info['url'];
      // we do this after the $page->title is set so that it can be changed if desired
      $vars = array('blog'=>$this->blog);
      if (file_exists($this->dir . 'plugins/' . $info['id'] . '.php')) {
        $vars['php'] = $page->outreach($this->dir . 'plugins/' . $info['id'] . '.php', array('img'=>$this->blog['img']));
      }
      $info['content'] = $this->smarty($vars, $info['content']);
      $post = (!$info['page']) ? true : false;
      unset($info['page']);
      if ($post) {
        if ($previous = $this->db->row('SELECT title, url FROM blog WHERE page = 0 AND published > ? AND published != 0 ORDER BY page, published ASC LIMIT 1', array(-$info['published']))) {
          $previous['url'] = $this->blog['url'] . $previous['url'] . '/';
        }
        if ($next = $this->db->row('SELECT title, url FROM blog WHERE page = 0 AND published < ? AND published != 0 ORDER BY page, published DESC LIMIT 1', array(-$info['published']))) {
          $next['url'] = $this->blog['url'] . $next['url'] . '/';
        }
        $posts = (!empty($info['tags'])) ? $this->info($this->similar_posts(array_keys($info['tags']), $info['id'])) : array();
        return $this->export('post', array('post'=>$info, 'breadcrumbs'=>$breadcrumbs, 'previous'=>$previous, 'next'=>$next, 'similar'=>$posts));
      } else { // a page
        return $this->export('page', array('page'=>$info, 'breadcrumbs'=>$breadcrumbs));
      }
    } elseif (empty($uri) || substr($uri, 0, 5) == 'index') {
      $page->enforce_uri('', 301);
      if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = urldecode($_GET['search']);
        $page->title = 'Search: ' . $search . ' at ' . $this->blog['name'];
        $page->description = 'All of the search results at ' . $this->blog['name'] . ' for ' . $search;
        $breadcrumbs = $this->breadcrumbs(array('Search'=>$this->blog['url'] . '?search=' . urlencode($search)));
        $posts = array();
        $fts = new FTS($this->db);
        $list = $bp->listings();
        if (!$list->display()) $list->display(20);
        if (!$list->count()) $list->count($fts->count('search', $search));
        $fts->search('search', $search, $list->limit());
        while (list($id) = $this->db->fetch('row')) $posts[] = $id;
        unset($fts);
        return $this->export('search', array('search'=>$search, 'breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
      } else {
        $page->title = $this->blog['name'];
        if (!empty($this->blog['slogan'])) $page->title .= ' - ' . $this->blog['slogan'];
        $page->description = (!empty($this->blog['summary'])) ? $this->blog['summary'] : 'View all of the posts at ' . $this->blog['name'];
        $breadcrumbs = $this->breadcrumbs(array());
        $count = 'SELECT COUNT(*) FROM blog WHERE page = 0 AND published != 0';
        $query = 'SELECT id FROM blog WHERE page = 0 AND published != 0 ORDER BY page, published ASC';
        $posts = $this->posts($count, $query);
        return $this->export('index', array('breadcrumbs'=>$breadcrumbs, 'posts'=>$this->info($posts)));
      }
    } else {
      header('HTTP/1.1 404 Not Found');
      $page->title = '404 Not Found';
      $page->description = 'Sorry, this page no longer exists.';
      $posts = array();
      $fts = new FTS($this->db);
      $fts->search('search', preg_replace('/(-|\/)/', ' ', $uri), 'LIMIT 0, 10');
      while (list($id) = $this->db->fetch('row')) $posts[] = $id;
      unset($fts);
      return $this->export(404, array('posts'=>$this->info($posts)));
    }
  }
  
  private function similar_posts ($tags, $id) {
    $this->db->db->sqliteCreateFunction('rank', array(&$this, 'rank'), 1);
    $this->db->query('SELECT id FROM blog
                      INNER JOIN (
                        SELECT docid, rank(matchinfo(search)) AS rank FROM search WHERE tags MATCH ?
                      ) AS search ON id = search.docid
                      WHERE search.docid != ?
                      ORDER BY search.rank, published ASC
                      LIMIT 10', array('"' . implode('" OR "', $tags) . '"', $id));
    $posts = array();
    while (list($id) = $this->db->fetch('row')) $posts[] = $id;
    return $posts;
  }
  
  public function rank ($info) {
    $score = (float) 0.0; // the value to return
    $isize = 4; // the amount of string we need to collect for each integer
    $phrases = (int) ord(substr($info, 0, $isize));
    $columns = (int) ord(substr($info, $isize, $isize));
    for ($p=0; $p<$phrases; $p++) {
      $term = substr($info, (2 + $p * $columns * 3) * $isize); // the start of $info for current phrase
      for ($c=0; $c<$columns; $c++) {
        $here = (float) ord(substr($term, (3 * $c * $isize), 1)); // total occurrences in this row and column
        $total = (float) ord(substr($term, (3 * $c + 1) * $isize, 1)); // total occurrences for all rows in this column
        $rows = (float) ord(substr($term, (3 * $c + 2) * $isize, 1)); // total rows with at least one occurence in this column
        if (!empty($here)) $score -= 1 / $rows; // ie. the less common it is, the more weight is given to this occurence (max 1)
      }
    }
    return $score; // order by ASC - the most relevant is the lowest number
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
  
  private function info ($ids, $content=false) {
    $single = (!is_array($ids)) ? true : false;
    if (empty($ids)) return ($single) ? '' : array();
    $content = ($single) ? ' b.post AS content, ' : ' ';
    $ids = (array) $ids;
    $this->db->query('SELECT b.id, b.page, a.author, b.title, b.summary,' . $content . 'b.url, b.tags, ABS(b.published) AS published, ABS(b.updated) AS updated
      FROM blog AS b
      LEFT JOIN authors AS a ON b.author_id = a.id
      WHERE b.id IN(' . implode(', ', $ids) . ')');
    $posts = array_flip($ids);
    $rows = $this->db->fetch('assoc', 'all');
    foreach ($rows as $row) {
      $row['page'] = ($row['page'] == 1) ? true : false;
      $row['url'] = $this->blog['url'] . $row['url'] . '/';
      $row['links'] = array();
      $row['tags'] = (!empty($row['tags'])) ? array_flip($this->tagged($row['tags'])) : array();
      foreach ($row['tags'] as $key => $value) $row['tags'][$key] = $this->blog['url'] . 'tags/' . $this->str_seo($key) . '/';
      if ($this->admin) $row['links']['edit'] = $this->blog['url'] . 'admin/blog/?edit=' . $row['id'];
      if (!$row['page']) {
        $row['links']['archive'] = $this->blog['url'] . 'archives' . date('/Y/m/d/', $row['published']);
        if (!empty($row['author'])) $row['links']['author'] = $this->blog['url'] . 'authors/' . $this->str_seo($row['author']) . '/';
      } else {
        unset($row['author']);
      }
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
  
  private function breadcrumbs ($links) {
    $url = $this->blog['url'];
    $breadcrumbs = array($this->blog['name'] => $url);
    if (!empty($links)) {
      foreach ($links as $key => $value) {
        $url .= $value . '/';
        $breadcrumbs[$key] = $url;
      }
      $breadcrumbs[$key] = $value; // the last one in the set
    }
    return $breadcrumbs;
  }
  
  private function export ($blog, $content) {
    global $page;
    $page->link('<link rel="alternate" type="application/atom+xml" href="' . $this->blog['url'] . 'atom.xml" title="' . $this->blog['name'] . '">');
    $page->link('<link rel="alternate" type="application/rss+xml" href="' . $this->blog['url'] . 'rss.xml" title="' . $this->blog['name'] . '">');
    return $this->layout($content, $blog);
  }
  
}

?>