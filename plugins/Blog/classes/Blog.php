<?php

class Blog {

  protected $user = false;
  protected $admin = false;
  protected $url; // to establish this plugin's url for .js files used in our admin pages
  protected $uri; // for this plugin's default code, variables, cache, etc.
  protected $dir; // for this specific blog's code (if !$this->sub), images, and plugins directories
  protected $sub = false;
  protected $db; // an SQLite object
  protected $blog; // an array of information we pass to smarty templates
  
  public function __construct ($params) {
    global $page;
    #-- Access --#
    if (isset($_SESSION['user_id'])) {
      $this->user = $_SESSION['user_id'];
      if (is_admin(2) || (isset($params['admin']) && in_array($this->user, (array) $params['admin']))) {
        $this->admin = true;
      }
    }
    #-- Paths --#
    $this->url = $params['plugin-url'];
    $this->uri = $params['plugin-uri'];
    $this->dir = BASE_URI . 'blog/';
    if (BASE_URL != $page->get('url') && $page->get('file') == '') { // we've passed the first merry go round, and now we're on to the second
      $this->sub = substr($page->get('url'), strlen(BASE_URL), -1);
      $this->dir .= $this->sub . '/'; // for images and plugins now - no more code!
    }
    if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    #-- Database --#
    $database = BASE_URI . 'blog';
    if ($this->sub) $database .= '.' . str_replace('/', '.', $this->sub);
    $page->plugin('SQLite', 'FTS');
    $this->db = new SQLite($database);
    if ($this->db->created) $this->create_tables();
    #-- Blog Info --#
    $info = $this->db->settings();
    $this->blog = array('page'=>''); // we'll fill this in later @ $this->layout() when we actually know what page we are creating
    $this->blog['url'] = ($page->get('file') != '') ? BASE_URL : $page->get('url'); // the actual (not plugin) blog's url
    $this->blog['img'] = str_replace(BASE_URI, $this->blog['url'], $this->dir . 'resources/');
    $this->blog['name'] = (isset($info['name']) && !empty($info['name'])) ? $info['name'] : '';
    $this->blog['slogan'] = (isset($info['slogan']) && !empty($info['slogan'])) ? $info['slogan'] : '';
    $this->blog['summary'] = (isset($info['summary']) && !empty($info['summary'])) ? $info['summary'] : '';
    if (empty($this->blog['name'])) { // the one thing we absolutely require
      $uri = $page->next_uri();
      if ($this->admin) {
        if ($uri != 'admin') $page->eject($this->blog['url'] . 'admin/setup/');
      } elseif ($this->user) {
        $page->eject(BASE_URL . 'users/logout/');
      } else {
        if ($uri != 'users') $page->eject(BASE_URL . 'users/');
      }
    }
  }
  
  public function layout ($content) { // this method should be protected, but then !is_callable in $page->theme(), so we make it public
    global $bp, $page;
    $count = func_num_args();
    if ($count > 1) { // we are wrapping this class up and delivering it's $content
      $this->blog['page'] = func_get_arg(1);
      if (is_array($content)) {
        $template = ($count > 2) ? func_get_arg(2) : $this->templates('content');
        $content = $this->smarty(array_merge(array('blog'=>$this->blog), $content), $template);
      }
      if (!$page->theme(array($this, 'layout'))) return $this->cache_resources($content);
      return $content; // otherwise we'll cache the resources on the flip side (below)
    }
    $files = array();
    if ($this->blog['page'] == 'admin') {
      if (isset($_GET['preview']) && $_GET['preview'] == 'changes' && isset($_GET['bootstrap'])) {
        $files['variables'] = $_GET['bootstrap'];
      }
      $page->plugin('Bootstrap', 'load');
      $html = $bp->container('content', $content);
    } else {
      $files['variables'] = (file_exists($this->dir . 'variables.less')) ? $this->dir . 'variables.less' : '';
      $files['custom'] = (file_exists($this->dir . 'custom.css')) ? $this->dir . 'custom.css' : '';
      $page->plugin('Bootstrap', 'load', $files);
      $vars = array();
      $vars['blog'] = $this->blog; // remove this and instead just deliver an array of basic blog info
      $vars['php'] = (file_exists($this->dir . 'layout.php')) ? $page->outreach($this->dir . 'layout.php', array('img'=>$this->blog['img'])) : '';
      $export = array();
      $export['header'] = $this->smarty($vars, $this->templates('header'));
      $export['content'] = $content;
      $export['sidebar'] = $this->smarty($vars, $this->templates('sidebar'));
      $export['footer'] = $this->smarty($vars, $this->templates('footer'));
      $html = $this->smarty(array_merge($vars, $export), $this->templates('layout'));
    }
    return $this->cache_resources($html);
  }
  
  protected function smarty ($vars, $template, $testing=false) {
    global $page, $bp;
    static $smarty = null;
    if (empty($template) || !preg_match('/\{\$(.*)}/s', $template)) return ($testing) ? true : $template;
    if ($vars == 'blog') $vars = array('blog'=>$this->blog);
    if (is_null($smarty)) {
      $smarty = $page->plugin('Smarty', 'class');
      $smarty->setCompileDir($this->uri . 'files/smarty/');
    }
    $vars['bp'] = $bp;
    $smarty->assign($vars);
    try {
      $html = $smarty->fetch('string:' . $template);
      $smarty->clearAllAssign();
    } catch (Exception $e) {
      preg_match('/[\s]+\[message[^\]]*\][\s]+=>(.*)/i', print_r($e, true), $message);
      $line = strpos($message[1], ' on ');
      $message = (!empty($message) && $line !== false) ? 'Smarty Syntax Error ' . trim(substr($message[1], $line)) : 'Unknown Smarty Syntax Error';
      if ($testing) return htmlspecialchars_decode($message);
      $html = '<p>' . $message[1] . '</p>';
    }
    return ($testing) ? true : $html;
  }
  
  protected function tagged ($ids) {
    if (empty($ids)) return array();
    if (is_string($ids)) $ids = explode(',', $ids);
    $tags = array_flip($ids);
    $this->db->query('SELECT id, tag FROM tags WHERE id IN (' . implode(', ', $ids) . ')');
    while (list($id, $tag) = $this->db->fetch('row')) $tags[$id] = $tag;
    return $tags;
  }
  
  protected function templates ($name) {
    static $templates = null;
    if (!in_array($name, array('header', 'content', 'sidebar', 'footer', 'layout'))) return '';
    if (is_null($templates)) {
      $templates = array('header'=>'', 'content'=>'', 'sidebar'=>'', 'footer'=>'', 'layout'=>''); // the defaults
      $this->db->query('SELECT name, template FROM templates');
      while ($row = $this->db->fetch('assoc')) $templates[$row['name']] = $row['template'];
      if (empty($templates['content'])) $templates['content'] = file_get_contents($this->uri . 'files/content.tpl');
      if (empty($templates['layout'])) $templates['layout'] = file_get_contents($this->uri . 'files/layout.tpl');
    }
    return $templates[$name];
  }
  
  private function cache_resources ($content) {
    global $page;
    $url = str_replace(array('.', '/'), array('\.', '\/'), BASE_URL);
    preg_match_all('/(' . $url . ')([^\s"]*)\.(jpe?g|gif|png|ico){1}/', $content, $matches);
    if (empty($matches[0])) return $content; // no images on this page
    $cached = $page->plugin('Cache', array('urls'=>array_unique($matches[0])));
    #-- Get Resource ID's --#
    $resources = str_replace(array(BASE_URI, BASE), '', $this->dir . 'resources/');
    $ids = array();
    $length = strlen($resources); // a helper for the next line
    foreach ($matches[2] as $key => $path) if (substr($path, 0, $length) == $resources) $ids[substr($path, $length)] = $matches[0][$key];
    if (!empty($ids)) {
      $this->db->query('SELECT r.id, r.type, r.name, p.name
                        FROM resources AS r
                        LEFT JOIN resources AS p ON r.parent = p.id
                        WHERE r.id IN(' . implode(', ', array_keys($ids)) . ')');
      while (list($id, $type, $name, $parent) = $this->db->fetch('row')) {
        if (empty($name)) $name = $parent;
        $url = $ids[$id];
        $cached[$url] = str_replace("{$id}.{$type}", "{$name}.{$type}", $cached[$url]);
      }
    }
    foreach ($cached as $key => $value) if ($key == $value) unset($cached[$key]); // no need to remove and replace these
    return (!empty($cached)) ? str_replace(array_keys($cached), array_values($cached), $content) : $content;
  }
  
  private function create_tables () {
    $columns = array();
    $columns["id"] = "INTEGER PRIMARY KEY";
    $columns["author"] = "TEXT UNIQUE COLLATE NOCASE";
    $columns["summary"] = "TEXT NOT NULL DEFAULT ''";
    $this->db->create("authors", $columns);
    
    $columns = array();
    $columns["id"] = "INTEGER PRIMARY KEY";
    $columns["category"] = "TEXT UNIQUE COLLATE NOCASE";
    $columns["tags"] = "TEXT NOT NULL DEFAULT ''";
    $this->db->create("categories", $columns);
    
    $columns = array();
    $columns["id"] = "INTEGER PRIMARY KEY";
    $columns["tag"] = "TEXT UNIQUE COLLATE NOCASE";
    $this->db->create("tags", $columns);
    
    $columns = array();
    $columns["id"] = "INTEGER PRIMARY KEY";
    $columns["page"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["url"] = "TEXT UNIQUE COLLATE NOCASE";
    $columns["title"] = "TEXT NOT NULL DEFAULT ''";
    $columns["summary"] = "TEXT NOT NULL DEFAULT ''";
    $columns["post"] = "TEXT NOT NULL DEFAULT ''";
    $columns["tags"] = "TEXT NOT NULL DEFAULT ''";
    $columns["author_id"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["published"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["updated"] = "INTEGER NOT NULL DEFAULT 0";
    $this->db->create("blog", $columns);
    $this->db->index("blog", 1, array("page", "published", "author_id"));
    
    $columns = array();
    $columns["blog_id"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["tag_id"] = "INTEGER NOT NULL DEFAULT 0";
    $this->db->create("tagged", $columns);
    $this->db->index("tagged", 1, "blog_id", "tag_id");
    
    $columns = array();
    $columns["blog_id"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["resource_id"] = "INTEGER NOT NULL DEFAULT 0";
    $this->db->create("images", $columns);
    $this->db->index("images", 1, "blog_id", "resource_id");
    
    $columns = array();
    $columns["id"] = "INTEGER PRIMARY KEY";
    $columns["type"] = "TEXT NOT NULL DEFAULT ''";
    $columns["parent"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["name"] = "TEXT NOT NULL DEFAULT ''";
    $columns["tags"] = "TEXT NOT NULL DEFAULT ''";
    $columns["size"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["width"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["height"] = "INTEGER NOT NULL DEFAULT 0";
    $columns["thumb"] = "TEXT NOT NULL DEFAULT ''";
    $this->db->create("resources", $columns);
    $this->db->index("resources", 1, "parent");
    
    $columns = array();
    $columns["name"] = "TEXT UNIQUE COLLATE NOCASE";
    $columns["template"] = "TEXT NOT NULL DEFAULT ''";
    $this->db->create("templates", $columns);
    
    $fts = new FTS($this->db);
    $fts->create("search", array("url", "title", "summary", "post", "tags"), "porter");
    $fts->create("research", array("resource"), "porter");
  }
  
}

?>