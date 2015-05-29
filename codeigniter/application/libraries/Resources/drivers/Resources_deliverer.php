<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Resources_deliverer extends CI_Driver {

  public function view ($file, $type) {
    global $ci, $page;
    $file .= '.' . $type;
    $type = strtolower($type);
    $image = $compress = $deliver = $download = $stream = false;
    if (preg_match('/^(jpe?g|gif|png|ico)$/', $type)) {
      $image = $type;
    } elseif (preg_match('/^(js|css|pdf|ttf|otf|svg)$/', $type)) {
      $compress = $type;
    } elseif (preg_match('/^(eot|woff2?|swf)$/', $type)) {
      $deliver = $type;
    } elseif (preg_match('/^(tar|t?gz|zip|csv|xls?x?|word|docx?|ppt|psd)$/', $type)) {
      $download = $type;
    } elseif (preg_match('/^(ogg|wav|mp3|mp4|mpeg?|mpg|mov|qt)$/', $type)) {
      $stream = $type;
    } else {
      exit(header('HTTP/1.1 503 Not Implemented'));
    }
    if ($this->cached($file)) {
      list($files, $updated) = $this->file_paths($file);
      if (empty($files)) exit(header('HTTP/1.1 404 Not Found'));
      $cache = array_shift($files); // Only one file at a time is allowed now
      $updated = array_shift($updated);
      if (preg_match('/^.{5}\/((~)?([0-9]{1,3})[x]{1}([0-9]{1,3})(:[0-9]{1,3})?)[\/\.]{1}/', $file, $matches)) {
        $thumb = BASE_URI . 'thumbs/' . md5($cache) . '/' . rawurlencode($matches[1]) . '.' . $type;
        if (!is_file($thumb) || filemtime($thumb) < $updated) { // create the image thumb
          require_once(BASE . 'Page.php');
          $page = new Page;
          $thumbnail = $page->plugin('Image', 'uri', $cache);
          if ($matches[2] == '~') {
            $thumbnail->constrain($matches[3], $matches[4]);
          } else {
            $thumbnail->resize($matches[3], $matches[4], 'enforce');
          }
          $thumbnail->save($thumb, (isset($matches[5]) ? min(substr($matches[5], 1), 100) : 80));
        }
        $cache = $thumb;
        $updated = filemtime($thumb);
      }
      if (!$download && !$stream) $this->never_expires($updated);
      if ($compress == 'pdf' || $download || $stream) {
        $ci->load->driver('session');
        $resource = str_replace(array(BASE_URI, BASE), '', $cache);
        $resource .= '#' . substr(strstr($ci->uri->uri_string(), '/'), 1);
        $ci->log_analytics('hits', $resource);
      }
    } elseif (strpos($file, 'CDN') !== false && is_file(BASE . $file)) {
      $cache = BASE . $file;
      $this->never_expires(filemtime($cache));
    } elseif ($file == 'favicon.ico') {
      $cache = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;
      $this->never_expires(filemtime($cache));
    } else {
      exit(header('HTTP/1.1 404 Not Found'));
    }
    $mimes =& get_mimes();
    header('Content-Type: ' . (is_array($mimes[$type]) ? array_shift($mimes[$type]) : $mimes[$type]));
    if ($compress) {
      $supported = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
      $gzip = strstr($supported, 'gzip');
      $deflate = strstr($supported, 'deflate');
      $encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');
      if (isset($_SERVER['HTTP_USER_AGENT']) && !strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') && preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
        $version = floatval($matches[1]);			
        if ($version < 6) $encoding = 'none';				
        if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) $encoding = 'none';
      }
      $cache = ($type == 'css') ? $this->css_get_contents($cache, $file) : file_get_contents($cache);
      if ($encoding != 'none') {
        $cache = gzencode($cache, 9, ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
        header('Vary: Accept-Encoding');
        header('Content-Encoding: ' . $encoding);
      }
      header('Content-Length: ' . strlen($cache));
      exit($cache);
    }
    if (!$fp = fopen($cache, 'rb')) {
      header('HTTP/1.1 500 Internal Server Error');
      exit;
    }
    $size = filesize($cache);
    if ($download || $stream) {
      ini_set('zlib.output_compression', 'Off');
      $length = $size;
      $start = 0;
      $end = $size - 1;
      if ($range = $ci->input->server('HTTP_RANGE')) { // serve a partial file
        if (preg_match('%bytes=(\d+)-(\d+)?%i', $range, $match)) {
          $match = array_map('intval', $match);
          if (!isset($match[2])) $match[2] = $end;
          $length = $match[2] - $match[1] + 1;
        }
        if ($length <= $size) {
          fseek($fp, $match[1]);
          header('HTTP/1.1 206 Partial Content');
          header("Content-Range: bytes {$match[1]}-{$match[2]}/{$size}");
        } else {
          header('HTTP/1.1 416 Requested Range Not Satisfiable');
          header("Content-Range: bytes {$start}-{$end}/{$size}");
          exit;
        }
      }
      header('Accept-Ranges: bytes');
      header('Connection: Keep-Alive"');
      header('X-Pad: avoid browser bug');
      header('Pragma: public'); // Fix IE6 Content-Disposition
      header('Expires: -1'); // Prevent caching
      header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
      header('Etag: "' . filemtime($cache) . '-' . md5($cache) . '"'); // Enable resumable download in IE9.
      if ($download) {
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
      } elseif ($stream) {
        header('Content-Disposition: inline;');
        header('Content-Transfer-Encoding: binary');
      }
      $size = $length;
    }
    header('Content-Length: ' . $size);
    ob_end_clean();
    while ($size) {
      set_time_limit(0);
      $read = ($size > 8192) ? 8192 : $size;
      $size -= $read;
      echo fread($fp, $read);
      flush();
    }
    fclose($fp);
    exit;
  }
  
  private function never_expires ($filemtime) {
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Cache-Control: max-age=31536000, public'); // 1 year (365 * 24 * 60 * 60)
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $filemtime - 15768000) . ' GMT');
  }
  
  private function css_get_contents ($file, $path) { // all we do here is resolve and translate relative links
    global $ci;
    $relative_path = str_replace('/', '../', preg_replace('/[^\/]/', '', $path));
    $css = file_get_contents($file);
    $imports = (preg_match_all('/@import\s*[\'"](\S*\.(?:css))[\'"]/i', $css, $matches)) ? $matches[1] : array();
    $urls = (preg_match_all('/url\s*\(\s*[\'"]?(\S*\.(?:css|jpe?g|gif|png|eot|ttf|otf|svg|woff2?))/i', $css, $matches)) ? $matches[1] : array();
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
    foreach ($links as $key => $value) $links[$key] = str_replace(array(BASE_URI, BASE), BASE_URL, $value); // convert to urls
    $cached = $this->cache(array_values($links));
    foreach ($links as $relative => $url) {
      if (isset($cached[$url])) {
        $links[$relative] = $relative_path . substr($cached[$url], strlen(BASE_URL));
      } else {
        unset($links[$relative]);
      }
    }
    return (!empty($links)) ? str_replace(array_keys($links), array_values($links), $css) : $css;
  }
  
}

/* End of file Resources_deliverer.php */
/* Location: ./application/libraries/Resources/drivers/Resources_deliverer.php */