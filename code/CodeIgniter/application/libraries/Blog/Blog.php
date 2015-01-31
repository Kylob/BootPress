<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog extends CI_Driver_Library {

  protected $valid_drivers = array('pages');
  private $admin;
  private $controller;
  private $templates;
  private $authors;
  private $post;
  private $config;
  private $blog;
  
  public function __construct ($blog) {
    global $admin, $bp, $page;
    $this->admin = $admin; // An array of values we pass to the $ci->auth class
    unset($admin); // We do not want these values accessible anywhere else
    $this->controller = $blog['role']; // Either '#admin#', '#folder#', '#blog#', or '#post#'
    $this->templates = APPPATH . 'libraries/templates/';
    $this->authors = BASE_URI . 'blog/authors/';
    $this->post = BASE_URI . 'blog/content/';
    if (!is_dir($this->post)) mkdir($this->post, 0755, true);
    #-- Blog --#
    $this->blog = array('name'=>'', 'slogan'=>'', 'summary'=>'');
    if (!is_file($this->post . 'setup.php')) file_put_contents($this->post . 'setup.php', file_get_contents($this->templates . 'setup.php'));
    include($this->post . 'setup.php');
    if (isset($config['blog'])) $this->blog = array_merge($this->blog, $config['blog']);
    unset($config['blog']);
    $this->config = array_merge(array('pagination'=>10, 'php_functions'=>null, 'page_plugins'=>null), $config);
    if (isset($config['bootstrap']) && is_dir(BASE . 'bootstrap/' . $config['bootstrap'])) {
      $this->blog['bootstrap'] = $config['bootstrap'];
    } else {
      $this->blog['bootstrap'] = '3.3.1';
    }
    $version = ($this->controller == '#admin#') ? '3.3.1' : $this->blog['bootstrap'];
    $page->load(BASE, 'bootstrap/' . $version . '/BootPress.php');
    $bp = new BootPress;
    $this->blog['page'] = ''; // This is established in the $ci->blog->pages class
    $this->blog['url'] = array(
      'base' => BASE_URL,
      'listings' => trim(BASE_URL . BLOG, '/') . '/',
      'media' => ''
    );
    if (empty($this->blog['name']) && $this->controller != '#admin#') $page->eject(ADMIN);
  }
  
  public function __get ($name) {
    global $page;
    switch ($name) {
      case 'db':
        if (!isset($this->db)) {
          $this->db = $page->plugin('Database', 'sqlite', BASE_URI . 'blog/databases/blog.db');
          if ($this->db->created) {
          
            $this->db->create('blog', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'seo' => 'TEXT NOT NULL DEFAULT ""',
              'title' => 'TEXT NOT NULL DEFAULT ""',
              'description' => 'TEXT NOT NULL DEFAULT ""',
              'keywords' => 'TEXT NOT NULL DEFAULT ""',
              'theme' => 'TEXT NOT NULL DEFAULT ""',
              'thumb' => 'TEXT NOT NULL DEFAULT ""',
              'author' => 'TEXT NOT NULL DEFAULT ""',
              'published' => 'INTEGER NOT NULL DEFAULT 0',
              'updated' => 'INTEGER NOT NULL DEFAULT 0',
              'content' => 'TEXT NOT NULL DEFAULT ""'
            ), 'published, updated, author');
            
            $this->db->create('authors', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'author' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""'
            ));
            
            $this->db->create('categories', array(
              'id' => 'INTEGER PRIMARY KEY',
              'uri' => 'TEXT UNIQUE COLLATE NOCASE',
              'category' => 'TEXT NOT NULL COLLATE NOCASE DEFAULT ""',
              'tags' => 'TEXT NOT NULL DEFAULT ""'
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
      case 'admin': return ($this->controller == '#admin#') ? $this->admin : false; break;
      case 'listings': return $this->blog['url']['listings']; break;
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
  
  public function set ($page, $media='') {
    $this->blog['page'] = $page;
    $this->blog['url']['media'] = $media;
  }
  
  public function smarty ($file, $vars=array(), $testing=false) {
    global $bp, $ci, $page;
    static $smarty = null;
    if (is_null($smarty)) {
      $smarty = $page->plugin('Smarty', 'class');
      $smarty->assign(array(
        'bp' => $bp,
        'page' => new PageClone($page, array('set', 'meta', 'link', 'plugin', 'filter'))
      ));
      $security = new Smarty_Security($smarty);
      $security->php_functions = $this->config['php_functions'];
      $smarty->enableSecurity($security);
    }
    unset($vars['page'], $vars['bp']);
    $vars['blog'] = $this->blog;
    $smarty->assign($vars);
    $smarty->setTemplateDir(dirname($file) . '/');
    try {
      $html = $smarty->fetch(basename($file));
      if (!empty($vars)) $smarty->clearAssign(array_keys($vars));
    } catch (Exception $e) {
      $error = $e->getMessage();
      if ($testing) return htmlspecialchars_decode($error);
      $html = '<p>' . $error . '</p>';
    }
    return ($testing) ? true : $html;
  }
  
  public function file ($uri) {
    global $ci, $page;
    if (preg_match('/[^a-z0-9-\/]/', $uri)) {
      $seo = $page->seo($uri);
      if (is_dir($this->post . $uri)) rename($this->post . $uri, $this->post . $seo);
      $uri = $seo;
    }
    if (empty($uri)) $uri = 'index';
    $blog = $this->db->row('SELECT id, keywords, author, updated FROM blog WHERE uri = ?', array($uri));
    $file = $this->post . $uri . '/index.tpl';
    if (is_file($file)) {
      $this->set('file', BASE_URL . 'blog/content/' . $uri . '/');
      $content = $this->smarty($file);
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
        'uri' => $uri,
        'title' => $page->title,
        'description' => $page->description,
        'keywords' => $page->keywords,
        'theme' => $page->theme,
        'thumb' => (string) $page->thumb,
        'author' => (!empty($author)) ? $page->seo($author) : '',
        'published' => $published,
        'updated' => filemtime($file) * -1,
        'content' => $content
      );
      if (empty($blog)) {
        $update['seo'] = $page->seo($update['title']);
        $id = $this->db->insert('blog', $update);
        if (!empty($update['author'])) $this->author($id, $author, $update['author']);
        if (!empty($update['keywords'])) $this->tag($id, $update['keywords']);
        return array('id'=>$id, 'uri'=>$uri, 'content'=>$content);
      } elseif ($update['updated'] != $blog['updated']) {
        $ci->sitemap->modify('uri', $uri);
        $update['seo'] = $page->seo($update['title']);
        $this->db->update('blog', 'id', array($blog['id']=>$update));
        $this->author($blog['id'], $author, $update['author'], $blog['author']);
        $this->tag($blog['id'], $update['keywords'], $blog['keywords']);
      }
      return array('id'=>$blog['id'], 'uri'=>$uri, 'content'=>$content);
    } elseif ($blog) { // then remove
      $this->delete($blog['uri']);
    }
    return false;
  }
  
  public function info ($ids) {
    global $ci;
    $single = (is_array($ids)) ? false : true;
    if (empty($ids)) return array();
    $ids = (array) $ids;
    $this->db->query(array(
      'SELECT b.id, b.uri, b.seo, b.title, b.description, b.thumb, ABS(b.published) AS published, ABS(b.updated) AS updated, b.content,',
      '  a.id AS author_id, a.uri AS author_uri, a.author AS author_name,',
      '  (SELECT GROUP_CONCAT(t.uri) FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_uris,',
      '  (SELECT GROUP_CONCAT(t.tag) FROM tagged INNER JOIN tags AS t ON tagged.tag_id = t.id WHERE tagged.blog_id = b.id) AS tag_names',
      'FROM blog AS b',
      'LEFT JOIN authors AS a ON b.author = a.uri',
      'WHERE b.id IN(' . implode(', ', $ids) . ')'
    ));
    $posts = array_flip($ids);
    while ($row = $this->db->fetch('assoc')) {
      $row['url'] = BASE_URL . $row['uri'];
      $row['page'] = true;
      if ($row['published'] > 1) {
        $row['page'] = false;
        $row['tags'] = (!empty($row['tag_uris'])) ? array_combine(explode(',', $row['tag_names']), explode(',', $row['tag_uris'])) : array();
        foreach ($row['tags'] as $tag => $url) $row['tags'][$tag] = $this->blog['url']['listings'] . 'tags/' . $url;
        $row['author'] = (!empty($row['author_id'])) ? $this->authors($row['author_uri'], $row['author_name']) : array();
        $row['archive'] = $this->blog['url']['listings'] . 'archives/' . date('Y/m/d', $row['published']);
      }
      $row['uri'] = $row['seo']; // we are just swapping out the uri for a more practical value now
      unset($row['seo'], $row['author_id'], $row['author_uri'], $row['author_name'], $row['tag_uris'], $row['tag_names']);
      $posts[$row['id']] = $row;
    }
    return ($single) ? array_shift($posts) : $posts;
  }
  
  public function create ($uri, $content='') {
    if (!is_dir($this->post . $uri)) mkdir($this->post . $uri, 0755, true);
    if (empty($content)) $content = file_get_contents($this->templates . 'blog.tpl');
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
    $author = array();
    if (is_file($this->authors . $uri . '.php')) {
      $info = include $this->authors . $uri . '.php';
      if (is_array($info)) $author = $info;
    }
    $author['uri'] = $uri;
    $author['url'] = $this->blog['url']['listings'] . 'authors/' . $uri;
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
  private $methods = array();
  
  public function __construct ($object, $methods=array()) {
    $this->class = $object;
    $this->methods = $methods;
  }
  
  public function __get ($name) {
    $property = $this->class->$name;
    return (is_object($property)) ? null : $property;
  }
  
  public function __call ($name, $arguments) {
    global $ci;
    if ($name == 'filter' && !in_array($arguments[1], array('prepend', 'append'))) return null;
    if ($name == 'plugin' && !in_array($arguments[0], (array) $ci->blog->config['page_plugins'])) return null;
    $result = (in_array($name, $this->methods)) ? call_user_func_array(array($this->class, $name), $arguments) : null;
    return (is_object($result)) ? null : $result;
  }
  
}


/* End of file Blog.php */
/* Location: ./application/libraries/Blog/Blog.php */