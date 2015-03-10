<?php

class Page {
  
  private $url = ''; // Includes BASE_URL, the "folder" (if applicable), and a trailing slash
  private $uri = ''; // Between $this->url and '?'
  private $type = ''; // $this->uri's file extension (if any)
  private $query = ''; // A string beginning with '?' (if any params)
  private $domain = ''; // As defined in the main index.php page
  private $folder = null; // A "folder" (directory) that shifts the relative context of the $this->url and $this->uri
  public $language = 'en';
  public $charset = 'UTF-8';
  public $title = '';
  public $description = '';
  public $keywords = '';
  public $robots = true;
  public $body = '';
  public $theme = 'default';
  public $vars = array(); // an extra array that can be stuffed with whatever values you please - for themes mainly
  private $data = array(); // meta, ico, apple, css, style, other, js, script
  private $params = array(); // managed in $this->outreach() (private) for plugins, retrieved in $this->get('params') (public)
  private $saved = array(); // managed in $this->save($name), and retrieved in $this->get('info', $name) - for filters mainly
  private $filters = array(); // managed in $this->filter() (public), and retrieved in $this->customize() (private)
  private $loaded = array(); // include(ed) files from $this->load()
  
  public function __construct () {
    global $ci;	
    $this->url = BASE_URL;
    $this->uri = $ci->uri->uri_string();
    $this->type = pathinfo($this->uri, PATHINFO_EXTENSION);
    if (empty($this->type)) $this->type = 'html';
    $this->query = strstr($_SERVER['REQUEST_URI'], '?');
    $this->domain = substr(BASE_URI, strrpos(trim(BASE_URI, '/'), '/') + 1, -1);
    $this->charset = $ci->config->item('charset');
  }
  
  public function folder ($folders='') {
    global $ci;
    if (is_null($this->folder) && !empty($folders)) {
      $this->folder = false;
      if (empty($this->uri) && is_file($folders . 'index/index.php')) {
        $this->folder = $folders . 'index/';
      } elseif (($folder = $ci->input->get('page')) && !preg_match('/(\?|&)page=/i', $this->query) && is_file($folders . $folder . '/index.php')) {
        $this->folder = $folders . $folder . '/';
      } else {
        $paths = explode('/', $this->uri);
        for ($count=count($paths); $count>0; $count--) {
          $path = implode('/', $paths);
          if (is_file($folders . $path . '/index.php')) {
            $this->url .= $path . '/';
            $this->uri = substr($this->uri, strlen($path . '/'));
            $this->folder = $folders . $path . '/';
            break;
          }
          array_pop($paths);
        }
      }
    }
    return $this->folder;
  }
  
  public function set ($var, $value='') {
    if (!is_array($var)) $var = array($var => $value);
    foreach ($var as $key => $value) {
      switch ($key) {
        case 'language':
        case 'charset':
        case 'title':
        case 'description':
        case 'keywords':
        case 'robots':
        case 'body':
        case 'theme':
          $this->$key = $value;
          break;
        default:
          $this->vars[$key] = $value;
          break;
      }
    }
  }
  
  public function __get ($name) {
    return (isset($this->vars[$name])) ? $this->vars[$name] : null;
  }
  
  public function get ($var, $name='') {
    $value = false;
    switch ($var) {
      case 'info': $value = (!empty($name) && isset($this->saved[$name])) ? $this->saved[$name] : array(); break;
      case 'params':
        $params = $this->params;
        $value = array_pop($params);
        break;
      case 'url':
      case 'uri':
      case 'type':
      case 'query':
      case 'domain': $value = $this->$var; break;
    }
    return $value;
  }
  
  ##
  # 
  # kudos to: https://github.com/dannyvankooten/AltoRouter - we copied the complicated stuff from them verbatim
  # 
  # [ match_type : param_name ]
  # 
  # *                    // Match all request URIs
  # [i]                  // Match an integer
  # [i:id]               // Match an integer as 'id'
  # [a:action]           // Match alphanumeric characters as 'action'
  # [h:key]              // Match hexadecimal characters as 'key'
  # [:action]            // Match anything up to the next / or end of the URI as 'action'
  # [create|edit:action] // Match either 'create' or 'edit' as 'action'
  # [*]                  // Catch all (lazy)
  # [*:trailing]         // Catch all as 'trailing' (lazy)
  # [**:trailing]        // Catch all (possessive - will match the rest of the URI)
  # .[:format]?          // Match an optional parameter 'format' - a / or . before the block is also optional
  # 
  # posts/[*:title][i:id]      // Matches "posts/this-is-a-title-123"
  # output.[xml|json:format]?  // Matches "output", "output.xml", "output.json"
  # [:controller]?/[:action]?  // Matches the typical controller/action format
  # @\.(json|csv)$             // Match all requests that end with '.json' or '.csv'
  # !@^admin/                  // Match all requests that _don't_ start with admin/
  # 
  # if ($route = $page->routes(array(
  #   '' => 'index.php',
  #   'plans/[farout|groovy:future]' => 'plans.php', // matches "plans/farout", "plans/groovy"
  #   'charge/[i:customer]' => 'charge.php', // matches "charge/4815162342"
  #   'payment/[:status]' => 'payment.php', // matches "payment/[^/\.]+"
  #   'api/[*:key]/[*:name]' => 'api.php', // matches "api/123/456/gadd" and name = "456/gadd"
  #   'blog/[*:title]-[i:id]' => 'blog.php', // matches "blog/this-is-a-title-123"
  #   'posts/[create|edit:action]?/[i:id]?' => 'posts.php', // matches "posts", "posts/123", "posts/create", "posts/create/123"
  #   'report.[xml|csv|json:format]?' => 'report.php', // matches "report", "report.xml", "report.csv", "report.json"
  #   '@\.(jpg|gif|png)$' => 'image.php', // matches anything that ends with ".jpg", ".gif", or ".png"
  #   '[:controller]?/[:method]?/[**:uri]?' => 'class.php' // for everything else there is this
  # ), 'argh/freak/out')) {
  #   
  #   $html .= '<pre>routes: ' . print_r($route, true) . '</pre>';
  #   
  # }
  # 
  ##
  public function routes ($routes, $uri=null, $types=array()) {
    global $ci;
    $uri = (is_null($uri)) ? $this->uri : trim($uri, '/');
    $types = array_merge(array(
      'i'  => '[0-9]++', // integer
      'a'  => '[0-9A-Za-z]++', // alphanumeric
      'h'  => '[0-9A-Fa-f]++', // hexadecimal
      '*'  => '.+?', // anything (lazy)
      '**' => '.++', // anything (possessive)
      ''   => '[^/\.]++' // not a slash (/) or period (.)
    ), $types);
    $params = array();
    $match = false;
    foreach ((array) $routes as $_route => $target) {
      $_route = (is_int($_route)) ? trim($target, '/') : trim($_route, '/');
      if (empty($_route)) {
        $match = (empty($uri)) ? true : false;
      } elseif ($_route === '*') {
        $match = true;
      } elseif (isset($_route[0]) && $_route[0] === '@') {
        $match = preg_match('`' . substr($_route, 1) . '`u', $uri, $params);
      } else {
        $route = null;
        $regex = false;
        $j = $i = 0;
        $n = isset($_route[0]) ? $_route[0] : null;
        while (true) { // Find the longest non-regex substring and match it against the URI
          if (!isset($_route[$i])) break;
          if (false === $regex) {
            $c = $n;
            $regex = $c === '[' || $c === '(' || $c === '.';
            if (false === $regex && false !== isset($_route[$i+1])) {
              $n = $_route[$i + 1];
              $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
            }
            if (false === $regex && $c !== '/' && (!isset($uri[$j]) || $c !== $uri[$j])) continue 2;
            $j++;
          }
          $route .= $_route[$i++];
        }
        $match = preg_match($this->altoRouter($route, $types), $uri, $params);
      }
      if (($match == true || $match > 0)) {
        if ($params) foreach ($params as $key => $value) if (is_numeric($key)) unset($params[$key]);
        return array(
          'target' => $target,
          'params' => $params,
          'method' => ($method = $ci->input->server('REQUEST_METHOD')) ? $method : 'GET'
        );
      }
    }
    return false;
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
    global $ci;
    if (empty($url)) {
      $url = BASE_URL;
    } elseif (!strpos($url, '://') || strpos($url, BASE_URL) !== false) {
      $parts = explode('?', $url);
      $uri = trim(str_replace(BASE_URL, '', array_shift($parts)), '/');
      $query = (!empty($parts)) ? '?' . htmlspecialchars_decode(implode('?', $parts)) : '';
      $type = pathinfo($uri, PATHINFO_EXTENSION);
      $url = (empty($type)) ? $ci->config->site_url($uri) . $query : $ci->config->base_url($uri) . $query;
    }
    do { ob_end_clean(); } while (ob_get_level());
    if (is_numeric($response)) { // ie. an http_response_code
      header('Location: ' . $url, true, (int) $response);
    } else { // just redirect then
      header('Location: ' . $url);
    }
    exit;
  }
  
  public function url ($action='', $url='', $key='', $value=NULL) {
    global $ci;
    if (!empty($action)) {
      if (is_array($action) && isset($action['url'])) {
        $href = $action['url'];
      } elseif (substr($action, 0, 4) == 'http') {
        $href = $action;
      } elseif (in_array($action, array('base', 'blog', 'admin', 'folder', 'post', 'theme'))) {
        switch ($action) {
          case 'base': $href = BASE_URL; break;
          case 'blog': $href = BASE_URL . BLOG; break;
          case 'admin': $href = BASE_URL . ADMIN; break;
          case 'folder': $href = $this->url; break;
          case 'post':
          case 'theme': $href = $ci->blog->url; break;
        }
      }
      if (isset($href)) {
        $href = array_merge((array) $href, (array_slice(func_get_args(), 1)));
        $href = implode('/', array_map('trim', $href, array_fill(0, count($href), '/')));
        return ($href == substr(BASE_URL, 0, -1)) ? BASE_URL : $href;
      }
    }
    if (empty($url)) {
      if (empty($this->uri) && !empty($this->folder)) {
        $url = substr($this->url, 0 , -1) . $this->query;
      } else {
        $url = $this->url . $this->uri . $this->query;
      }
      if (empty($action)) return $url;
    }
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
  
  public function seo ($title, $slashes=false) {
    global $ci;
    $ci->load->helper(array('text', 'url'));
    $title = str_replace(array('\\', '_', '-'), array('/', ' ', ' '), $title);
    $title = entities_to_ascii($title);
    $title = convert_accented_characters($title);
    $title = ($slashes !== false && strpos($title, '/') !== false) ? array_filter(explode('/', $title)) : array($title);
    foreach ($title as $key => $value) $title[$key] = url_title($value, '-', true);
    return implode('/', $title);
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
  
  public function style ($code) {
    if (is_array($code)) foreach ($code as $key => $css) if (is_array($css)) $code[$key] = "{$key} {\n\t\t" . implode("\n\t\t", $css) . "\n\t}";
    $this->link('<style>' . (is_array($code) ? "\n\t" . implode("\n\t", $code) . "\n  " : trim($code)) . '</style>');
  }
  
  public function script ($code) {
    $this->link('<script>' . (is_array($code) ? "\n\t" . implode("\n\t", $code) . "\n  " : trim($code)) . '</script>');
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
    if (is_file(BASE_URI . $path . 'index.php')) {
      $params['plugin']['uri'] = BASE_URI . $path;
      $file = BASE_URI . $path . 'index.php';
    } elseif (is_file(BASE . $path . 'index.php')) {
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
        if (is_file($path . $file)) {
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
    if (!in_array($section, array('document', 'metadata', 'css', 'styles', 'content', 'layout', 'javascript', 'scripts', 'page'))) {
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
    return $this->customize('document', implode("\n", array(
      '<!DOCTYPE html>',
      '<html lang="' . $this->language . '">'
    )));
  }
  
  private function metadata () { // used in $this->display()
    $meta = array('<meta charset="' . $this->charset . '">');
    $meta[] = '<title>' . trim($this->title) . '</title>';
    if (!empty($this->description)) $meta[] = '<meta name="description" content="' . trim($this->description) . '">';
    if (!empty($this->keywords)) $meta[] = '<meta name="keywords" content="' . trim($this->keywords) . '">';
    if ($this->robots !== true) {
      $meta[] = ($this->robots) ? '<meta name="robots" content="' . $this->robots . '">' : '<meta name="robots" content="noindex, nofollow">'; // ie. false
    }
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
    foreach ($css as $url) $styles[] = '<link rel="stylesheet" href="' . $url . '">';
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
    foreach ($javascript as $url) $scripts[] = '<script src="' . $url . '"></script>';
    if (isset($this->data['script'])) {
      foreach ($this->data['script'] as $script) $scripts[] = $script;
    }
    return $this->customize('scripts', '  ' . implode("\n  ", $scripts));
  }
  
  private function altoRouter ($route, $types) {
    if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
      foreach($matches as $match) {
        list($block, $pre, $type, $param, $optional) = $match;
        if (isset($types[$type])) $type = $types[$type];
        if ($pre === '.') $pre = '\.';
        $pattern = '(?:' . ($pre !== '' ? $pre : null) . '(' . ($param !== '' ? "?P<$param>" : null) . $type . '))' . ($optional !== '' ? '?' : null);
        $route = str_replace($block, $pattern, $route);
      }
    }
    return "`^$route$`u";
  }
  
}

function in_session ($reset=false) {
  global $ci;
  static $session = null;
  if (is_null($session) || $reset) {
    if ($ci->input->cookie('ci_session')) {
      $ci->load->driver('auth');
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
  if ($user_id = is_user()) return $ci->auth->user_in_group($user_id, $group, $check);
  return false;
}

?>