<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog extends CI_Driver_Library {

  public $db; // an SQLite object
  public $template = 'default';
  protected $valid_drivers = array('auth', 'pages', 'thumbs');
  private $blog = array(); // information that will be passed to Smarty templates
  
  public function __construct ($params) {
    global $ci, $page;
    #-- Database --#
    $this->db = $page->plugin('Database', 'sqlite', BASE_URI . 'blog/databases/blog.db');
    if ($this->db->created) $this->create_tables();
    #-- Blog Info --#
    $info = $this->db->settings();
    $this->blog['page'] = $params['page']; // $method;
    $this->blog['name'] = (isset($info['name']) && !empty($info['name'])) ? $info['name'] : '';
    $this->blog['slogan'] = (isset($info['slogan']) && !empty($info['slogan'])) ? $info['slogan'] : '';
    $this->blog['summary'] = (isset($info['summary']) && !empty($info['summary'])) ? $info['summary'] : '';
    $this->blog['img'] = str_replace(BASE_URI, BASE_URL, BASE_URI . 'blog/resources/');
    $this->blog['url'] = trim(BASE_URL . BLOG, '/') . '/';
    $this->blog['uri'] = substr(BASE_URL . $page->get('uri'), strlen($this->blog['url']));
    #-- Setup --#
    if (empty($this->blog['name']) && $this->blog['page'] != '#admin#') $page->eject('admin');
  }
  
  public function get ($var=false) {
    global $ci;
    if (!isset($this->blog['thumb'])) $this->blog['thumb'] = $ci->blog->thumbs->url('blog', 0); // this cannot be called from the constructor so ...
    if ($var === false) return $this->blog; // the whole array
    return (isset($this->blog[$var])) ? $this->blog[$var] : false;
  }
  
  public function feeds () {
    global $page;
    $page->link('<link rel="alternate" type="application/atom+xml" href="' . $this->blog['url'] . 'atom.xml" title="' . $this->blog['name'] . '">');
    $page->link('<link rel="alternate" type="application/rss+xml" href="' . $this->blog['url'] . 'rss.xml" title="' . $this->blog['name'] . '">');
  }
  
  public function smarty ($vars, $template, $testing=false) {
    global $page, $bp;
    static $smarty = null;
    if (empty($template) || strpos($template, '{$') === false) {
      if ($testing) return true;
      if (empty($template) && isset($vars['php'])) return $vars['php']; // no need to run through smarty if we don't have to
      return $template;
    }
    if (is_null($smarty)) $smarty = $page->plugin('Smarty', 'class');
    if ($vars == 'blog') $vars = array('blog'=>$this->blog);
    $vars['bp'] = $bp;
    $smarty->assign($vars);
    try {
      $html = $smarty->fetch('string:' . str_replace("\r\n", "\n", $template)); // for some reason \r\n's causes this to choke sometimes
      $smarty->clearAllAssign();
    } catch (Exception $e) {
      $error = $e->getMessage();
      if ($testing) return htmlspecialchars_decode($error);
      $html = '<p>' . $error . '</p>';
    }
    return ($testing) ? true : $html;
  }
  
  public function tagged ($ids, $key='id') { // or 'url'
    if (empty($ids)) return array();
    if (is_string($ids)) $ids = explode(',', $ids);
    $tags = array_flip($ids);
    $this->db->query('SELECT id, url, tag FROM tags WHERE id IN (' . implode(', ', $ids) . ')');
    while (list($id, $url, $tag) = $this->db->fetch('row')) $tags[$id] = array('url'=>$url, 'name'=>$tag);
    $tagged = array();
    foreach ($tags as $id => $tag) {
      if (!is_array($tag)) continue;
      switch($key) {
        case 'id':  $tagged[$id] = $tag['name']; break;
        case 'url': $tagged[$tag['url']] = $tag['name']; break;
        default:    $tagged[$id] = $tag; break;
      }
    }
    return $tagged;
  }
  
  public function templates ($name='', $template='default') {
    static $templates = array();
    if (!isset($templates[$template])) {
      if ($sections = $this->db->row('SELECT * FROM templates WHERE template = ?', array($template))) {
        $templates[$template] = $sections;
      } else {
        $templates[$template] = ($template == 'default') ? array('template'=>'default', 'variables'=>'', 'custom'=>'', 'header'=>'', 'listings'=>'', 'post'=>'', 'page'=>'', 'tags'=>'', 'authors'=>'', 'archives'=>'', 'sidebar'=>'', 'footer'=>'', 'layout'=>'') : $this->templates();
      }
      foreach (array('variables', 'listings', 'post', 'page', 'tags', 'authors', 'archives', 'layout') as $required) {
        if (empty($templates[$template][$required])) {
          $sections = false; // to trigger an update
          if ($template == 'default') {
            $file = ($required == 'variables') ? 'bootstrap/3.2.0/variables.less' : $required . '.tpl';
            $templates[$template][$required] = file_get_contents(APPPATH . 'libraries/templates/' . $file);
          } else {
            $templates[$template][$required] = $this->templates($required); // the 'default'
          }
        }
      }
      if (empty($sections)) $this->db->insert('templates', array_merge(array('template'=>$template), $templates[$template]), '', 'OR REPLACE');
    }
    if (!empty($name)) return (isset($templates[$template][$name])) ? $templates[$template][$name] : '';
    return $templates[$template];
  }
  
  private function create_tables () {
  
    $this->db->create('authors', array(
      'id' => 'INTEGER PRIMARY KEY',
      'url' => 'TEXT UNIQUE COLLATE NOCASE',
      'author' => 'TEXT NOT NULL DEFAULT ""',
      'summary' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('categories', array(
      'id' => 'INTEGER PRIMARY KEY',
      'url' => 'TEXT UNIQUE COLLATE NOCASE',
      'category' => 'TEXT NOT NULL DEFAULT ""',
      'tags' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('tags', array(
      'id' => 'INTEGER PRIMARY KEY',
      'url' => 'TEXT UNIQUE COLLATE NOCASE',
      'tag' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('tagged', array(
      'blog_id' => 'INTEGER NOT NULL DEFAULT 0',
      'tag_id' => 'INTEGER NOT NULL DEFAULT 0'
    ), array('unique'=>'blog_id, tag_id'));
    
    $this->db->create('blog', array(
      'id' => 'INTEGER PRIMARY KEY',
      'page' => 'INTEGER NOT NULL DEFAULT 0',
      'url' => 'TEXT UNIQUE COLLATE NOCASE',
      'title' => 'TEXT NOT NULL DEFAULT ""',
      'summary' => 'TEXT NOT NULL DEFAULT ""',
      'post' => 'TEXT NOT NULL DEFAULT ""',
      'tags' => 'TEXT NOT NULL DEFAULT ""',
      'author_id' => 'INTEGER NOT NULL DEFAULT 0',
      'published' => 'INTEGER NOT NULL DEFAULT 0',
      'updated' => 'INTEGER NOT NULL DEFAULT 0'
    ), 'page, published, author_id');
    
    $this->db->create('templates', array(
      'template' => 'TEXT UNIQUE COLLATE NOCASE',
      'variables' => 'TEXT NOT NULL DEFAULT ""',
      'custom' => 'TEXT NOT NULL DEFAULT ""',
      'header' => 'TEXT NOT NULL DEFAULT ""',
      'listings' => 'TEXT NOT NULL DEFAULT ""',
      'post' => 'TEXT NOT NULL DEFAULT ""',
      'page' => 'TEXT NOT NULL DEFAULT ""',
      'tags' => 'TEXT NOT NULL DEFAULT ""',
      'authors' => 'TEXT NOT NULL DEFAULT ""',
      'archives' => 'TEXT NOT NULL DEFAULT ""',
      'sidebar' => 'TEXT NOT NULL DEFAULT ""',
      'footer' => 'TEXT NOT NULL DEFAULT ""',
      'layout' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('resources', array(
      'id' => 'INTEGER PRIMARY KEY',
      'type' => 'TEXT NOT NULL DEFAULT ""',
      'parent' => 'INTEGER NOT NULL DEFAULT 0',
      'name' => 'TEXT NOT NULL DEFAULT ""',
      'tags' => 'TEXT NOT NULL DEFAULT ""',
      'size' => 'INTEGER NOT NULL DEFAULT 0',
      'width' => 'INTEGER NOT NULL DEFAULT 0',
      'height' => 'INTEGER NOT NULL DEFAULT 0',
      'thumb' => 'TEXT NOT NULL DEFAULT ""'
    ), 'parent');
    
    $this->db->fts->create('research', array('resource'), 'porter');
    
  }
  
}

/* End of file Blog.php */
/* Location: ./application/libraries/Blog/Blog.php */