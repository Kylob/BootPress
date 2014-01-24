<?php

define ('BASE', str_replace('\\', '/', dirname(__FILE__)) . '/');

class Page {

  private $domain = ''; // the BASE_URL without the protocol and trailing slash - retrieve in $this->get('domain')
  private $file = ''; // extra-curricular code that is only using the bootpress theming abilities for it's own devious purposes
  private $url = ''; // includes BASE_URL and the portion that retrieves $this->file with trailing slash - retrieve in $this->get('url')
  private $uri = ''; // between $this->url and '?' - it includes either a '.' extension, or a trailing slash - retrieve in $this->get('uri')
  private $query = ''; // a string beginning with '?' (if any params) - retrieve in $this->get('query')
  public $language = 'en';
  public $charset = 'utf-8';
  public $title = '';
  public $description = '';
  public $keywords = '';
  public $robots = true;
  public $body = '';
  private $theme = ''; // a function, or method, or not to $this->display($content) as managed in $this->theme()
  private $data = array(); // meta, ico, apple, css, style, other, js, script
  private $params = array(); // managed in $this->outreach() (private) for plugins, retrieved in $this->get('params') (public)
  private $saved = array(); // managed in $this->save($name), and retrieved in $this->get('info', $name) - for filters mainly
  private $filters = array(); // managed in $this->filter() (public), and retrieved in $this->customize() (private)
  private $loaded = array(); // include(ed) files from $this->load()
  
  public function __construct () {
    $this->domain = substr(strstr(BASE_URL, '//'), 2, -1);
    define('BASE_URI', BASE . 'websites/' . $this->domain . '/');
    $path = substr($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], strlen($this->domain) + 1);
    if (preg_match('/\.(js|css|jpe?g|gif|png|eot|ttf|otf|svg|woff|swf)$/', preg_replace('/\?.*$/', '', $path))) {
      $this->url = BASE_URL;
      $this->uri = preg_replace('/\?.*$/', '', $path);
    } else {
      $this->file_url_uri_query($path);
      $desired_url = $this->url . $this->uri . $this->query;
      $actual_url = implode('//', array(($_SERVER['SERVER_PORT'] == 443) ? 'https:' : 'http:', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
      if ($desired_url != $actual_url) $this->eject($desired_url, 301);
    }
  }
  
  public function theme ($code) {
    if (empty($this->theme) && is_callable($code)) {
      $this->theme = $code;
      return true;
    } // the first one here wins so that we can have multiple themes per site as the default is called last - returns [bool] so we know whether we got here first or not
    return false;
  }
  
  public function display ($content) {
    $content = $this->customize('content', $content);
    if (!empty($this->theme)) $content = call_user_func($this->theme, $content);
    $html = array();
    $html[] = $this->document();
    $html[] = '<head>';
    $html[] = $this->metadata();
    $html[] = $this->styles();
    $html[] = '</head>';
    $html[] = (!empty($this->body)) ? '<body ' . $this->body . '>' : '<body>';
    $html[] = '  ' . trim($content);
    $html[] = $this->scripts();
    $html[] = '</body>';
    $html[] = '</html>';
    ob_start('ob_gzhandler');
    header('Content-type: text/html; charset=' . $this->charset);
    echo $this->customize('page', implode("\n", $html));
    ob_end_flush();
  }
  
  public function access ($user, $level=1, $eject='') {
    switch ($user) {
      case 'users':
        if (!isset($_SESSION['user_id'])) $this->eject($eject);
        break;
      case 'admin':
        if (!isset($_SESSION['admin']) || $_SESSION['admin'] == 0 || $_SESSION['admin'] > $level) $this->eject($eject);
        break;
      case 'others':
        if (isset($_SESSION['user_id'])) $this->eject($eject);
        break;
    }
  }
  
  public function eject ($url='', $response='') { // http_response_code
    do { ob_end_clean(); } while (ob_get_level());
    if (empty($url)) $url = BASE_URL;
    if (is_numeric($response)) { // ie. an http_response_code
      header('Location: ' . str_replace('&amp;', '&', $url), true, (int) $response);
    } else { // just redirect then
      header('Location: ' . str_replace('&amp;', '&', $url));
    }
    exit;
  }
  
  public function next_uri ($paths='') {
    $uri = explode('/', $this->uri);
    $index = (empty($paths)) ? 0 : count((array)$paths);
    return (isset($uri[$index])) ? $uri[$index] : '';
  }
  
  public function enforce_uri ($path, $redirect='') {
    if (empty($path)) return (!empty($this->uri)) ? $this->eject($this->url . $this->query, $redirect) : true;
    if (strpos($path, '.') === false) $path  = rtrim($path, '/') . '/'; // to make sure there is a trailing slash if there is no '.' something
    return ($path != $this->uri) ? $this->eject($this->url . $path . $this->query, $redirect) : true;
  }
  
  public function plugin ($name, $params=array(), $value=true) {
    if ($name == 'file') {
      if (empty($this->file)) return '';
      $plugin = $this->file;
      $this->file = ''; // so that it can no longer be accessed
      return $this->outreach($plugin, $params);
    }
    if ($name == 'info') {
      $info = array();
      $plugin = debug_backtrace(false);
      $plugin = explode('/', str_replace('\\', '/', $plugin[0]['file']));
      $key = array_search('plugins', $plugin);
      if ($key !== false) {
        $dir = $plugin[$key + 1];
        $info['name'] = $dir;
        $info['url'] = BASE_URL . 'plugins/' . $dir . '/';
        $info['uri'] = implode('/', array_slice($plugin, 0, $key + 1)) . '/' . $dir . '/';
      }
      return $info;
    }
    if (!is_array($params)) $params = (!empty($params)) ? array($params=>$value) : array();
    foreach ($params as $key => $value) { // make it easier to check if isset()
      if (is_numeric($key)) {
        $params[$value] = true;
        unset($params[$key]);
      }
    }
    $path = 'plugins/' . $name . '/';
    $params['plugin-name'] = $name;
    $params['plugin-url'] = BASE_URL . $path;
    if (file_exists(BASE_URI . $path . 'index.php')) {
      $params['plugin-uri'] = BASE_URI . $path;
      $file = BASE_URI . $path . 'index.php';
    } elseif (file_exists(BASE . $path . 'index.php')) {
      $params['plugin-uri'] = BASE . $path;
      $file = BASE . $path . 'index.php';
    }
    return (isset($file)) ? $this->outreach($file, $params) : false;
  }
  
  public function load () {
    $files = func_get_args();
    $path = array_shift($files);
    if (is_array($path) && isset($path['plugin-uri'])) {
      $path = $path['plugin-uri'];
    }
    foreach ($files as $file) {
      if (substr($file, -1) == '/') {
        $path .= $file;
      } elseif (!isset($this->loaded[$path . $file])) {
        $this->loaded[$path . $file] = '';
        if (file_exists($path . $file)) {
          include $path . $file;
        } else {
          trigger_error($path . $file . ' does not exist');
        }
      }
    }
  }
  
  public function save ($name, $key, $value) {
    if (is_array($value)) {
      foreach ($value as $insert) $this->saved[$name][$key][] = $insert; // multiple values
    } else {
      $this->saved[$name][$key] = $value; // one value
    }
  }
  
  public function get ($var, $name='') {
    if ($var == 'info' && !empty($name)) {
      return (isset($this->saved[$name])) ? $this->saved[$name] : array();
    } elseif (in_array($var, array('domain', 'file', 'url', 'uri', 'query', 'params'))) {
      if ($var == 'params') {
        $params = $this->params;
        return array_pop($params);
      } else {
        return $this->$var;
      }
    }
    return false;
  }
  
  public function filter ($section, $function, $params, $order=10) {
    $errors = array();
    if (!in_array($section, array('document', 'metadata', 'css', 'styles', 'content', 'javascript', 'scripts', 'page'))) {
      $errors[] = "'{$section}' cannot be filtered";
    } elseif (in_array($function, array('prepend', 'append'))) {
      if (!is_string($params)) $errors[] = "When using '{$function}', \$params must be a string";
      if (in_array($section, array('css', 'javascript'))) $errors[] = "'{$section}' is an array of data, and as such may not be used with '{$function}'";
      $key = ''; // not applicable here
    } else {
      $params = (array) $params;
      $key = array_search('this', $params);
      if ($key === false) $errors[] = "'this' must be listed in the \$params so that we can give you something to filter";
      if (!is_callable($function, false, $name)) $errors[] = "'{$name}' cannot be called";
    }
    if (!empty($errors)) {
      trigger_error(implode("\n\n", $errors));
      return false;
    }
    $this->filters[$section][] = array('function'=>$function, 'params'=>$params, 'order'=>$order, 'key'=>$key);
    return true;
  }
  
  public function meta ($args) {
    if (is_string($args)) {
      $this->data('meta', $args, false);
    } else {
      foreach ($args as $key => $value) $args[$key] = $key . '="' . $value . '"';
      $this->data('meta', implode(' ', $args), false);
    }
  }
  
  public function link ($link, $prepend=false) {
    $link = (array) $link;
    if ($prepend) $link = array_reverse($link); // so they are added in the correct order
    foreach ($link as $file) {
      if (preg_match('/\.(js|css|ico|apple)$/i', $file)) {
        $split = strrpos($file, '.');
        $ext = substr($file, $split + 1);
        $name = substr($file, 0, $split);
        switch ($ext) {
          case 'js': $this->data('js', $file, $prepend); break;
          case 'css': $this->data('css', $file, $prepend); break;
          case 'ico': $this->data['ico'] = $file; break;
          case 'apple': $this->data['apple'] = $name . '.png'; break;
        }
      } elseif (substr($file, 1, 6) == 'script') {
        $this->data('script', $file, $prepend);
      } elseif (substr($file, 1, 5) == 'style') {
        $this->data('style', $file, $prepend);
      } else {
        $this->data('other', $file, $prepend);
      }
    }
  }
  
  public function url ($action='', $url='', $key='', $value=NULL) {
    if (empty($url)) $url = $this->url . $this->uri . $this->query;
    if (empty($action)) return $url;
    if ($action == 'delete' && $key == '?') return preg_replace('/[\?#].*$/', '', $url); // clean the slate
    if ($action == 'ampify') return $this->ampify($url);
    $url = str_replace ('&amp;', '&', $url);
    if (is_array($key) && in_array($action, array('add', 'delete'))) {
      if ($action == 'add') foreach ($key as $k => $v) $url = $this->url('add', $url, $k, $v);
      if ($action == 'delete') foreach ($key as $value) $url = $this->url('delete', $url, $value);
      return $url;
    }
    $fragment = parse_url ($url, PHP_URL_FRAGMENT);
    if (!empty($fragment)) {
      $fragment = '#' . $fragment; // to add on later
      $url = str_replace($fragment, '', $url);
    }
    if ($key == '#') {
      if ($action == 'delete') $fragment = '';
      elseif ($action == 'add') $fragment = '#' . urlencode($value);
      return $this->ampify($url . $fragment);
    }
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    $value = urlencode($value);
    if ($action == 'delete') {
      return $this->ampify($url . $fragment);
    } elseif ($action == 'add') {
      $insert = (strpos($url, '?') !== false) ? '&' : '?';
      return $this->ampify($url . $insert . $key . '=' . $value . $fragment);
    }
  }
  
  private function file_url_uri_query ($path) { // used in $this->__construct()
    #-- get the desired $protocol, $paths, and $extension --#
    $protocol = (substr(BASE_URL, 0, 5) == 'https') ? 'https://' : 'http://';
    $uri = explode('/', preg_replace('/\?.*$/', '', $path)); // remove the query string and break up into chunks
    $paths = array();
    foreach ($uri as $key => $value) {
      if (strpos($value, '.') !== false) {
      // if (strpos($value, '.') !== false && !is_numeric(str_replace('.', '', $value))) { // skip version numbers 1.0.0 etc.
        $value = preg_replace('/\..*$/', '', $value); // to remove any file extensions
      }
      if (!empty($value)) $paths[] = $value;
    }
    $extension = strstr(array_pop($uri), '.');
    if (empty($extension) && !empty($paths)) $extension = '/';
    #-- establish $this params --#
    $this->query = strstr($path, '?');
    $this->uri = implode('/', $paths) . $extension;
    $this->url = str_replace(array('https://', 'http://'), $protocol, BASE_URL);
    if (empty($this->uri)) {
      if (file_exists(BASE_URI . 'code/index.php')) {
        $this->file = BASE_URI . 'code/index.php';
      }
      return;
    }
    #-- search for $file and adjust $this->url and $this->uri accordingly --#
    if (isset($_GET['page']) && !preg_match('/(\?|&)page=/i', $path) && file_exists(BASE_URI . 'code/' . $_GET['page'] . '.php')) { // as managed in the .htaccess file
      $this->file = BASE_URI . 'code/' . $_GET['page'] . '.php';
    } else {
      $uri = $paths; // start over afresh
      for ($count = count($paths); $count > 0; $count--) {
        $page = implode('/', $paths);
        $file = BASE_URI . 'code/' . $page . '.php';
        if (file_exists($file)) {
          $this->file = $file;
          $this->url = ($page == 'index') ? $this->url : $this->url . $page . '/';
          $this->uri = implode('/', array_slice($uri, $count));
          if (!empty($this->uri)) $this->uri .= $extension;
          return;
        }
        array_pop($paths);
      }
    }
  }
  
  private function data ($type, $value, $prepend) { // used in $this->meta() and $this->link()
    if ($prepend) {
      if (!isset($this->data[$type])) $this->data[$type] = array();
      array_unshift($this->data[$type], $value);
    } else {
      $this->data[$type][] = $value;
    }
  }
  
  private function ampify ($string) { // used in $this->url
    return str_replace(array('&amp;', '&'), array('&', '&amp;'), $string);
  }
  
  public function outreach ($path, $params=array()) { // used in $this->plugin()
    global $page, $bp, $export;
    $this->params[] = $params;
    $export = '';
    ob_start();
    include $path;
    $html = ob_get_clean();
    if (!empty($export) || is_numeric($export) || is_array($export)) $html = $export;
    $export = '';
    array_shift($this->params);
    return $html;
  }
  
  private function customize ($section, $param) {
    if (!isset($this->filters[$section])) return $param;
    usort($this->filters[$section], create_function('$a, $b', 'return $a["order"] - $b["order"];'));
    foreach ($this->filters[$section] as $key => $filter) {
      if ($filter['function'] == 'prepend') {
        $param = $filter['params'] . $param;
      } elseif ($filter['function'] == 'append') {
        $param .= $filter['params'];
      } else {
        $filter['params'][$filter['key']] = $param;
        $param = call_user_func_array($filter['function'], $filter['params']);
      }
      unset($this->filters[$section][$key]);
    }
    return $param;
  }
  
  private function document () { // used in $this->display()
    $doc = array('<!DOCTYPE html>');
    $doc[] = '<html lang="' . $this->language . '">';
    return $this->customize('document', implode("\n", $doc));
  }
  
  private function metadata () { // used in $this->display()
    $meta = array('<meta charset="' . $this->charset . '">');
    $meta[] = '<title>' . $this->title . '</title>';
    if (!empty($this->description)) $meta[] = '<meta name="description" content="' . $this->description . '">';
    if (!empty($this->keywords)) $meta[] = '<meta name="keywords" content="' . $this->keywords . '">';
    if ($this->robots === false) $meta[] = '<meta name="robots" content="noindex, nofollow">';
    if (isset($this->data['meta'])) foreach ($this->data['meta'] as $tag) $meta[] = '<meta ' . $tag . '>';
    return $this->customize('metadata', '  ' . implode("\n  ", $meta));
  }
  
  private function styles () { // used in $this->display()
    $styles = array();
    if (isset($this->data['ico'])) {
      $styles[] = '<link rel="shortcut icon" href="' . $this->data['ico'] . '">';
    }
    if (isset($this->data['apple'])) {
      $styles[] = '<link rel="apple-touch-icon" href="' . $this->data['apple'] . '">';
    }
    $css = (isset($this->data['css'])) ? $this->data['css'] : array();
    $css = $this->customize('css', array_unique($css));
    $css = $this->plugin('Cache', array('urls'=>$css));
    foreach ($css as $url) $styles[] = '<link type="text/css" rel="stylesheet" href="' . $url . '">';
    if (isset($this->data['style'])) {
      foreach ($this->data['style'] as $style) $styles[] = $style;
    }
    if (isset($this->data['other'])) {
      foreach ($this->data['other'] as $other) $styles[] = $other;
    }
    return $this->customize('styles', '  ' . implode("\n  ", $styles));
  }
  
  private function scripts () { // used in $this->display()
    $scripts = array();
    $javascript = (isset($this->data['js'])) ? $this->data['js'] : array();
    $javascript = $this->customize('javascript', array_unique($javascript));
    $javascript = $this->plugin('Cache', array('urls'=>$javascript));
    foreach ($javascript as $url) $scripts[] = '<script type="text/javascript" src="' . $url . '"></script>';
    if (isset($this->data['script'])) {
      foreach ($this->data['script'] as $script) $scripts[] = $script;
    }
    return $this->customize('scripts', '  ' . implode("\n  ", $scripts));
  }
  
}

$page = new Page;
if (preg_match('/\.(js|css|jpe?g|gif|png|eot|ttf|otf|svg|woff|swf)$/', $page->get('uri'))) {
  $page->plugin('Cache', array('deliver'=>$page->get('uri')));
} else {
  $page->plugin('Error_Handler'); // must come before 'Sessions'
  $page->plugin('Sessions');
  $page->plugin('Users');
  if (is_admin()) notify_me_of_errors(BASE_URL . 'errors/');
}
$page->load(BASE, 'BootPress.php', 'BootPress/', 'Listings.php', 'Navbar.php', 'Table.php');
$bp = new BootPress;
if ($page->get('url') == BASE_URL) {
  switch ($page->get('uri')) {
    case 'adminer/':
      $page->access('admin');
      $page->plugin('SQLite', array('adminer'=>'edit'));
      break;
    case 'errors/':
      $page->access('admin');
      $page->plugin('Error_Handler', 'Admin');
      break;
  }
}
$page->display($page->plugin('Blog'));
unset($page, $bp);

?>