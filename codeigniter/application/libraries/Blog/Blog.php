<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog extends CI_Driver_Library {

  protected $valid_drivers = array();
  private $admin;
  private $controller;
  private $templates;
  private $authors;
  private $post;
  private $config;
  private $blog;
  private $url = '';
  
  public function __construct ($blog) {
    global $admin, $bp, $ci, $page;
    if ($blog['role'] == '#blog#') $this->valid_drivers = array('pages');
    $this->admin = $admin; // An array of values we pass to the $ci->auth class
    unset($admin); // We do not want these values accessible anywhere else
    $this->controller = $blog['role']; // Either '#admin#', '#folder#', '#blog#', or '#post#'
    $this->templates = str_replace('\\', '/', APPPATH) . 'libraries/Blog/theme/';
    $this->authors = BASE_URI . 'blog/authors/';
    $this->post = BASE_URI . 'blog/content/';
    if (!is_dir($this->post)) mkdir($this->post, 0755, true);
    if (!is_file($this->post . 'setup.ini')) file_put_contents($this->post . 'setup.ini', file_get_contents(dirname($this->templates) . '/setup.ini'));
    
    // for backwards compatibility:
    if (is_file($this->post . 'blog.tpl') && is_dir(BASE_URI . 'themes/default/')) {
      rename($this->post . 'blog.tpl', BASE_URI . 'themes/default/blog.tpl');
      if (is_file($this->post . 'archives.tpl')) rename($this->post . 'archives.tpl', BASE_URI . 'themes/default/archives.tpl');
      if (is_file($this->post . 'authors.tpl')) rename($this->post . 'authors.tpl', BASE_URI . 'themes/default/authors.tpl');
      if (is_file($this->post . 'listings.tpl')) rename($this->post . 'listings.tpl', BASE_URI . 'themes/default/listings.tpl');
      if (is_file($this->post . 'post.tpl')) rename($this->post . 'post.tpl', BASE_URI . 'themes/default/post.tpl');
      if (is_file($this->post . 'tags.tpl')) rename($this->post . 'tags.tpl', BASE_URI . 'themes/default/tags.tpl');
    }
    
    $config = parse_ini_file($this->post . 'setup.ini');
    $this->blog = array('name'=>'', 'summary'=>'');
    if (isset($config['blog'])) $this->blog = array_merge($this->blog, $config['blog']);
    unset($config['blog']);
    $this->config = array_merge(array('pagination'=>10), $config);
    $this->blog['bootstrap'] = '3.3.2';
    $page->load(BASE, 'bootstrap/Bootstrap.php');
    $bp = new Bootstrap;
    $this->blog['page'] = trim($this->controller, '#');
    if (empty($this->blog['name']) && $this->controller != '#admin#') $page->eject($page->url('admin'));
  }
  
  public function __get ($name) {
    global $page;
    switch ($name) {
      case 'db':
        if (!isset($this->db)) {
          $this->db = $page->plugin('Database', 'sqlite', BASE_URI . 'databases/blog.db');
          if ($this->db->created || version_compare('1.0.1', $this->db->settings('version'))) {
            $this->db->settings('version', '1.0');
            $this->db->create('blog', array(
              'id' => 'INTEGER PRIMARY KEY',
              'category_id' => 'INTEGER NOT NULL DEFAULT 0',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'seo' => 'TEXT NOT NULL DEFAULT ""',
              'title' => 'TEXT NOT NULL DEFAULT ""',
              'description' => 'TEXT NOT NULL DEFAULT ""',
              'keywords' => 'TEXT NOT NULL DEFAULT ""',
              'theme' => 'TEXT NOT NULL DEFAULT ""',
              'thumb' => 'TEXT NOT NULL DEFAULT ""',
              'featured' => 'INTEGER NOT NULL DEFAULT 0',
              'published' => 'INTEGER NOT NULL DEFAULT 0',
              'updated' => 'INTEGER NOT NULL DEFAULT 0',
              'author' => 'TEXT NOT NULL DEFAULT ""',
              'content' => 'TEXT NOT NULL DEFAULT ""'
            ), array('category_id', 'featured, published, updated, author'));
            $this->db->create('authors', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'author' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""'
            ));
            $this->db->create('categories', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'category' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""',
              'parent' => 'INTEGER NOT NULL DEFAULT 0',
              'level' => 'INTEGER NOT NULL DEFAULT 0',
              'lft' => 'INTEGER NOT NULL DEFAULT 0',
              'rgt' => 'INTEGER NOT NULL DEFAULT 0'
            ));
            $this->db->create('tags', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'tag' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""'
            ));
            $this->db->create('tagged', array(
              'blog_id' => 'INTEGER NOT NULL DEFAULT 0',
              'tag_id' => 'INTEGER NOT NULL DEFAULT 0'
            ), array('unique'=>'blog_id, tag_id'));
          }
        }
        return $this->db;
        break;
      case 'admin':
        list(, $caller) = debug_backtrace(false);
        return ($caller['class'] == 'Auth') ? $this->admin : false;
        break;
      default:
        if (isset($this->$name)) {
          return $this->$name;
        } elseif (isset($this->blog[$name])) {
          return $this->blog[$name];
        } elseif (in_array($name, $this->valid_drivers)) {
          return $this->load_driver($name);
        } else {
          return null;
        }
        break;
    }
  }
  
  public function resources ($media, $page=null) {
    $this->url = $media;
    if ($page) $this->blog['page'] = $page;
  }
  
  public function smarty ($file, $vars=array(), $testing=false) {
    global $bp, $ci, $page;
    $debug = (!$testing && is_admin(2) && $ci->session->enable_profiler) ? true : false;
    if ($debug) {
      $memory = memory_get_usage();
      $start = microtime(true);
      $time = $start - $ci->benchmark->marker['total_execution_time_start'];
    }
    static $smarty = null;
    if (is_null($smarty)) {
      $functions = array('preg_replace', 'number_format', 'implode', 'explode', 'array_keys', 'array_values', 'array_flip', 'array_reverse', 'array_shift', 'array_unshift', 'array_pop', 'array_push', 'array_combine', 'array_merge');
      if ($testing || $this->controller == '#post#') $functions = array_merge(array('is_user', 'is_admin', 'in_group'), $functions);
      $smarty = $page->plugin('Smarty', 'class');
      $smarty->setCompileDir($smarty->getCompileDir() . $page->get('domain'));
      $smarty->assign(array(
        'bp' => new BootstrapClone($bp),
        'page' => new PageClone($page, ($this->controller == '#post#' ? 'post' : 'blog'))
      ));
      $security = new Smarty_Security($smarty);
      $security->php_functions = array_merge(array('isset', 'empty', 'count', 'in_array', 'is_array', 'date', 'time', 'nl2br'), $functions); // Smarty defaults (except date)
      $security->allow_super_globals = false;
      $security->allow_constants = false;
      $smarty->enableSecurity($security);
    }
    unset($vars['bp'], $vars['page']);
    $vars['blog'] = $this->blog;
    $smarty->assign($vars);
    $smarty->setTemplateDir(dirname($file) . '/');
    try {
      $html = $smarty->fetch(basename($file));
      if ($debug) {
        $smarty->loadPlugin('Smarty_Internal_Debug');
        $debug = Smarty_Internal_Debug::display_debug($smarty);
        if (!is_callable('smarty_modifier_debug_print_var')) include SMARTY_PLUGINS_DIR . 'modifier.debug_print_var.php';
        foreach ($debug['vars'] as $key => $obj) {
          if (strtolower($obj->scope) == 'global') {
            unset($debug['vars'][$key]);
          } else {
            $debug['vars'][$key] = smarty_modifier_debug_print_var($obj->value, 0, 80);
          }
        }
        $page->save('Smarty', array(
          'memory' => $memory,
          'file' => $file,
          'start' => $time,
          'time' => microtime(true) - $start,
          'vars' => $debug['vars']
        ));
      }
      if (!empty($vars)) $smarty->clearAssign(array_keys($vars));
    } catch (Exception $e) {
      $error = $e->getMessage();
      if ($testing) return htmlspecialchars_decode($error);
      $html = '<p>' . $error . '</p>';
    }
    return ($testing) ? true : $html;
  }
  
  public function decache ($domain=null) {
    global $page;
    if (empty($domain)) $domain = $page->get('domain');
    $cache = APPPATH . 'cache/' . $domain . '/proceed.txt';
    if (is_file($cache)) unlink($cache);
    $smarty = $page->plugin('Smarty', 'class');
    $path = $smarty->getCompileDir() . $domain;
    list($dirs, $files) = $this->folder($path, 'recursive');
    arsort($dirs);
    foreach ($files as $file) unlink($path . $file);
    foreach ($dirs as $dir) rmdir($path . $dir);
    unset($smarty);
  }
  
  public function query ($type, $params=null) {
    global $bp, $ci, $page;
    $posts = array();
    switch ($type) {
    
      #-- The following are for listings --#
      
      case 'similar': // keywords, limit 5 (order by RANK)
        if (empty($params)) $params = $this->db->row('SELECT id AS exclude, keywords AS tags FROM blog WHERE uri = ?', array($page->get('uri')));
        if (!empty($params)) {
          $id = (is_array($params) && isset($params['exclude'])) ? (int) $params['exclude'] : 0;
          $limit = (is_array($params) && isset($params['limit'])) ? (int) $params['limit'] : 10;
          $tags = (is_array($params) && isset($params['tags'])) ? $params['tags'] : $params;
          if (!is_array($tags)) $tags = array_map('trim', explode(',', $params));
          $search = $ci->sitemap->search('"' . implode('" OR "', $tags) . '"', 'blog', $limit, array(0,0,0,1,0), 'AND u.id != ' . $id);
          foreach ($search as $blog) $posts[] = $blog['id'];
          $posts = $this->info($posts);
        }
        break;
        
      case 'posts': // uris (limit and order inherent)
        foreach ((array) $params as $uri) $posts[$uri] = '';
        $this->db->query('SELECT uri, id FROM blog WHERE uri IN(' . implode(', ', array_fill(0, count($posts), '?')) . ')', array_keys($posts));
        while (list($uri, $id) = $this->db->fetch('row')) $posts[$uri] = $id;
        $posts = $this->info(array_values(array_filter($posts)));
        break;
        
      case 'search': // phrase, limit config (order by RANK) - same as search page
        $term = (string) $params;
        if (!$bp->listings->set) $bp->listings->count($ci->sitemap->count($term, 'blog'));
        $search = $ci->sitemap->search($term, 'blog', $bp->listings->limit());
        $posts = $snippets = array();
        foreach ($search as $blog) {
          $posts[] = $blog['id'];
          $snippets[$blog['id']] = $blog['snippet'];
        }
        $posts = $this->info($posts);
        foreach ($posts as $id => $row) $posts[$id]['snippet'] = $snippets[$id];
        break;
        
      case 'listings': // limit config (order by published ~DESC) - same as index page
        if (!$bp->listings->set) $bp->listings->count($this->db->value('SELECT COUNT(*) FROM blog WHERE featured <= 0 AND published < 0'));
        $this->db->query('SELECT id FROM blog WHERE featured <= 0 AND published < 0 ORDER BY featured, published ASC' . $bp->listings->limit());
        while (list($id) = $this->db->fetch('row')) $posts[] = $id;
        $posts = $this->info($posts);
        break;
      
      #-- The following are for linking --#
      
      case 'archives': // no limit (order by month DESC, and only start if count > 0) - include empty months?
        $years = (is_array($params)) ? $params : array();
        if (empty($years)) {
          $times = $this->db->row('SELECT ABS(MAX(published)) AS begin, ABS(MIN(published)) AS end FROM blog WHERE featured <= 0 AND published < 0');
          if (!is_null($times['end'])) $years = range(date('Y', $times['begin']), date('Y', $times['end']));
        }
        $months = array('Jan'=>1, 'Feb'=>2, 'Mar'=>3, 'Apr'=>4, 'May'=>5, 'Jun'=>6, 'Jul'=>7, 'Aug'=>8, 'Sep'=>9, 'Oct'=>10, 'Nov'=>11, 'Dec'=>12);
        $archives = array();
        foreach ($years as $Y) {
          foreach ($months as $M => $n) {
            $to = mktime(23, 59, 59, $n + 1, 0, $Y) * -1;
            $from = mktime(0, 0, 0, $n, 1, $Y) * -1;
            $archives[] = "SUM(CASE WHEN featured <= 0 AND published >= {$to} AND published <= {$from} THEN 1 ELSE 0 END) AS {$M}{$Y}";
          }
        }
        if (!empty($archives)) {
          $archives = array('SELECT', implode(",\n", $archives), 'FROM blog');
          $archives = $this->db->row($archives);
          foreach ($archives as $date => $count) {
            $time = mktime(0, 0, 0, $months[substr($date, 0, 3)], 15, substr($date, 3));
            list($Y, $M, $m) = explode(' ', date('Y M m', $time));
            if (!isset($posts[$Y])) $posts[$Y] = array('count'=>0, 'url'=>$page->url('blog', 'archives', $Y));
            $posts[$Y]['months'][$M] = array('url'=>$page->url('blog', 'archives', $Y, $m), 'count'=>$count, 'time'=>$time);
            $posts[$Y]['count'] += $count;
          }
        }
        break;
        
      case 'authors': // no limit (order by count DESC, then author ASC)
        $this->db->query(array(
          'SELECT COUNT(*) AS count, a.id, a.uri, a.author AS name',
          'FROM blog AS b',
          'INNER JOIN authors AS a ON b.author = a.uri',
          'WHERE b.featured <= 0 AND b.published < 0 AND b.updated < 0 AND b.author != ""',
          'GROUP BY b.author',
          'ORDER BY a.author ASC'
        ));
        $authors = $this->db->fetch('assoc', 'all');
        $authored = array();
        foreach ($authors as $author) $authored[$author['uri']] = $author['count'];
        arsort($authored);
        if (is_int($params)) $authored = array_slice($authored, 0, $params, true);
        foreach ($authors as $author) {
          if (isset($authored[$author['uri']])) {
            $info = $this->authors($author['uri'], $author['name']);
            $info['count'] = $author['count'];
            $posts[] = $info;
          }
        }
        break;
        
      case 'categories': // no limit (order by count DESC, then category ASC)
        // http://www.smarty.net/docs/en/language.function.function.tpl
        if (is_array($params) && isset($params['nest']) && isset($params['tree'])) {
          foreach ($params['nest'] as $id => $subs) {
            $posts[$id] = array(
              'url' => $page->url('blog', $params['tree'][$id]['uri']),
              'category' => $params['tree'][$id]['category'],
              'count' => $params['tree'][$id]['count']
            );
            if (!empty($subs)) $posts[$id]['subs'] = $this->query('categories', array('nest'=>$subs, 'tree'=>$params['tree']));
          }
          return array_values($posts);
        }
        $hier = $page->plugin('Hierarchy', 'categories', $this->db);
        $tree = $hier->tree(array('uri', 'category'));
        $counts = $hier->counts('blog', 'category_id');
        foreach ($tree as $id => $fields) $tree[$id]['count'] = $counts[$id];
        $nest = $hier->nestify($tree);
        $slice = array();
        foreach ($nest as $id => $subs) if ($tree[$id]['count'] > 0) $slice[$id] = $tree[$id]['count'];
        arsort($slice);
        if (is_int($params)) $slice = array_slice($slice, 0, $params, true);
        foreach ($nest as $id => $subs) if (!isset($slice[$id])) unset($nest[$id]);
        if (!empty($slice)) $posts = $this->query('categories', array('nest'=>$nest, 'tree'=>$tree));
        break;
        
      case 'tags': // no limit (order by count DESC, then tag ASC)
        $this->db->query(array(
          'SELECT COUNT(t.blog_id) AS count, tags.uri, tags.tag AS name',
          'FROM tagged AS t',
          'INNER JOIN blog AS b ON t.blog_id = b.id',
          'INNER JOIN tags ON t.tag_id = tags.id',
          'WHERE b.featured <= 0 AND b.published != 0',
          'GROUP BY tags.id',
          'ORDER BY tags.tag ASC'
        ));
        $tags = $this->db->fetch('assoc', 'all');
        $tagged = array();
        foreach ($tags as $tag) $tagged[$tag['uri']] = $tag['count'];
        arsort($tagged);
        if (is_int($params)) $tagged = array_slice($tagged, 0, $params, true);
        if (count($tagged) > 0) {
          // http://en.wikipedia.org/wiki/Tag_cloud
          // http://stackoverflow.com/questions/18790677/what-algorithm-can-i-use-to-sort-tags-for-tag-cloud?rq=1
          // http://stackoverflow.com/questions/227/whats-the-best-way-to-generate-a-tag-cloud-from-an-array-using-h1-through-h6-fo
          $min = min($tagged);
          $range = max(.01, max($tagged) - $min) * 1.0001;
          foreach ($tags as $tag) {
            if (isset($tagged[$tag['uri']])) {
              $posts[$tag['name']] = array(
                'rank' => ceil(((4 * ($tag['count'] - $min)) / $range) + 1),
                'url' => $page->url('blog', 'tags', $tag['uri']),
                'count' => $tag['count']
              );
            }
          }
        }
        break;
        
    }
    return $posts;
  }
  
  public function file ($uri) {
    global $bp, $ci, $page;
    if (preg_match('/[^a-z0-9-\/]/', $uri)) {
      $seo = $page->seo($uri);
      if (is_dir($this->post . $uri)) rename($this->post . $uri, $this->post . $seo);
      $uri = $seo;
    }
    if (empty($uri)) $uri = 'index';
    $blog = $this->db->row(array(
      'SELECT b.id, b.category_id AS category, c.uri AS path, b.content, b.keywords, b.author, b.updated',
      'FROM blog AS b LEFT JOIN categories AS c ON b.category_id = c.id',
      'WHERE b.uri = ?'
    ), array($uri));
    $file = $this->post . $uri . '/index.tpl';
    if (is_file($file)) {
      $this->resources(BASE_URL . 'blog/content/' . $uri . '/');
      $page->title = '';
      $page->description = '';
      $page->keywords = '';
      $page->robots = true;
      $page->body = '';
      $page->theme = 'default';
      $page->vars = array();
      $content = trim($this->smarty($file));
      if ($page->markdown === true) $content = $bp->md($content);
      unset($page->vars['markdown']);
      $published = $page->published;
      if (is_string($published) && ($date = strtotime($published))) {
        $published = $date * -1; // a post
      } elseif ($published === true) {
        $published = 1; // a page
      } else {
        $published = 0; // unpublished
      }
      $author = (string) $page->author;
      $update = array(
        'category_id' => $this->category($blog, $uri),
        'uri' => $uri,
        'title' => $page->title,
        'description' => $page->description,
        'keywords' => $page->keywords,
        'theme' => $page->theme,
        'thumb' => (string) $page->thumb,
        'author' => (!empty($author)) ? $page->seo($author) : '',
        'featured' => ($page->featured === true) ? -1 : 0,
        'published' => $published,
        'updated' => filemtime($file) * -1,
        'content' => $content
      );
      if (empty($blog)) {
        $update['seo'] = $page->seo($update['title']);
        $blog = array('id' => $this->db->insert('blog', $update));
        if (!empty($update['author'])) $this->author($blog['id'], $author, $update['author']);
        if (!empty($update['keywords'])) $this->tag($blog['id'], $update['keywords']);
      } else {
        foreach (array('updated', 'content') as $check) {
          if ($update[$check] != $blog[$check]) {
            $ci->sitemap->modify('uri', $uri);
            $update['seo'] = $page->seo($update['title']);
            $this->db->update('blog', 'id', array($blog['id']=>$update));
            $this->author($blog['id'], $author, $update['author'], $blog['author']);
            $this->tag($blog['id'], $update['keywords'], $blog['keywords']);
            break;
          }
        }
      }
      return array('id'=>$blog['id'], 'uri'=>$uri, 'content'=>$content);
    } elseif ($blog) { // then remove
      $this->delete($blog['uri']);
    }
    return false;
  }
  
  public function info ($ids) {
    global $ci, $page;
    $single = (is_array($ids)) ? false : true;
    if (empty($ids)) return array();
    $ids = (array) $ids;
    $this->db->query(array(
      'SELECT b.id, b.uri, b.seo, b.title, b.description, b.thumb, ABS(b.featured) AS featured, ABS(b.published) AS published, ABS(b.updated) AS updated, b.content,',
      '  a.id AS author_id, a.uri AS author_uri, a.author AS author_name,',
      '  (SELECT p.uri || "," || p.title FROM blog AS p WHERE p.featured = b.featured AND p.published > b.published AND p.published < 0 ORDER BY p.featured, p.published ASC LIMIT 1) AS previous,',
      '  (SELECT n.uri || "," || n.title FROM blog AS n WHERE n.featured = b.featured AND n.published < b.published AND n.published < 0 ORDER BY n.featured, n.published DESC LIMIT 1) AS next,',
      '  (SELECT GROUP_CONCAT(p.uri) FROM categories AS c INNER JOIN categories AS p WHERE c.lft BETWEEN p.lft AND p.rgt AND c.id = b.category_id ORDER BY c.lft) AS category_uris,',
      '  (SELECT GROUP_CONCAT(p.category) FROM categories AS c INNER JOIN categories AS p WHERE c.lft BETWEEN p.lft AND p.rgt AND c.id = b.category_id ORDER BY c.lft) AS category_names,',
      '  (SELECT GROUP_CONCAT(t.uri) FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_uris,',
      '  (SELECT GROUP_CONCAT(t.tag) FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_names',
      'FROM blog AS b',
      'LEFT JOIN authors AS a ON b.author = a.uri',
      'WHERE b.id IN(' . implode(', ', $ids) . ')'
    ));
    $posts = array_flip($ids);
    while ($row = $this->db->fetch('assoc')) {
      $row['url'] = BASE_URL . $row['uri'];
      $row['uri'] = $row['seo'];
      $row['page'] = true;
      if ($row['published'] > 1) {
        $row['page'] = false;
        $row['archive'] = $page->url('blog', 'archives', date('Y/m/d', $row['published']));
        $row['author'] = (!empty($row['author_id'])) ? $this->authors($row['author_uri'], $row['author_name']) : array();
        if ($row['previous']) {
          $previous = explode(',', $row['previous']);
          $row['previous'] = array('url'=>$page->url('base', array_shift($previous)), 'title'=>implode(',', $previous));
        }
        if ($row['next']) {
          $next = explode(',', $row['next']);
          $row['next'] = array('url'=>$page->url('base', array_shift($next)), 'title'=>implode(',', $next));
        }
      } else {
        unset($row['previous'], $row['next']);
      }
      $row['featured'] = (!empty($row['featured'])) ? true : false;
      $row['categories'] = (!empty($row['category_uris'])) ? array_combine(explode(',', $row['category_names']), explode(',', $row['category_uris'])) : array();
      foreach ($row['categories'] as $category => $url) $row['categories'][$category] = $page->url('blog', $url);
      $row['tags'] = (!empty($row['tag_uris'])) ? array_combine(explode(',', $row['tag_names']), explode(',', $row['tag_uris'])) : array();
      foreach ($row['tags'] as $tag => $url) $row['tags'][$tag] = $page->url('blog', 'tags', $url);
      unset($row['seo'], $row['author_id'], $row['author_uri'], $row['author_name'], $row['category_uris'], $row['category_names'], $row['tag_uris'], $row['tag_names']);
      $posts[$row['id']] = $row;
    }
    return ($single) ? array_shift($posts) : $posts;
  }
  
  public function create ($uri, $content='') {
    if (!is_dir($this->post . $uri)) mkdir($this->post . $uri, 0755, true);
    if (empty($content)) {
      if (is_file(BASE_URI . 'themes/default/default.tpl')) {
        $content = file_get_contents(BASE_URI . 'themes/default/default.tpl');
      } else {
        $content = file_get_contents($this->templates . 'default.tpl');
      }
    }
    if (!is_file($this->post . $uri . '/index.tpl')) file_put_contents($this->post . $uri . '/index.tpl', $content);
    return $this->file($uri);
  }
  
  public function delete ($uri) {
    global $ci;
    $ci->sitemap->modify('uri', $uri, 'delete');
    $path = $this->post . $uri . '/';
    list($dirs, $files) = $this->folder($path);
    foreach ($files as $file) unlink($path . $file);
    if (empty($dirs)) rmdir($path);
    if ($id = $this->db->value('SELECT id FROM blog WHERE uri = ?', array($uri))) {
      $this->db->delete('blog', 'id', $id);
      $this->db->delete('tagged', 'blog_id', $id);
    }
  }
  
  public function rename ($old, $new) {
    global $ci;
    if (is_file($this->post . $new . '/index.tpl')) return false;
    $ci->sitemap->modify('uri', $old);
    $path = $this->post . $old . '/';
    $rename = $this->post . $new . '/';
    list($dirs, $files) = $this->folder($path);
    if (!is_dir($rename)) mkdir($rename, 0755, true);
    foreach ($files as $file) rename($path . $file, $rename . $file);
    if (empty($dirs) && strpos($rename, $path) === false) rmdir($path);
    if ($id = $this->db->value('SELECT id FROM blog WHERE uri = ?', array($old))) {
      $this->db->update('blog', 'id', array($id => array('uri'=>$new)));
    }
    return $this->file($new);
  }
  
  public function folder ($path, $recursive=false, $types=null) {
    $dirs = $files = array();
    if (is_dir($path)) {
      $cut = strlen($path);
      $regex = ($types) ? '/^.+\.(' . $types . ')$/i' : false;
      $dir = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
      if ($recursive) {
        $dir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
        if (is_int($recursive)) $dir->setMaxDepth($recursive);
      }
      foreach ($dir as $file) {
        $path = str_replace('\\', '/', substr($file->getRealpath(), $cut));
        if ($file->isDir()) {
          if (iterator_count($dir->getChildren()) === 0) {
            rmdir($file->getRealpath()); // might as well do some garbage collection while we are at it
          } else {
            $dirs[] = $path;
          }
        } elseif ($types !== false) {
          if ($regex) {
            if (preg_match($regex, $file->getFilename())) $files[] = $path;
          } else {
            $files[] = $path;
          }
        }
      }
    }
    return array($dirs, $files);
  }
  
  public function authors ($uri, $name) {
    global $page;
    $file = $this->authors . $uri . '.ini';
    $author = (is_file($file) && $info = parse_ini_file($file)) ? $info : array();
    $author['uri'] = $uri;
    $author['url'] = $page->url('blog', 'authors', $uri);
    if (!isset($author['name'])) $author['name'] = $name;
    if ($author['name'] != $name) $this->db->update('authors', 'uri', array($uri => array('author'=>$author['name'])));
    $author['thumb'] = (isset($author['thumb']) && is_file($this->authors . $author['thumb'])) ? str_replace(BASE_URI, BASE_URL, $this->authors . $author['thumb']) : '';
    return $author;
  }
  
  private function author ($blog_id, $author, $seo, $former='') {
    global $page;
    if (empty($seo) || $seo == $former) return;
    if (!$this->db->value('SELECT id FROM authors WHERE uri = ?', array($seo))) {
      $this->db->insert('authors', array('uri'=>$seo, 'author'=>$author));
    }
  }
  
  private function category ($blog, $uri) {
    global $page;
    $path = (($slash = strrpos($uri, '/')) !== false) ? substr($uri, 0, $slash) : '';
    $matches = ($blog && $blog['path'] == $path) ? true : false;
    if ($matches) {
      if (empty($path) && $blog['category'] == 0) {
        return 0;
      } elseif (!empty($path) && $blog['category'] > 0) {
        return $blog['category']; // we assume it is correct
      }
    }
    $refresh = false;
    $categories = array(''=>0);
    $this->db->query('SELECT id, uri FROM categories');
    while (list($id, $uri) = $this->db->fetch('row')) $categories[$uri] = $id;
    if (!isset($categories[$path])) {
      $parent = 0;
      $previous = '';
      foreach (explode('/', $path) as $uri) {
        if (!isset($categories[$previous . $uri])) {
          $categories[$previous . $uri] = $this->db->insert('categories', array(
            'uri' => $previous . $uri,
            'category' => ucwords(str_replace('-', ' ', $uri)),
            'parent' => $parent
          ));
          $refresh = true;
        }
        $parent = $categories[$previous . $uri];
        $previous .= $uri . '/';
      }
    }
    if ($blog) $this->db->update('blog', 'id', array($blog['id'] => array('category_id' => $categories[$path])));
    $delete = array();
    foreach ($categories as $uri => $id) if (!is_dir($this->post . $uri)) $delete[] = $id;
    if (!empty($delete)) $this->db->delete('categories', 'id', $delete);
    if (!empty($delete) || $refresh) {
      $hier = $page->plugin('Hierarchy', 'categories', $this->db);
      $hier->refresh('category');
    }
    return $categories[$path];
  }
  
  private function tag ($blog_id, $keywords, $former='') {
    global $page;
    if ($keywords == $former) return;
    $this->db->delete('tagged', 'blog_id', $blog_id);
    if (!empty($keywords)) {
      $insert = array();
      $tags = array_map('trim', explode(',', $keywords));
      foreach ($tags as $tag) {
        $uri = $page->seo($tag);
        if ($tag_id = $this->db->value('SELECT id FROM tags WHERE uri = ?', array($uri))) {
          $insert[$tag_id] = array('blog_id'=>$blog_id, 'tag_id'=>$tag_id);
        } elseif ($tag_id = $this->db->insert('tags', array('uri'=>$uri, 'tag'=>$tag))) {
          $insert[$tag_id] = array('blog_id'=>$blog_id, 'tag_id'=>$tag_id);
        }
      }
      $this->db->insert('tagged', array_values($insert));
    }
  }
  
}

class PageClone {
  
  // http://www.smarty.net/forums/viewtopic.php?p=64771
  // http://www.garfieldtech.com/blog/magic-benchmarks
  
  private $class;
  private $plugin;
  private $methods = array();
  
  public function __construct ($object, $type) {
    $this->class = $object;
    switch ($type) {
      case 'blog':
        $this->plugin = 'blog.php';
        $this->methods = array('url', 'set', 'meta', 'link', 'style', 'script', 'plugin', 'filter');
        break;
      case 'post':
        $this->class->post = array();
        $this->plugin = 'post.php';
        $this->methods = array('url', 'plugin');
        break;
    }
  }
  
  public function __get ($name) {
    $property = $this->class->$name;
    return (is_object($property)) ? null : $property;
  }
  
  public function __call ($name, $arguments) {
    global $ci;
    if ($name == 'post' && $this->plugin == 'post.php') {
      $this->class->post[array_shift($arguments)] = array_shift($arguments);
    } elseif ($name == 'query') {
      return call_user_func_array(array($ci->blog, $name), $arguments);
    } else {
      if ($name == 'plugin') $arguments[0] = array($arguments[0] => $this->plugin);
      if ($name == 'filter' && !in_array($arguments[1], array('prepend', 'append'))) return null;
      $result = (in_array($name, $this->methods)) ? call_user_func_array(array($this->class, $name), $arguments) : null;
      return (is_object($result)) ? null : $result;
    }
  }
  
}

class BootstrapClone {
  
  private $class;
  
  public function __construct ($object) {
    $this->class = $object;
  }
  
  public function __get ($name) {
    $property = $this->class->$name;
    return (is_object($property) && !in_array($name, array('table', 'navbar', 'listings'))) ? null : $property;
  }
  
  public function __call ($name, $arguments) {
    $result = (is_callable(array($this->class, $name))) ? call_user_func_array(array($this->class, $name), $arguments) : null;
    return (is_object($result)) ? null : $result;
  }
  
}

/* End of file Blog.php */
/* Location: ./application/libraries/Blog/Blog.php */