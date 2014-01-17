<?php

class CacheURLs {

  private $db;
  
  public function __construct () {
    global $page;
    $page->plugin('SQLite');
    $this->db = new SQLite ('CombineFiles');
    if ($this->db->created) $this->create_tables();
  }
  
  public function combine ($urls) { // should all be of the same type (css or js)
    $names = array();
    $combine = array(); // ours
    $others = array();
    foreach ($urls as $url) {
      $file = substr($url, strlen(BASE_URL));
      $local = ($url == BASE_URL . $file) ? true : false;
      if ($local && !$this->cached($file)) {
        list($name, $type) = $this->type($file);
        $names[] = $name;
        $combine[] = (file_exists(BASE_URI . $file)) ? BASE_URI . $file : BASE . $file;
      } else {
        $others[] = $url;
      }
    }
    if (empty($combine)) return $urls;
    list($combined) = $this->tiny_path($combine);
    $combined = array(BASE_URL . $combined . '/' . implode('-', $names) . $type);
    return array_merge($combined, $others); // an array of links with ours (combined) on top
  }
  
  public function urls ($urls) {
    $single = (!is_array($urls)) ? true : false;
    $urls = (array) $urls;
    $cached = array();
    foreach ($urls as $url) {
      $file = substr($url, strlen(BASE_URL));
      $local = ($url == BASE_URL . $file && strpos($file, 'CDN') === false) ? true : false;
      if ($local && !$this->cached($file)) {
        $uri = (file_exists(BASE_URI . $file)) ? BASE_URI . $file : BASE . $file;
        $cached[$uri] = $url;
      }
    }
    if (!empty($cached)) {
      list($combined, $uris) = $this->tiny_path(array_keys($cached));
      foreach ($uris as $uri => $tiny) {
        $url = $cached[$uri];
        $cached[$url] = BASE_URL . $tiny . '/' . basename($url);
      }
    }
    $links = array();
    foreach ($urls as $url) {
      $links[$url] = (isset($cached[$url])) ? $cached[$url] : $url;
    }
    return ($single) ? array_shift($links) : $links;
  }
  
  protected function cached ($path) {
    return preg_match('/^([1-9a-z]{5}[0]?)+(\/.*)?\.(js|css|jpe?g|gif|png|ico|eot|ttf|otf|svg|woff)(\?.*)?$/i', $path);
    // return preg_match('/^([1-9a-z]{5}[0]?)+\/(.*).(js|css|jpe?g|gif|png|ico|eot|ttf|otf|svg|woff)$/i', $path);
  }
  
  protected function tiny_path ($uri) {
    $files = (array) $uri;
    $paths = array(); // uri => tiny
    $uris = array(); // original (unconverted) uri => tiny_id
    foreach ($files as $uri) $paths[$this->convert_file($uri)] = false; // for now
    $this->db->query('SELECT id, path, tiny_id, updated FROM paths WHERE path IN("' . implode('", "', array_keys($paths)) . '")');
    while (list($id, $uri, $tiny, $time) = $this->db->fetch('row')) {
      $file = $this->convert_file($uri);
      if (file_exists($file)) {
        $paths[$uri] = (filemtime($file) != $time) ? $this->new_tiny($id, filemtime($file)) : $tiny;
        $uris[$file] = $paths[$uri];
      } else {
        $this->delete_path($id);
      }
    }
    $removed = array(); // of files
    foreach ($paths as $uri => $tiny) {
      if ($tiny === false) {
        $file = $this->convert_file($uri);
        if (file_exists($file)) {
          $paths[$uri] = $this->new_tiny($file, filemtime($file));
          $uris[$file] = $paths[$uri];
        } else {
          $removed[] = $file;
        }
      }
    }
    $path = implode('0', array_filter($paths));
    return array($path, $uris, $removed);
  }
  
  protected function uri_path ($tiny) {
    if (strpos($tiny, '/')) $tiny = substr($tiny, 0, strpos($tiny, '/'));
    $ids = explode('0', $tiny);
    $paths = array();
    $this->db->query('SELECT path_id, tiny_id FROM ids WHERE tiny_id IN("' . implode('", "', $ids) . '")');
    while (list($id, $tiny) = $this->db->fetch('row')) $paths[$id] = $tiny;
    $files = array(); // requested_tiny => file (in the order they were requested)
    $ids = array_flip($ids); // requested_tiny => path_id (will never change)
    foreach ($ids as $tiny => $id) {
      $files[$tiny] = false;
      $ids[$tiny] = false;
    }
    $updated = array(); // requested_tiny => filemtime
    $removed = array(); // of requested_tiny_id's
    $this->db->query('SELECT id, path, tiny_id, updated FROM paths WHERE id IN(' . implode(', ', array_keys($paths)) . ')');
    while (list($id, $uri, $tiny, $time) = $this->db->fetch('row')) {
      $file = $this->convert_file($uri);
      if (file_exists($file)) {
        $updated[$paths[$id]] = filemtime($file);
        if ($updated[$paths[$id]] != $time) $tiny = $this->new_tiny($id, $updated[$paths[$id]]);
        if ($paths[$id] != $tiny) {
          $files[$paths[$id]] = $file;
          $ids[$paths[$id]] = $id;
        } else {
          $files[$tiny] = $file;
          $ids[$tiny] = $id;
        }
      } else {
        $this->delete_path($id);
      }
    }
    foreach ($files as $tiny => $file) if ($file === false) $removed[] = $tiny;
    $files = array_filter($files);
    $ids = array_filter($ids);
    return array($files, $ids, $updated, $removed);
  }
  
  private function type ($file) {
    $file = basename($file);
    $split = strrpos($file, '.');
    $name = substr($file, 0, $split);
    $type = substr($file, $split); // included the '.'
    return array($name, $type);
  }
  
  private function convert_file ($path) {
    if (substr($path, 0, 4) == 'BASE') {
      return str_replace(array('BASE_URI', 'BASE'), array(BASE_URI, BASE), $path);
    } else {
      return str_replace(array(BASE_URI, BASE), array('BASE_URI', 'BASE'), $path);
    }
  }
  
  private function new_tiny ($path, $updated=false) {
    if (is_numeric($path) || file_exists($path)) {
      if (!is_numeric($path)) {
        $insert = array();
        $insert['path'] = $this->convert_file($path);
        $insert['updated'] = ($updated) ? $updated : filemtime($path);
        $path = $this->db->insert('paths', $insert);
      }
      $tiny_id = ''; // 60 (characters) ^ 5 (length) gives 777,600,000 possible combinations
      $string = "123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
      while (strlen($tiny_id) < 5) $tiny_id .= $string[rand(0,strlen($string)-1)];
      if ($this->db->insert('ids', array('path_id'=>$path, 'tiny_id'=>$tiny_id))) {
        $update = array('tiny_id'=>$tiny_id);
        if ($updated) $update['updated'] = $updated;
        $this->db->update('paths', $update, 'id', $path);
        return $tiny_id;
      } elseif ($path) { // isn't false
        return $this->new_tiny($path); // just keep trying until we get one right
      }
    }
    return false;
  }
  
  private function delete_path ($id) {
    $this->db->delete('paths', 'id', $id);
    $this->db->delete('ids', 'path_id', $id);
  }
  
  private function create_tables () {
    $table = 'paths';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['path'] = 'TEXT UNIQUE COLLATE NOCASE';
    $columns['tiny_id'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['updated'] = 'INTEGER NOT NULL DEFAULT 0'; // filemtime
    $this->db->create($table, $columns);
    
    $table = 'ids';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['path_id'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['tiny_id'] = 'TEXT UNIQUE';
    $this->db->create($table, $columns);
    $this->db->index('ids', 1, 'path_id');
  }
  
}

?>