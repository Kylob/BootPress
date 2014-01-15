<?php

class CacheFile extends CombineFiles {

  public function __construct ($file) {
    switch ($_GET['type']) {
      case 'js': $type = 'js'; break;
      case 'css': $type = 'css'; break;
      case 'jpeg':
      case 'jpg': $type = 'jpg'; break;
      case 'gif': $type = 'gif'; break;
      case 'png': $type = 'png'; break;
      case 'ico': $type = 'ico'; break;
      case 'eot': $type = 'eot'; break;
      case 'ttf': $type = 'ttf'; break;
      case 'otf': $type = 'otf'; break;
      case 'svg': $type = 'svg'; break;
      case 'woff': $type = 'woff'; break;
      default:
        header ("HTTP/1.1 503 Not Implemented");
        exit;
    }
    parent::__construct();
    $image = (in_array($type, array('jpeg', 'jpg', 'gif', 'png', 'ico'))) ? true : false;
    if (strpos($file, '/') !== false) { // then pull the actual file
      if (file_exists(BASE_URI . $file)) $file = BASE_URI . $file;
      elseif (file_exists(BASE . $file)) $file = BASE . $file;
      else {
        header( "HTTP/1.1 404 Not Found" );
        exit;
      }
      $this->may_change($file);
      $cache = file_get_contents($file);
    } else {
      list($paths, $ids, $updated) = $this->uri_path($_GET['file']);
      if (empty($paths)) {
        header( "HTTP/1.1 404 Not Found" );
        exit;
      }
      $this->never_expires(max($updated));
      if ($image) {
        $file = array_shift($paths);
        $cache = file_get_contents($file);
      } else {
        $cache = '';
        if ($type == 'css') {
          foreach ($paths as $tiny => $uri) $cache .= $this->css_get_contents($uri);
        } else {
          foreach ($paths as $tiny => $uri) $cache .= file_get_contents($uri);
        }
      }
    }
    switch ($type) {
      case 'js': header('Content-Type: text/javascript; charset=utf-8'); break;
      case 'css': header('Content-Type: text/css; charset=utf-8'); break;
      case 'jpeg':
      case 'jpg': header('Content-Type: image/jpeg'); break;
      case 'gif': header('Content-Type: image/gif'); break;
      case 'png': header('Content-Type: image/png'); break;
      case 'ico': header('Content-Type: image/x-icon'); break;
      case 'eot': header('Content-Type: application/vnd.ms-fontobject'); break;
      case 'ttf': header('Content-Type: font/ttf'); break;
      case 'otf': header('Content-Type: font/opentype'); break;
      case 'svg': header('Content-Type: image/svg+xml'); break;
      case 'woff': header('Content-Type: font/x-woff'); break;
    }
    if (!$image && !in_array($type, array('eot', 'woff'))) { // these are already compressed
      $supported = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
      $gzip = strstr($supported, 'gzip');
      $deflate = strstr($supported, 'deflate');
      $encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');
      if (isset($_SERVER['HTTP_USER_AGENT']) && !strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') && preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
        $version = floatval($matches[1]);			
        if ($version < 6) $encoding = 'none';				
        if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) $encoding = 'none';
      }
      if ($encoding != 'none') {
        $cache = gzencode($cache, 9, ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
        header('Vary: Accept-Encoding');
        header('Content-Encoding: ' . $encoding);
      }
    }
    header('Content-Length: ' . strlen($cache));
    echo $cache;
    exit;
  }
  
  private function may_change ($file) {
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Cache-Control: max-age=31536000, must-revalidate');
    $created = filemtime($file);
    $lastmod = gmdate('D, d M Y H:i:s', $created) . ' GMT';
    $etag = '"' . $created . '-' . md5($file) . '"';
    $ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastmod : null;
    $iftag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == $etag : null;
    $match = (($ifmod || $iftag) && ($ifmod !== false && $iftag !== false)) ? true : false;
    header('ETag: ' . $etag); // ETag is sent even with 304 header
    if ($match) {
      header('Content-Type:', true, 304);
      exit;
    }
    header('Last-Modified: ' . $lastmod);
  }
  
  private function never_expires ($filemtime) {
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Cache-Control: max-age=31536000, public'); // 1 year (365 * 24 * 60 * 60)
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $filemtime - 15768000) . ' GMT');
  }
  
  private function css_get_contents ($file) {
    $css = file_get_contents($file);
    $imports = (preg_match_all('/@import\s*[\'"](\S*\.(?:css))[\'"]/i', $css, $matches)) ? $matches[1] : array();
    $urls = (preg_match_all('/url\s*\(\s*[\'"]?(\S*\.(?:css|jpe?g|gif|png|eot|ttf|otf|svg|woff))\S*[\'"]?\s*\)/i', $css, $matches)) ? $matches[1] : array();
    $links = array_unique(array_merge($imports, $urls));
    $check = (array) $links;
    $links = array_flip($check); // relative => uri
    foreach ($links as $key => $value) $links[$key] = false; // for now
    foreach ($check as $link) {
      if (strpos($link, '//') !== false || substr($link, 0, 5) == 'data:') continue; // not relative
      $dir = dirname($file); // no trailing slash
      $uri = $link;
      if ($uri[0] == '/') $uri = substr($uri, 1); // current directory
      if (substr($uri, 0, 3) == '../') {
        do {
          $dir = dirname($dir); // up one parent directory
          $uri = substr($uri, 3);
        } while (substr($uri, 0, 3) == '../');
      } elseif (substr($uri, 0, 2) == './') { // current directory
        $uri = substr($uri, 2);
      }
      $links[$link] = $dir . '/' . $uri;
    }
    $links = array_filter($links);
    list($paths, $uris, $removed) = $this->tiny_path($links);
    foreach ($links as $relative => $uri) {
      if (isset($uris[$uri])) {
        $links[$relative] = $uris[$uri] . substr($uri, strrpos($uri, '.'));
      } else {
        unset($links[$relative]);
      }
    }
    return str_replace(array_keys($links), array_values($links), $css);
  }
  
}

?>