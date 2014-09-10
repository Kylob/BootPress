<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sitemap extends CI_Driver_Library {

  private $folder;
  private $now; // for consistency
  private $id = false; // determines cacheability
  private $uri = false; // determines searchability
  private $cache = false;
  private $save = false;
  private $minify = false;
  private $search = false;
  
  public function __construct () {
    global $ci;
    $this->folder = $ci->config->item('cache_path');
    $this->now = time();
    if (!is_dir($this->folder)) mkdir($this->folder, 0755, true);
    if (empty($_POST)) {
      $uri = $ci->uri->uri_string();
      $this->id = md5($uri);
      if (empty($_GET)) {
        if (strpos($uri, '.') === false) $this->uri = $uri; // ie. not an xml or txt file
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
  
  public function __destruct () {
    $this->db('close');
  }
  
  public function cache ($hours=24, $minify=false) {
    if ($this->id) {
      $this->save = $hours * 3600; // in seconds
      $this->minify = ($minify) ? true : false;
    }
  }
  
  public function save ($thumb, $id, $content, $info=array()) {
    if (!empty($this->uri)) $this->search = array($thumb, $id, $content, $info);
  }
  
  public function modify ($thumb, $id, $delete=false) {
    global $ci;
    if ($thumb == 'uri') {
      $uri = $id;
    } elseif (!$uri = $this->db()->value('SELECT uri FROM uris WHERE uri != ? AND thumb = ? AND id = ? LIMIT 1', array('', $thumb, $id))) {
      return false;
    }
    if ($delete !== false && ($docid = $this->db()->value('SELECT docid FROM uris WHERE uri = ?', array($uri)))) {
      $this->db()->ci->trans_start();
      $this->db()->delete('uris', 'docid', $docid);
      $this->db()->delete('search', 'docid', $docid);
      $this->db()->delete('resources', 'docid', $docid);
      $this->db()->ci->trans_complete();
    }
    $id = md5($uri);
    if (file_exists($this->folder . $id . '.txt')) unlink($this->folder . $id . '.txt');
    if (file_exists($this->folder . $id . '/')) {
      $ci->load->helper('file');
      delete_files($this->folder . $id . '/');
      rmdir($this->folder . $id . '/');
    }
    return true;
  }
  
  public function count ($phrase, $thumb='') {
    $where = '';
    if (!empty($thumb)) {
      $thumb = (is_array($thumb)) ? "IN ('" . implode("', '", $thumb) . "')" : "= '{$thumb}'";
      $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' AND u.thumb " . $thumb;
    }
    return $this->db()->fts->count('search', $phrase, $where);
  }
  
  public function search ($phrase, $thumb='', $limit='', $weights=array(), $where='') { // $where is undocumented, use at your own peril
    if (!empty($thumb)) $thumb = (is_array($thumb)) ? "AND u.thumb IN ('" . implode("', '", $thumb) . "')" : "AND u.thumb = '{$thumb}'";
    $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' {$thumb} {$where}";
    $fields = array('s.uri', 's.title', 's.description', 's.keywords', 's.content', 'u.thumb', 'u.id', 'u.info', 'u.updated', 'u.code');
    $weights = array_slice(array_pad($weights, 5, 1), 0, 5);
    return $this->db()->fts->search('search', $phrase, $limit, $where, $fields, $weights);
  }
  
  public function words ($phrase, $thumb, $id) {
    $where = "INNER JOIN uris AS u ON u.docid = s.docid WHERE u.uri != '' AND u.thumb = '{$thumb}' AND id = {$id}";
    return $this->db()->fts->words('search', $phrase, $where);
  }
  
  public function db ($close=false) { // This method is undocumented, but still needs to remain public for BootPress internals.  Use at your own peril.
    global $page;
    static $instance = null;
    if ($close) return (!is_null($instance)) ? $instance->ci->close() : false;
    if (is_null($instance)) {
      $instance = $page->plugin('Database', 'sqlite', BASE_URI . 'blog/databases/sitemap.db');
      if ($instance->created) {
        $instance->fts->create('search', array('uri', 'title', 'description', 'keywords', 'content'), 'porter');
        $instance->create('uris', array(
          'docid' => 'INTEGER PRIMARY KEY',
          'uri' => 'TEXT NOT NULL DEFAULT ""',
          'thumb' => 'TEXT NOT NULL DEFAULT ""',
          'id' => 'INTEGER NOT NULL DEFAULT 0',
          'info' => 'TEXT NOT NULL DEFAULT ""',
          'updated' => 'INTEGER NOT NULL DEFAULT 0',
          'code' => 'TEXT NOT NULL DEFAULT ""'
        ), array('unique'=>'uri,thumb,id'));
        $instance->create('resources', array(
          'docid' => 'INTEGER NOT NULL DEFAULT 0',
          'resource_id' => 'INTEGER NOT NULL DEFAULT 0'
        ), array('unique'=>'docid,resource_id'));
      }
    }
    return $instance;
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
  
  public function disable_caching () { // declared from the layout if it includes dynamic content
    $this->save = false; // but it can still be sitemapped and searched
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
      list($thumb, $id, $content, $info) = $this->search;
      $search = array(
        'uri' => $this->uri,
        'title' => $page->title,
        'description' => $page->description,
        'keywords' => implode(' ', array_map('trim', explode(',', $page->keywords))),
        'content' => strip_tags($content)
      );
      $uris = array(
        'uri' => $this->uri,
        'thumb' => $thumb,
        'id' => $id,
        'info' => serialize($info)
      );
      $uris['code'] = md5(serialize(array_merge($search, $uris)));
      $uris['updated'] = time();
      $this->db()->ci->trans_start();
      if ($sitemap = $this->uri()) {
        $docid = array_shift($sitemap);
        if ($uris['code'] != $sitemap['code']) {
          $this->db()->update('search', 'docid', array($docid => $search));
          $this->db()->update('uris', 'docid', array($docid => $uris));
        }
      } else { // insert
        $uris['docid'] = $this->db()->insert('search', $search);
        $this->db()->insert('uris', $uris);
        $docid = $uris['docid'];
      }
      if (!empty($ci->resource)) {
        if (implode(',', array_keys($ci->resource)) != $this->db()->value('SELECT GROUP_CONCAT(resource_id) FROM resources WHERE docid = ?', array($docid))) {
          $this->db()->delete('resources', 'docid', $docid);
          foreach (array_keys($ci->resource) as $id) $this->db()->insert('resources', array('docid'=>$docid, 'resource_id'=>$id));
        }
      }
      $this->db()->ci->trans_complete();
    }
  }
  
  public function may_change () {
    global $ci;
    $file = $this->folder . $this->id;
    if (empty($this->id) || !file_exists($file)) return;
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Cache-Control: max-age=31536000, must-revalidate');
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
  
  public function robots ($text=null) {
    $this->cache();
    if (!is_null($text)) {
      $this->db()->settings('robots', $text);
      $this->modify('uri', 'robots.txt');
    }
    return ($robots = $this->db()->settings('robots')) ? $robots : '';
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
      $this->db()->query('SELECT uri, updated FROM uris WHERE uri != ? AND thumb = ? ORDER BY uri ASC LIMIT ' . $offset . ', 10000', array('', $folder));
      while (list($uri, $updated) = $this->db()->fetch('row')) {
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
      $this->db()->query('SELECT thumb, MAX(updated) AS updated, COUNT(thumb) AS count FROM uris GROUP BY thumb ORDER BY thumb ASC');
      while (list($thumb, $updated, $count) = $this->db()->fetch('row')) {
        $sitemap = (!empty($thumb)) ? 'sitemap-' . $thumb . '-' : 'sitemap-';
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
  
  private function uri ($field='') {
    global $ci;
    static $uri = null;
    if (is_null($uri)) {
      $uri = $this->db()->row(array(
        'SELECT s.docid, s.uri, s.title, s.description, s.keywords, s.content, u.thumb, u.id, u.info, u.updated, u.code',
        'FROM uris AS u INNER JOIN search AS s ON u.docid = s.docid',
        'WHERE u.uri = ?'
      ), array($this->uri));
      if ($uri) $uri['info'] = unserialize($uri['info']);
    }
    if (empty($field)) return $uri; // an array or false
    return ($uri && isset($uri[$field])) ? $uri[$field] : false; // a value or false
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