<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sitemap extends CI_Driver_Library {

  private $now; // for consistency
  private $folder; // where we do our caching
  private $config = array(); // saved sitemap info (if any)
  private $id = false; // determines cacheability
  private $uri = false; // determines searchability
  private $cache = false;
  private $save = false;
  private $minify = false;
  private $search = false;
  
  public function __construct () {
    global $ci;
    $this->now = time();
    $this->folder = $ci->config->item('cache_path');
    if (!is_dir($this->folder)) mkdir($this->folder, 0755, true);
    $uri = $ci->uri->uri_string();
    $analytics = (substr($uri, 0, 33) == $ci->poster . '/') ? true : false;
    if ($analytics) $uri = substr($uri, 33); // so that we can increment_views() and that is all
    $file = BASE_URI . 'sitemap/' . $uri . '.php';
    if (is_file($file)) include($file);
    if (isset($config)) $this->config = $config;
    $this->config['file'] = $file;
    if (!$analytics && empty($_POST)) {
      $this->id = md5(BASE_URL . $uri);
      if (empty($_GET)) {
        if (strpos($uri, '.') === false) $this->uri = $uri; // ie. not an xml, txt or less file
      } else {
        $this->id .= '/' . md5(serialize($_GET));
      }
      $this->id .= '.txt';
      $ci->load->driver('cache', array('adapter'=>'file'));
      if ($data = $ci->cache->get($this->id)) {
        if ($data['saved'] >= $this->proceed() && $this->may_proceed_to_cache()) {
          $ci->output->set_content_type($data['output']);
          $this->cache = $data['cached']; // and current
        } else {
          $ci->cache->delete($this->id); // for future de-reference
        }
      }
    }
  }
  
  public function __get ($name) {
    global $page;
    if ($name == 'db' && !isset($this->db)) {
      $this->db = $page->plugin('Database', 'sqlite', BASE_URI . 'databases/sitemap.db');
      if ($this->db->created) {
        $this->db->fts->create('search', array('uri', 'title', 'description', 'keywords', 'content'), 'porter');
        $this->db->create('uris', array(
          'docid' => 'INTEGER PRIMARY KEY',
          'thumb' => 'TEXT DEFAULT NULL',
          'uri' => 'TEXT NOT NULL DEFAULT ""',
          'type' => 'TEXT NOT NULL DEFAULT ""',
          'id' => 'INTEGER NOT NULL DEFAULT 0',
          'info' => 'TEXT NOT NULL DEFAULT ""',
          'updated' => 'INTEGER NOT NULL DEFAULT 0'
        ), array('unique'=>'uri,type,id'));
      }
    }
    return (isset($this->$name)) ? $this->$name : null;
  }
  
  public function cache ($hours=24, $minify=false) {
    if ($this->id) {
      $this->save = $hours * 3600; // in seconds
      $this->minify = ($minify) ? true : false;
    }
  }
  
  public function save ($type, $id, $content, $info=array()) {
    if (!empty($this->uri)) $this->search = array($type, $id, $content, $info);
  }
  
  public function modify ($type, $id, $delete=false) {
    global $ci;
    if ($type == 'uri') {
      $uri = $id;
    } elseif (!$uri = $this->db->value('SELECT uri FROM uris WHERE uri != ? AND type = ? AND id = ? LIMIT 1', array('', $type, $id))) {
      return false;
    }
    if ($delete !== false && is_file(BASE_URI . 'sitemap/' . $uri . '.php')) unlink(BASE_URI . 'sitemap/' . $uri . '.php');
    if (!is_dir(BASE_URI . 'sitemap')) mkdir(BASE_URI . 'sitemap', 0755, true);
    $file = fopen(BASE_URI . 'sitemap/index.txt', 'ab');
    fwrite($file, $uri . "\n");
    fclose($file);
    $id = md5(BASE_URL . $uri);
    if (is_file($this->folder . $id . '.txt')) unlink($this->folder . $id . '.txt');
    if (is_dir($this->folder . $id)) {
      list($dirs, $files) = $ci->blog->folder($this->folder . $id);
      foreach ($files as $file) unlink($this->folder . $id . $file);
      if (empty($dirs)) rmdir($this->folder . $id);
    }
    return true;
  }
  
  public function uri ($field=null) { // Mainly for use in a theme's post.php file
    if (!isset($this->config['type'])) return null;
    if (is_null($field)) return $this->config;
    if ($field == $this->config['type']) return $this->config['id']; // eg. if ($id = $ci->sitemap->uri('blog')) { }
    return (isset($this->config[$field])) ? $this->config[$field] : null;
  }
  
  public function count ($phrase, $type='') {
    $where = '';
    if (!empty($type)) {
      $type = (is_array($type)) ? "IN ('" . implode("', '", $type) . "')" : "= '{$type}'";
      $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' AND u.type " . $type;
    }
    return $this->db->fts->count('search', $phrase, $where);
  }
  
  public function search ($phrase, $type='', $limit='', $weights=array(), $where='') { // $where is undocumented, use at your own peril
    if (!empty($type)) $type = (is_array($type)) ? "AND u.type IN ('" . implode("', '", $type) . "')" : "AND u.type = '{$type}'";
    $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' {$type} {$where}";
    $fields = array('s.uri', 's.title', 's.description', 's.keywords', 's.content', 'u.thumb', 'u.type', 'u.id', 'u.info', 'u.updated');
    $weights = array_slice(array_pad($weights, 5, 1), 0, 5);
    return $this->db->fts->search('search', $phrase, $limit, $where, $fields, $weights);
  }
  
  public function words ($phrase, $type, $id) {
    $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' AND u.type = '{$type}' AND id = {$id}";
    return $this->db->fts->words('search', $phrase, $where);
  }
  
  ##
  # All of the following methods are (and should only be) called from the controller
  ##
  
  public function cached () {
    return $this->cache; // so that it cannot be arbitrarily changed
  }
  
  public function caching () {
    return $this->may_proceed_to_cache(); // in general, not necessarily able to do so now
  }
  
  public function suspend_caching ($duration=5) { // in minutes - this one is actually called from the Admin model
    $this->proceed($this->now + ($duration * 60));
  }
  
  public function increment_views () { // our up-to-the-minute, easily accessible counter
    if (isset($this->config['views'])) {
      $this->config['views']++;
      $search = $this->config;
      unset($search['file']);
      file_put_contents($this->config['file'], implode("\n\n", array(
        '<?php',
        '$config = ' . var_export($search, true) . ';',
        '?>'
      )));
    }
  }
  
  public function update ($code) { // in order to check for any changes and update accordingly
    global $ci, $page;
    if ($this->save && $this->may_proceed_to_cache()) {
      if ($pos = strpos($this->id, '/')) {
        $dir = $this->folder . substr($this->id, 0, $pos);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
      }
      if ($this->minify && $page->get('type') == 'html') $code = $ci->output->minify($code);
      $ci->cache->save($this->id, array('saved'=>$this->now, 'output'=>$ci->output->get_content_type(), 'cached'=>$code), $this->save);
    }
    if ($this->search) {
      list($type, $id, $content, $info) = $this->search;
      $search = array(
        'uri' => $this->uri,
        'thumb' => $page->thumb,
        'title' => $page->title,
        'description' => $page->description,
        'keywords' => implode(' ', array_map('trim', explode(',', $page->keywords))),
        'content' => trim(strip_tags($content)),
        'type' => $type,
        'id' => $id,
        'info' => $info
      );
      $search['code'] = md5(serialize($search));
      if (!isset($this->config['code']) || $this->config['code'] != $search['code']) {
        $search['views'] = (isset($this->config['views'])) ? $this->config['views'] : 0;
        $search['updated'] = $this->now;
        if (!is_dir(dirname($this->config['file']))) mkdir(dirname($this->config['file']), 0755, true);
        file_put_contents($this->config['file'], implode("\n\n", array(
          '<?php',
          '$config = ' . var_export($search, true) . ';',
          '?>'
        )));
        $this->modify('uri', $this->uri);
        $this->config = $search;
      }
    } elseif (is_file($this->config['file'])) {
      $this->modify('uri', $this->uri, 'delete');
      $this->config = array('file'=>$this->config['file']);
    }
  }
  
  public function refresh () {
    $dir = BASE_URI . 'sitemap/';
    $current = $dir . 'index.txt';
    $file = $dir . 'indexing.txt';
    if (is_file($file)) {
      if ((time() - filemtime($file)) < 600) return; // we are already on it
      touch($file); // Houston, we had a problem
      if (is_file($current)) {
        $write = fopen($file, 'ab');
        $read = fopen($current, 'rb');
        while (!feof($read)) fwrite($write, fgets($read));
        fclose($read);
        fclose($write);
        unset($read, $write);
      }
      rename($file, $current);
    }
    if (!is_file($current)) return; // nothing to process
    rename($current, $file);
    $this->db->ci->trans_start();
    $uris = array_unique(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    $insert = $update = $delete = array();
    foreach ($uris as $uri) {
      $docid = $this->db->value('SELECT docid FROM uris WHERE uri = ?', array($uri));
      $config = array();
      if (is_file($dir . $uri . '.php')) include($dir . $uri . '.php');
      if (!empty($config)) {
        $search = array(
          'uri' => $config['uri'],
          'title' => $config['title'],
          'description' => $config['description'],
          'keywords' => $config['keywords'],
          'content' => $config['content']
        );
        $table = array(
          'docid' => $docid,
          'thumb' => $config['thumb'],
          'uri' => $config['uri'],
          'type' => $config['type'],
          'id' => $config['id'],
          'info' => serialize($config['info']),
          'updated' => $config['updated']
        );
      }
      if ($docid) {
        if (empty($config)) { // delete
          $delete[] = $docid;
        } else { // update
          $update['search'][$docid] = $search;
          $update['uris'][$docid] = $table;
        }
      } elseif (!empty($config)) { // insert
        $table['docid'] = $this->db->insert('search', $search);
        $insert[] = $table;
      }
    }
    if (!empty($insert)) $this->db->insert('uris', $insert);
    if (!empty($update)) {
      $this->db->update('search', 'docid', $update['search']);
      $this->db->update('uris', 'docid', $update['uris']);
    }
    if (!empty($delete)) {
      $this->db->delete('search', 'docid', $delete);
      $this->db->delete('uris', 'docid', $delete);
    }
    $this->db->ci->trans_complete();
    unlink($file);
  }
  
  public function may_change () {
    global $ci;
    $file = $this->folder . $this->id;
    if (empty($this->id) || !file_exists($file)) return;
    header('Cache-Control: max-age=0, must-revalidate');
    $created = filemtime($file);
    $lastmod = gmdate('D, d M Y H:i:s', $created) . ' GMT';
    $etag = '"' . $created . '-' . md5($file) . '"';
    $ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastmod : null;
    $iftag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == $etag : null;
    $match = (($ifmod || $iftag) && ($ifmod !== false && $iftag !== false)) ? true : false;
    header('ETag: ' . $etag); // ETag is sent even with 304 header
    if ($match) exit(header('Content-Type:', true, 304));
    header('Last-Modified: ' . $lastmod);
  }
  
  public function robots () {
    $this->cache();
    $file = BASE_URI . 'blog/content/robots.txt';
    return (is_file($file)) ? file_get_contents($file) : '';
  }
  
  public function xml ($folder) {
    global $ci, $page;
    if (!empty($folder)) {
      $folder = explode('-', trim($folder, '-'));
      $num = (is_numeric(end($folder))) ? max((int) array_pop($folder), 1) : 1;
      $folder = implode('-', $folder);
      $page->enforce(!empty($folder) ? 'sitemap-' . $folder . '-' . $num . '.xml' : 'sitemap' . '-' . $num . '.xml');
      $suffix = $ci->config->item('url_suffix');
      $urls = array();
      if ($folder == 'blog' && $num == 1) {
        $urls[] = '  <url>';
        $urls[] = '    <loc>' . BASE_URL . '</loc>';
        $urls[] = '  </url>';
      }
      $offset = ($num > 1) ? (($num - 1) * 10000) - 1 : 0;
      $this->db->query('SELECT uri, updated FROM uris WHERE uri != ? AND type = ? ORDER BY uri ASC LIMIT ' . $offset . ', 10000', array('', $folder));
      while (list($uri, $updated) = $this->db->fetch('row')) {
        $urls[] = '  <url>';
        $urls[] = '    <loc>' . BASE_URL . $uri . $suffix . '</loc>';
        $urls[] = '    <lastmod>' . date('Y-m-d', $updated) . '</lastmod>';
        $urls[] = '  </url>';
      }
      if (empty($urls)) exit(header('HTTP/1.1 404 Not Found'));
      $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode("\n", $urls) . "\n";
      $xml .= '</urlset>' . "\n";
    } else {
      $page->enforce('sitemap.xml');
      $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
      $this->db->query('SELECT type, MAX(updated) AS updated, COUNT(type) AS count FROM uris GROUP BY type ORDER BY type ASC');
      while (list($type, $updated, $count) = $this->db->fetch('row')) {
        $sitemap = (!empty($type)) ? 'sitemap-' . $type . '-' : 'sitemap-';
        $updated = date('Y-m-d', $updated);
        for ($i=1; $i<=$count; $i+=10000) {
          $xml .= '  <sitemap>' . "\n";
          $xml .= '    <loc>' . BASE_URL . $sitemap . ceil($i / 10000) . '.xml</loc>' . "\n";
          $xml .= '    <lastmod>' . $updated . '</lastmod>' . "\n";
          $xml .= '  </sitemap>' . "\n";
        }
      }
      $xml .= '</sitemapindex>' . "\n";
    }
    $this->cache();
    return $xml;
  }
  
  private function proceed ($update=null) {
    global $ci;
    static $proceed = null;
    if (!isset($ci->cache)) $ci->load->driver('cache', array('adapter'=>'file'));
    if (is_null($proceed) && !($proceed = $ci->cache->get('proceed.txt'))) $proceed = $save = $this->now;
    if (is_int($update) && $update >= $this->now) $proceed = $save = $update;
    if (isset($save)) $ci->cache->save('proceed.txt', $proceed, 604800); // everything is recached weekly no matter what
    return $proceed;
  }
  
  private function may_proceed_to_cache () { // nothing is cached or delivered until this method approves
    return ($this->proceed() <= $this->now) ? true : false;
  }
  
}

/* End of file Sitemap.php */
/* Location: ./application/libraries/Sitemap/Sitemap.php */