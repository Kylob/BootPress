<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Resources extends CI_Driver_Library {

  protected $valid_drivers = array('deliverer');

  public function cache ($urls, $combine=false) {
    $single = (!is_array($urls)) ? true : false;
    $urls = (array) $urls;
    $cache = array(); // (url => path) determines what $urls can and need to be cached
    $names = array(); // (url => name)
    foreach ($urls as $url) {
      $file = substr($url, strlen(BASE_URL));
      if ($url == BASE_URL . $file && strpos($file, 'CDN') === false && !$this->cached($file)) {
        if (file_exists(BASE_URI . $file)) {
          $cache[$url] = 'BASE_URI' . $file;
        } elseif (file_exists(BASE . $file)) {
          $cache[$url] = 'BASE' . $file;
        }
        if (isset($cache[$url])) {
          list($name, $type) = $this->type($file);
          if ($combine) {
            if (!is_numeric($name)) $names[$cache[$url]] = $name;
          } else {
            $names[$cache[$url]] = (is_numeric($name) && substr($cache[$url], 0, 23) != 'BASE_URIblog/resources/') ? $type : '/' . $name . $type;
          }
        }
      }
    }
    if ($combine && empty($cache)) return '';
    if (!empty($cache)) {
      $tiny_paths = $this->tiny_paths($cache);
      if ($combine) {
        $path = (empty($names)) ? $type : '/' . implode('-', $names) . $type;
        return BASE_URL . implode(0, $tiny_paths) . $path;
      }
      foreach ($cache as $url => $path) $cache[$url] = BASE_URL . $tiny_paths[$path] . $names[$path];
    }
    $links = array();
    foreach ($urls as $url) $links[$url] = (isset($cache[$url])) ? $cache[$url] : $url;
    return ($single) ? array_shift($links) : $links;
  }
  
  public function file_paths ($tiny) { // takes a tiny string and returns the file (uri) paths in the order specified
    global $ci;
    $tiny = (strpos($tiny, '/')) ? substr($tiny, 0, strpos($tiny, '/')) : substr($tiny, 0, strpos($tiny, '.'));
    $files = explode('0', $tiny);
    $updated = array();
    $paths = array();
    $query = $ci->db->query('SELECT t.path AS tiny, f.path AS full FROM ci_tiny_paths AS t
                             INNER JOIN ci_full_paths AS f ON t.full_id = f.id
                             WHERE t.path IN("' . implode('", "', $files) . '")');
    foreach ($query->result() as $row) $paths[$row->tiny] = $this->convert_file($row->full);
    $files = array_flip($files);
    foreach ($files as $tiny => $path) {
      if (isset($paths[$tiny]) && file_exists($paths[$tiny])) {
        $files[$tiny] = $paths[$tiny];
        $updated[] = filemtime($paths[$tiny]);
      } else {
        unset($files[$tiny]);
      }
    }
    return array($files, $updated);
  }
  
  public function cached ($path) { // public (instead of protected) for child's sake
    return preg_match('/^([1-9a-z]{5}[0]?)+(\/.*)?\.(jpe?g|gif|png|ico|js|css|pdf|ttf|otf|svg|eot|woff|swf|tar|t?gz|zip|csv|xls?x?|word|docx?|ppt|mp3|mpeg?|mpg|mov|qt|psd)(\?.*)?$/i', $path);
  }
  
  public function convert_file ($path) { // public (instead of protected) for child's sake
    if (substr($path, 0, 4) == 'BASE') {
      return str_replace(array('BASE_URI', 'BASE'), array(BASE_URI, BASE), $path);
    } else {
      return str_replace(array(BASE_URI, BASE), array('BASE_URI', 'BASE'), $path);
    }
  }
  
  private function tiny_paths ($files) { // takes an array of file paths (that exist) and returns their tiny counterparts (in order)
    global $ci;
    $ids = array(); // full_id => full - to update paths if updating
    $paths = array(); // full => tiny
    $update = array(); // full_id => updated
    $where = array();
    foreach ($files as $key => $value) $where[$key] = $ci->db->escape($value);
    $query = $ci->db->query("SELECT f.id AS full_id, f.path AS full, t.path AS tiny, f.updated AS updated \nFROM ci_full_paths AS f INNER JOIN ci_tiny_paths AS t ON f.tiny_id = t.id \nWHERE f.path IN(\n" . implode(",\n", $where) . ")");
    if ($query->num_rows() > 0) {
      foreach ($query->result() as $row) {
        $file = $this->convert_file($row->full);
        $ids[$row->full_id] = $row->full;
        $paths[$row->full] = $row->tiny;
        if (filemtime($file) != $row->updated) $update[$row->full_id] = filemtime($file);
      }
    }
    $count = count($files) - count($paths) + count($update);
    if ($count > 0) {
      $ci->db->trans_start();
      // get the fresh tiny ids we'll be needing for the updates and inserts
      $tiny_ids = $this->tiny_ids($count);
      // update the paths whose files have been updated since the last time we checked (if any)
      foreach ($update as $full_id => $updated) {
        list($tiny, $tiny_id) = each($tiny_ids);
        $ci->db->query('UPDATE ci_full_paths SET tiny_id = ?, updated = ? WHERE id = ?', array($tiny_id, $updated, $full_id));
        $ci->db->query('UPDATE ci_tiny_paths SET full_id = ? WHERE id = ?', array($full_id, $tiny_id));
        $paths[$ids[$full_id]] = $tiny;
      }
      // insert new paths if there are any of them left for us to cache
      foreach ($files as $url => $full) {
        if (!isset($paths[$full])) {
          $file = $this->convert_file($full);
          list($tiny, $tiny_id) = each($tiny_ids);
          $ci->db->query('INSERT INTO ci_full_paths (path, tiny_id, updated) VALUES (?, ?, ?)', array($full, $tiny_id, filemtime($file)));
          $full_id = $ci->db->insert_id();
          $ci->db->query('UPDATE ci_tiny_paths SET full_id = ? WHERE id = ?', array($full_id, $tiny_id));
          $paths[$full] = $tiny;
        }
      }
      $ci->db->trans_complete();
    }
    $links = array(); // to return in the same order they were received
    foreach ($files as $url => $path) $links[$path] = $paths[$path]; // (path => tiny)
    return $links;
  }
  
  private function tiny_ids ($count) {
    global $ci;
    $query = $ci->db->query('SELECT id, path FROM ci_tiny_paths WHERE full_id = 0 LIMIT ' . $count);
    if ($query->num_rows() == $count) {
      $ids = array();
      foreach ($query->result() as $row) $ids[$row->path] = $row->id;
      return $ids;
    }
    $string = "123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for ($i=0; $i < $count + 100; $i++) {
      $tiny_id = ''; // 60 (characters) ^ 5 (length) gives 777,600,000 possible combinations
      while (strlen($tiny_id) < 5) $tiny_id .= $string[mt_rand(0,60)];
      $ci->db->query("INSERT OR IGNORE INTO ci_tiny_paths (path) VALUES ('{$tiny_id}')");
    }
    return $this->tiny_ids($count);
  }
  
  private function type ($file) {
    $file = basename($file);
    $split = strrpos($file, '.');
    $name = substr($file, 0, $split);
    $type = substr($file, $split); // including the '.'
    return array($name, $type);
  }
  
}

/* End of file Resources.php */
/* Location: ./application/libraries/Resources/Resources.php */