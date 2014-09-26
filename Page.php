<?php

class Page {

  private $domain = ''; // as defined in the main index.php page - retrieve in $this->get('domain')
  private $folder = ''; // a "folder" that shifts the relative context of the $this->url - retrieve in $this->get('folder')
  private $type = ''; // either 'txt', 'xml', or 'html' - retrieve in $this->get('type')
  private $url = ''; // includes BASE_URL, the "folder" (if applicable), and a trailing slash - retrieve in $this->get('url')
  private $uri = ''; // between $this->url and '?' - it does not include a trailing slash or dot extension (unless it's .txt or .xml) - retrieve in $this->get('uri')
  private $query = ''; // a string beginning with '?' (if any params) - retrieve in $this->get('query')
  public $language = 'en';
  public $charset = 'UTF-8';
  public $title = '';
  public $description = '';
  public $keywords = '';
  public $robots = true;
  public $body = '';
  private $data = array(); // meta, ico, apple, css, style, other, js, script
  private $params = array(); // managed in $this->outreach() (private) for plugins, retrieved in $this->get('params') (public)
  private $saved = array(); // managed in $this->save($name), and retrieved in $this->get('info', $name) - for filters mainly
  private $filters = array(); // managed in $this->filter() (public), and retrieved in $this->customize() (private)
  private $loaded = array(); // include(ed) files from $this->load()
  
  public function __construct ($folder=false) {
    global $ci;
    $paths = array();
    $uri = explode('/', $ci->uri->uri_string());
    foreach ($uri as $key => $value) {
      $extension = strpos($value, '.');
      if ($extension !== false) $value = substr($value, 0, $extension); // remove file extensions
      if (!empty($value)) $paths[] = $value; // remove empty "folders"
    }
    while (isset($paths[0]) && $paths[0] == 'index') $paths = array_slice($paths, 1); // remove base references to index
    // Here we make sure the $actual_url has the desired protocol, subdomain(s), and url_suffix (if there is no $extension).
    $actual_url = implode('//', array(
      ($_SERVER['SERVER_PORT'] == 443) ? 'https:' : 'http:',
      $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ));
    $extension = strstr(array_pop($uri), '.');
    if (!empty($paths) && ($extension == '.txt' || $extension == '.xml')) {
      $this->type = substr($extension, 1);
      $desired_url = $ci->config->base_url(implode('/', $paths) . $extension);
    } else {
      $this->type = 'html';
      $desired_url = $ci->config->site_url($paths) . strstr($actual_url, '?');
    }
    if ($desired_url != $actual_url) {
      header('Location: ' . $desired_url, true, 301);
      exit;
    }
    $this->url = $ci->config->site_url();
    $this->uri = $ci->uri->uri_string();
    $this->query = strstr($actual_url, '?');
    if ($folder !== false) {
      $page = $ci->input->get('page');
      if (empty($this->uri) && file_exists(BASE_URI . 'code/index.php')) {
        $this->folder = 'index';
      } elseif ($page && !preg_match('/(\?|&)page=/i', $actual_url) && file_exists(BASE_URI . 'code/' . $page . '.php')) {
        $this->folder = $page;
      } else {
        for ($count = count($paths); $count > 0; $count--) {
          $path = implode('/', $paths);
          if (file_exists(BASE_URI . 'code/' . $path . '.php')) {
            $this->folder = $path;
            $this->url .= $this->folder . '/';
            $this->uri = substr($this->uri, strlen($this->folder . '/'));
            break;
          }
          array_pop($paths);
        }
      }
    }
    $this->domain = substr(BASE_URI, strrpos(trim(BASE_URI, '/'), '/') + 1, -1);
  }
  
  public function get ($var, $name='') {
    $value = false;
    switch ($var) {
      case 'file': $value = (!empty($this->folder)) ? BASE_URI . 'code/' . $this->folder . '.php' : false; break;
      case 'info': $value = (!empty($name) && isset($this->saved[$name])) ? $this->saved[$name] : array(); break;
      case 'params':
        $params = $this->params;
        $value = array_pop($params);
        break;
      case 'domain':
      case 'folder':
      case 'type':
      case 'url':
      case 'uri':
      case 'query': $value = $this->$var; break;
    }
    return $value;
  }
  
  public function uri ($action, $arg='') {
    $uri = explode('/', $this->uri);
    switch($action) {
      case 'count': return count($uri); break;
      case 'first': return implode('/', array_slice($uri, 0, max((int) $arg, 1))); break;
      case 'after':
        if (is_numeric($arg)) return implode('/', array_slice($uri, (int) $arg));
        if (!empty($arg)) {
          if (strpos($arg, $this->url) === 0) $arg = trim(substr($arg, strlen($this->url)), '/');
          if (strpos($this->uri, $arg) === 0) return trim(substr($this->uri, strlen($arg)), '/');
        }
        return '';
        break;
      case 'number':
      case 'num': $index = (int) $arg - 1; break;
      case 'next':
        if (empty($arg)) {
          $index = 0;
        } else {
          if (!is_array($arg)) $arg = explode('/', trim($arg, '/'));
          $index = count($arg);
        }
        break;
    }
    return (isset($index) && isset($uri[$index])) ? $uri[$index] : '';
  }
  
  public function seo ($title, $slashes=false) {
    global $ci;
    $ci->load->helper(array('text', 'url'));
    $title = str_replace('-', ' ', $title);
    $title = entities_to_ascii($title);
    $title = convert_accented_characters($title);
    $title = ($slashes !== false && strpos($title, '/') !== false) ? explode('/', $title) : array($title);
    foreach ($title as $key => $value) $title[$key] = url_title($value, '-', true);
    return implode('/', $title);
  }
  
  public function enforce ($uri, $redirect=301) {
    global $ci;
    $actual_uri = $this->uri;
    if (!empty($actual_uri) && $this->type == 'html') $actual_uri .= $ci->config->item('url_suffix');
    $desired_uri = (is_array($uri)) ? implode('/', $uri) : $uri;
    if (strpos($desired_uri, BASE_URL) === 0) $desired_uri = substr($desired_uri, strlen(BASE_URL));
    $extension = strstr($desired_uri, '.');
    if (!empty($extension)) $desired_uri = substr($desired_uri, 0, strpos($desired_uri, '.'));
    $desired_uri = trim($desired_uri, '/');
    if (!empty($desired_uri)) $desired_uri .= ($extension == '.txt' || $extension == '.xml') ? $extension : $ci->config->item('url_suffix');
    if ($desired_uri != $actual_uri) $this->eject($this->url . $desired_uri . $this->query, $redirect);
  }
  
  public function access ($user, $level=1, $eject='') {
    switch ($user) {
      case 'admin': if (!is_admin($level)) $this->eject($eject); break;
      case 'others': if (is_user()) $this->eject($eject); break;
      case 'users': if (!is_user()) $this->eject($eject); break;
    }
  }
  
  public function eject ($url='', $response='') { // http_response_code
    do { ob_end_clean(); } while (ob_get_level());
    if (strpos($url, BASE_URL) === false) $url = BASE_URL . ltrim($url, '/');
    if (is_numeric($response)) { // ie. an http_response_code
      header('Location: ' . str_replace('&amp;', '&', $url), true, (int) $response);
    } else { // just redirect then
      header('Location: ' . str_replace('&amp;', '&', $url));
    }
    exit;
  }
  
  public function url ($action='', $url='', $key='', $value=NULL) {
    if (empty($url)) $url = $this->url . $this->uri . $this->query;
    if (empty($action)) return $url;
    $base = preg_replace('/[\?#].*$/', '', $url); // just the url and path
    $url = parse_url(str_replace('&amp;', '&', $url));
    if (isset($url['query'])) {
      parse_str($url['query'], $params);
    } else {
      $params = array();
    }
    if ($action == 'params') return $params;
    if ($action == 'delete' && $key == '?') return $base;
    $fragment = (!empty($url['fragment'])) ? '#' . $url['fragment'] : '';
    if ($key == '#') {
      $url = (!empty($params)) ? $base . '?' . http_build_query($params, '', '&amp;') : $base;
      if ($action == 'delete') $fragment = '';
      elseif ($action == 'add') $fragment = '#' . urlencode($value);
      return $url . $fragment;
    }
    if ($action == 'add') {
      $merge = (is_array($key)) ? $key : array($key => $value);
      foreach ($merge as $key => $value) $params[$key] = $value;
    } elseif ($action == 'delete') {
      foreach ((array) $key as $value) unset($params[$value]);
    }
    $params = http_build_query($params, '', '&amp;');
    $query = (!empty($params)) ? '?' . $params : '';
    return $base . $query . $fragment;
  }
  
  public function post ($params) {
    return '<!--post' . json_encode($params) . '-->';
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
    if ($prepend !== false) $link = array_reverse($link); // so they are added in the correct order
    foreach ($link as $file) {
      $frag = (strpos($file, '<') === false) ? strstr($file, '#') : '';
      if (!empty($frag)) $file = substr($file, 0, -strlen($frag));
      if (preg_match('/\.(js|css|ico|apple)$/i', $file)) {
        $split = strrpos($file, '.');
        $ext = substr($file, $split + 1);
        $name = substr($file, 0, $split);
        switch ($ext) {
          case 'js': $this->data('js', $file . $frag, $prepend); break;
          case 'css': $this->data('css', $file . $frag, $prepend); break;
          case 'ico': $this->data['ico'] = $file . $frag; break;
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
  
  public function id ($prefix='') {
    static $id = 0;
    $id++;
    $result = '';
    $lookup = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
    $number = $id;
    if ($number < 100) $lookup = array_slice($lookup, 4);
    foreach ($lookup as $roman => $value) {
      $matches = intval($number / $value);
      $result .= str_repeat($roman, $matches);
      $number = $number % $value;
    }
    return $prefix . $result;
  }
  
  public function plugin ($name, $params=array(), $value=true) {
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
    $params['plugin']['params'] = array_keys($params); // so we check if in_array()
    $path = 'plugins/' . $name . '/';
    $params['plugin']['name'] = $name;
    $params['plugin']['url'] = BASE_URL . $path;
    if (file_exists(BASE_URI . $path . 'index.php')) {
      $params['plugin']['uri'] = BASE_URI . $path;
      $file = BASE_URI . $path . 'index.php';
    } elseif (file_exists(BASE . $path . 'index.php')) {
      $params['plugin']['uri'] = BASE . $path;
      $file = BASE . $path . 'index.php';
    }
    return (isset($file)) ? $this->outreach($file, $params) : false;
  }
  
  public function load () {
    $files = func_get_args();
    $path = array_shift($files);
    if (is_array($path) && isset($path['uri'])) $path = $path['uri'];
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
  
  public function filter ($section, $function, $params, $order=10) {
    $errors = array();
    if (!in_array($section, array('document', 'metadata', 'css', 'styles', 'header', 'content', 'sidebar', 'footer', 'layout', 'javascript', 'scripts', 'page'))) {
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
    if (empty($errors)) {
      $this->filters[$section][] = array('function'=>$function, 'params'=>$params, 'order'=>$order, 'key'=>$key);
    } else {
      trigger_error(implode("\n\n", $errors));
    }
  }
  
  public function customize ($section, $param) {
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
  
  public function outreach ($path, $params=array()) { // used in $this->plugin()
    global $bp, $ci, $page, $export;
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
  
  public function display ($content) {
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
    return $this->customize('page', implode("\n", $html));
  }
  
  private function data ($type, $value, $prepend) { // used in $this->meta() and $this->link()
    if ($prepend !== false) {
      if (!isset($this->data[$type])) $this->data[$type] = array();
      array_unshift($this->data[$type], $value);
    } else {
      $this->data[$type][] = $value;
    }
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
    foreach ($javascript as $url) $scripts[] = '<script type="text/javascript" src="' . $url . '"></script>';
    if (isset($this->data['script'])) {
      foreach ($this->data['script'] as $script) $scripts[] = $script;
    }
    return $this->customize('scripts', '  ' . implode("\n  ", $scripts));
  }
  
}

function in_session ($reset=false) {
  global $ci;
  static $session = null;
  if (is_null($session) || $reset) {
    if ($ci->input->cookie('ci_session')) {
      $ci->session->load_driver('cookie');
      $session = true;
    } else {
      $session = false;
    }
  }
  return $session;
}

function is_user ($user_id='') {
  global $ci;
  if (!in_session()) return false;
  $user = $ci->session->cookie->userdata('user_id');
  if (empty($user_id)) return (!empty($user)) ? $user : false; // an id > 0 or false
  return (!empty($user) && $user === $user_id) ? $user : false;
}

function is_admin ($level=1) {
  global $ci;
  if (!in_session()) return false;
  $admin = $ci->session->cookie->userdata('admin');
  return (!empty($admin) && $admin <= $level) ? $admin : false;
}

function in_group ($group, $check='all') { // or 'any'
  global $ci;
  if ($user_id = is_user()) return $ci->blog->auth->user_in_group($user_id, $group, $check);
  return false;
}

?>