<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Controller extends CI_Controller {

  public $poster;
  private $model;
  
  public function _remap ($model) {
    global $admin, $ci, $page;
    $this->benchmark->mark('page_setup_start');
    $admin = array_merge(array('name'=>'', 'email'=>'', 'password'=>'', 'folder'=>''), (array) $admin);
    define('ADMIN', !empty($admin['folder']) ? $admin['folder'] : 'admin');
    define('BASE_URL', $this->config->base_url());
    if (!defined('BLOG')) define('BLOG', '');
    $ci = $this; // $ci =& get_instance(); doesn't cut it so ...
    $this->poster = md5(BASE_URL);
    $this->model = $model;
    $errors = '';
    do { $errors .= ob_get_clean(); } while (ob_get_level());
    ob_start();
    session_cache_limiter('');
    if ($this->model == '#cache#') {
      $file = func_get_arg(1);
      $type = array_pop($file);
      $this->load->driver('resources');
      return $this->resources->deliverer->view(implode('/', $file), $type);
    }
    $this->load->driver(array('session', 'sitemap'));
    if ($html = $this->sitemap->cached()) {
      $this->benchmark->mark('page_setup_end');
      $this->benchmark->mark('page_display_start');
    } else {
      require_once(BASE . 'Page.php');
      $page = new Page;
      $this->benchmark->mark('page_setup_end');
      $this->benchmark->mark('page_content_start');
      if ($this->model == $this->poster) {
        $this->delay_flashdata();
        $this->log('users');
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
        header('Cache-Control: max-age=0, must-revalidate');
        header('Content-Length: ' . strlen($image));
        header('Content-Type: image/gif');
        header('Pragma: no-cache');
        exit($image);
      } elseif ($template = $this->input->post($this->poster)) {
        $this->delay_flashdata();
        $data = array();
        $file = BASE_URI . 'themes/' . $page->seo($template) . '/post.php';
        if (is_file($file)) {
          $this->load->driver('blog', array('role'=>'#post#'));
          $export = $page->outreach($file);
          if (is_array($export)) $data = $this->compile($export);
          if (isset($data['css'])) $data = array('css'=>$data['css']) + $data; // move css to the beginning
          if (isset($data['javascript'])) { // move javascript to the end
            $javascript = $data['javascript'];
            unset($data['javascript']);
            $data['javascript'] = $javascript;
          }
        }
        $data = json_encode($this->filter_links($data));
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($data));
        exit($data);
      } elseif ($this->model == '#sitemap#') {
        $params = func_get_arg(1);
        $method = array_shift($params); // Either 'robots' or 'xml'
        $html = $this->sitemap->$method(array_shift($params));
      } else {
        if ($this->model == ADMIN) {
          $this->load->driver('blog', array('role'=>'#admin#'));
          if ($route = $page->routes(array(
            ADMIN,
            ADMIN . '/[blog:view]/[published|unpublished|posts|pages' . (is_admin(1) ? '|authors|categories|tags|templates' : null) . ':folder]?',
            ADMIN . '/[sitemap|image' . (is_admin(1) ? '|setup|errors|plugins|folders|databases' : null) . ':view]',
            ADMIN . '/[users:view]/[logout' . (is_admin(1) ? '|register|edit|list' : null) . ':action]?',
            ADMIN . '/[themes:view]/[preview:action]?/[:theme]?/[bootstrap\.less:less]?',
            ADMIN . '/[analytics:view]/[users|pages|referrers:method]?'
          ))) {
            if (!isset($route['params']['view'])) $page->eject(ADMIN . '/blog');
            $view = $route['params']['view'];
            $this->load->driver('admin', array('file'=>$view));
            $html = $this->admin->$view->view($route['params']);
          } else {
            $page->eject(BASE_URL . ADMIN . '/users');
          }
        } elseif ($folder = $page->folder(BASE_URI . 'folders/')) {
          $this->load->driver('blog', array('role'=>'#folder#'));
          $html = $page->outreach($folder . 'index.php');
        } else {
          $this->load->driver('blog', array('role'=>'#blog#'));
          if ($file = $this->blog->file($page->get('uri'))) {
            $html = $this->blog->pages->post($file);
          } elseif ($route = $page->routes(array(
            BLOG,
            BLOG . '/[atom|rss:method].xml',
            BLOG . '/[archives:method]/[i:year]?/[i:month]?/[i:day]?',
            BLOG . '/[authors|tags:method]/[:uri]?',
            BLOG . '/[:method]' => 'category'
          ))) {
            $method = (isset($route['params']['method'])) ? $route['params']['method'] : ($this->input->get('search') ? 'search' : 'index');
            if ($route['target'] == 'category') {
              if ($route['params'] = $this->blog->db->row('SELECT id, uri, category, tags FROM categories WHERE uri = ?', array($route['params']['method']))) {
                $method = 'category';
              } else {
                show_404($page->url());
              }
            }
            $html = $this->blog->pages->$method($route['params']);
          } else {
            show_404($page->url());
          }
        }
      }
      $this->benchmark->mark('page_content_end');
      $this->benchmark->mark('page_display_start');
      if ($page->get('type') == 'html') {
        $html = $this->layout($html);
        do { $errors .= ob_get_clean(); } while (ob_get_level());
        $html = $this->filter_links($page->display($html . $errors));
      }
      if ($this->output->get_content_type() == 'text/html') $this->output->set_content_type($page->get('type'));
      if (trim($errors) == '') $this->sitemap->update($html);
    }
    $this->log('hits');
    if ($this->session->native->userdata('profiler') && $this->output->get_content_type() == 'text/html') { // && $this->model != ADMIN
      $this->session->select_driver('native');
      $this->output->enable_profiler(true);
    } elseif (trim($errors) == '') {
      $this->sitemap->may_change();
    }
    do { $errors .= ob_get_clean(); } while (ob_get_level()); // for compression's sake
    $this->load->view('view', array('html'=>$html));
    $this->benchmark->mark('page_display_end');
  }
  
  public function log ($type, $resource=false) { // made public so that we can log resources
    global $page;
    $file = fopen(BASE_URI . 'databases/analytics.csv', 'ab');
    $analytics = $this->session->native->userdata('analytics');
    switch ($type) {
      case 'hits':
        $microtime = microtime(true); // so that the session and the hit are the same
        if ($resource !== false && is_string($resource)) {
          list($uri, $query) = explode('#', $resource . '#');
        } else {
          $uri = $this->uri->uri_string();
          $query = strstr($_SERVER['REQUEST_URI'], '?');
        }
        if (!is_array($analytics)) {
          $analytics['id'] = session_id();
          $analytics['hits'] = 0;
          $this->load->library('user_agent');
          $referrer = $this->agent->referrer();
          fputcsv($file, array(
            'table' => 'sessions',
            'session' => $analytics['id'],
            'time' => (int) $microtime,
            'agent' => $this->agent->agent_string(),
            'platform' => $this->agent->platform(),
            'browser' => $this->agent->browser(),
            'version' => $this->agent->version(),
            'mobile' => $this->agent->mobile(),
            'robot' => $this->agent->robot(),
            'uri' => $uri,
            'query' => $query,
            'type' => $this->output->get_content_type(),
            'referrer' => (strpos('r' . trim($referrer), BASE_URL) != 1) ? $referrer : '',
            'ip' => $this->input->ip_address()
          ));
        }
        $analytics['hits']++;
        fputcsv($file, array(
          'table' => 'hits',
          'hits' => $analytics['hits'],
          'session' => $analytics['id'],
          'time' => $microtime,
          'uri' => $uri,
          'query' => $query,
          'type' => $this->output->get_content_type()
        ));
        break;
      case 'users':
        if (is_array($analytics)) { // it should be, but just in case ...
          $user_id = is_user();
          $admin = is_admin();
          if (!is_admin(1)) $this->sitemap->increment_views();
          $analytics['offset'] = (int) $this->input->get('offset');
          $zones = array('-12'=>'UM12', '-11'=>'UM11', '-10'=>'UM10', '-9.9'=>'UM95', '-9'=>'UM9', '-8'=>'UM8', '-7'=>'UM7', '-6'=>'UM6', '-5'=>'UM5', '-4.5'=>'UM45', '-4'=>'UM4', '-3.5'=>'UM35', '-3'=>'UM3', '-2'=>'UM2', '-1'=>'UM1', '0'=>'UTC', '1'=>'UP1', '2'=>'UP2', '3'=>'UP3', '3.5'=>'UP35', '4'=>'UP4', '4.5'=>'UP45', '5'=>'UP5', '5.5'=>'UP55', '5.75'=>'UP575', '6'=>'UP6', '6.5'=>'UP65', '7'=>'UP7', '8'=>'UP8', '8.75'=>'UP875', '9'=>'UP9', '9.5'=>'UP95', '10'=>'UP10', '10.5'=>'UP105', '11'=>'UP11', '11.5'=>'UP115', '12'=>'UP12', '12.75'=>'UP1275', '13'=>'UP13', '14'=>'UP14');
          $timezone = (string) $this->input->get('timezone');
          if (isset($zones[$timezone])) $timezone = $zones[$timezone];
          fputcsv($file, array(
            'table' => 'users',
            'hits' => $analytics['hits'],
            'session_id' => $analytics['id'],
            'time' => microtime(true),
            'referrer' => (string) $this->input->get('referrer'),
            'width' => (int) $this->input->get('width'),
            'height' => (int) $this->input->get('height'),
            'hemisphere' => (string) $this->input->get('hemisphere'),
            'timezone' => $timezone,
            'dst' => (int) $this->input->get('dst'),
            'offset' => $analytics['offset'],
            'user_id' => $user_id ? $user_id : 0,
            'admin' => $admin ? $admin : 0
          ));
        }
        break;
    }
    $this->session->native->set_userdata('analytics', $analytics);
    fclose($file);
    if ($type == 'users' && mt_rand(1, ini_get('session.gc_divisor')) <= ini_get('session.gc_probability')) {
      $this->load->driver('analytics');
      $this->analytics->process_hits();
      $this->sitemap->refresh();
    }
  }
  
  public function default_value ($str, $default) { // used as a form validation callback, and for anything else you would like
    return (empty($str)) ? $default : $str;
  }
  
  public function delay_flashdata () {
    foreach (array_keys($this->session->native->userdata()) as $key) {
      if ($pos = strpos($key, ':old:')) $this->session->native->keep_flashdata(substr($key, $pos + 5));
    }
  }
  
  public function filter_links ($html) { // made public so that it can be called elsewhere when ending the script prematurely if desired
    if (empty($html)) return $html;
    $array = (is_array($html)) ? $html : false;
    if ($array !== false) {
      $html = array();
      array_walk_recursive($array, function($value) use (&$html) { $html[] = $value; });
      $html = implode(' ', $html);
    }
    $url = preg_quote(BASE_URL, '/');
    $chars = $this->config->item('permitted_uri_chars');
    preg_match_all('/(' . $url . ')([' . $chars . '\/]+)(#[' . $chars . '\/]+)?/i', $html, $matches);
    $cache = array_flip(array('jpeg', 'jpg', 'gif', 'png', 'ico', 'js', 'css', 'pdf', 'ttf', 'otf', 'svg', 'eot', 'woff', 'swf', 'tar', 'tgz', 'gz', 'zip', 'csv', 'xlsx', 'xls', 'xl', 'word', 'docx', 'doc', 'ppt', 'mp3', 'ogg', 'wav', 'mpeg', 'mpe', 'mpg', 'mov', 'qt', 'psd'));
    $resources = array(); // we'll run these through the cache machine
    $types = array(); // $resources['uri'] = '.ext';
    $links = array(); // improperly suffixed uri's
    asort($matches[2]); // sort array while maintaining index association
    foreach(array_map('trim', array_unique($matches[2])) as $key => $uri) {
      $dot = strrpos($uri, '.');
      $ext = ($dot) ? strtolower(substr($uri, $dot + 1)) : '';
      if (isset($cache[$ext])) { // we want to cache this resource
        $resources[] = BASE_URL . $uri;
        $types[$uri] = '.' . $ext;
      } elseif ($ext == 'txt' || $ext == 'xml' || $ext == 'less') {
        // We'll place these at the top to make sure they are preserved
        $links = array_merge(array(BASE_URL . $uri => BASE_URL . $uri), $links);
      } else { // we are ensuring the correct url_suffix
        $suffixed = ($dot) ? substr($uri, 0, $dot) : rtrim($uri, '/');
        $suffixed .= $this->config->item('url_suffix');
        if ($uri != $suffixed) $links[BASE_URL . $uri] = BASE_URL . $suffixed;
      }
    }
    $rnr = array(); // for the final remove and replace operation
    if (!empty($resources)) {
      $this->load->driver('resources');
      $cached = $this->resources->cache($resources);
      foreach ($cached as $key => $value) if ($key != $value) $rnr[$key] = $value;
      $rename = array();
      foreach ($matches[3] as $key => $frag) {
        $uri = $matches[2][$key];
        if (!empty($frag) && isset($rnr[BASE_URL . $uri]) && !isset($rename[BASE_URL . $uri . $frag])) {
          // A ':' (%3A) will cause the path to fail (it redirects to the index page for some reason) even though it is within CodeIgniter's permitted_uri_chars
          // See: https://ellislab.com/forums/viewthread/183230/ and http://php.net/manual/en/function.urlencode.php#111410
          $replace = '$1$2/' . implode("/", array_map("rawurlencode", explode("/", substr($frag, 1)))) . $types[$uri];
          $rename[BASE_URL . $uri . $frag] = preg_replace('/(' . $url . ')([1-9a-z]{5}[0]?)+(.*)/i', $replace, $rnr[BASE_URL . $uri]);
          unset($matches[2][$key]);
          if (array_search($uri, $matches[2]) === false) unset($rnr[BASE_URL . $uri]); // no longer needed
        }
      }
      if (!empty($rename)) $rnr = array_merge($rename, $rnr);
    }
    if (!empty($links)) { // these come last because we don't care about their frag's
      krsort($links);
      foreach ($links as $uri => $suffixed) {
        $rnr[$uri] = '<--{' . md5($uri . rand(1000, 9999)) . '}-->';
        $links[$rnr[$uri]] = $suffixed;
        unset($links[$uri]);
      }
      $rnr = array_merge($rnr, $links);
    }
    if (!empty($rnr)) return str_replace(array_keys($rnr), array_values($rnr), $array ? $array : $html);
    return $array ? $array : $html;
  }
  
  public function load_database () {
    global $page;
    static $db = null;
    if (is_null($db)) {
      $config = array();
      $query_builder = false;
      if (is_file(BASE_URI . 'blog/database.php')) include(BASE_URI . 'blog/database.php');
      switch (isset($config['dbdriver']) ? $config['dbdriver'] : '') {
        case 'oci8': $config = array('oracle' => $config); break;
        case 'mysqli': $config = array('mysql' => $config); break;
        case 'mysql':
        case 'mssql':
        case 'postgre': $config = array($config['dbdriver'] => $config); break;
        default: $config = array('sqlite' => BASE_URI . 'databases/users.db'); break;
      }
      $config['profile'] = false;
      $config['query_builder'] = true;
      $db = $page->plugin('Database', $config);
      if (isset($config['sqlite']) && $db->created) {
        $db->create('ci_sessions', array(
          'session_id' => 'TEXT NOT NULL DEFAULT ""',
          'ip_address' => 'TEXT NOT NULL DEFAULT ""',
          'user_agent' => 'TEXT NOT NULL DEFAULT ""',
          'user_data' => 'TEXT NOT NULL DEFAULT ""',
          'user_id' => 'INTEGER NOT NULL DEFAULT 0',
          'last_activity' => 'INTEGER NOT NULL DEFAULT 0'
        ), 'last_activity, user_id');
        $db->create('ci_users', array(
          'id' => 'INTEGER PRIMARY KEY',
          'name' => 'TEXT NOT NULL DEFAULT ""',
          'email' => 'TEXT UNIQUE COLLATE NOCASE',
          'password' => 'TEXT NOT NULL DEFAULT ""',
          'admin' => 'INTEGER NOT NULL DEFAULT 0',
          'approved' => 'TEXT NOT NULL DEFAULT "Y"',
          'registered' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ));
        $db->create('ci_groups', array(
          'id' => 'INTEGER PRIMARY KEY',
          'name' => 'TEXT UNIQUE COLLATE NOCASE'
        ));
        $db->create('ci_user_groups', array(
          'user_id' => 'INTEGER NOT NULL DEFAULT 0',
          'group_id' => 'INTEGER NOT NULL DEFAULT 0'
        ), array('unique'=>'user_id, group_id'));
      }
      $this->db = $db->ci;
    }
    return $db;
  }
  
  private function layout ($content) {
    global $ci, $page;
    if ($this->model == ADMIN) {
      $page->plugin('CDN', array('prepend', 'links'=>array(
        'bootstrap/' . $ci->blog->bootstrap . '/css/bootstrap.min.css',
        'bootstrap/' . $ci->blog->bootstrap . '/js/bootstrap.min.js',
        'fontawesome/4.2.0/css/font-awesome.min.css'
      )));
      $page->plugin('jQuery');
      $this->analytics();
      return $content;
    }
    $content = $page->customize('content', $content);
    if (($preview = $this->session->native->tempdata('preview_layout')) && is_admin(2)) $page->theme = $preview;
    if ($page->theme !== false) {
      $this->analytics();
      if (strpos($page->theme, BASE_URI) !== false) {
        if (is_file($page->theme)) $content = $page->outreach($page->theme, array('content'=>$content));
      } else {
        if (is_file(BASE_URI . 'themes/' . $page->theme . '/index.tpl')) {
          $this->blog->set('layout', BASE_URL . 'themes/' . $page->theme . '/');
          $layout = BASE_URI . 'themes/' . $page->theme . '/index.tpl';
        } elseif (is_file(BASE_URI . 'themes/default/index.tpl')) {
          $page->theme = 'default';
          $this->blog->set('layout', BASE_URL . 'themes/' . $page->theme . '/');
          $layout = BASE_URI . 'themes/' . $page->theme . '/index.tpl';
        } else {
          $this->blog->set('layout');
          $layout = $this->blog->templates . 'layout.tpl';
        }
        if (is_file(BASE_URI . 'themes/' . $page->theme . '/post.php')) {
          $page->plugin('jQuery', 'code', '$.ajax({type:"POST", url:location.href, data:{"' . md5(BASE_URL) . '":"' . $page->theme . '"}, cache:false, success:function(data){ $.each(data,function(key,value){ if(key=="css")$("<style/>").html(value).appendTo("head"); else if(key=="javascript")eval(value); else $("<span/>").html(value).prependTo(key) })}, dataType:"json"});');
        }
        $content = $this->blog->smarty($layout, array('content'=>$content));
      }
    }
    return $page->customize('layout', $content);
  }
  
  private function analytics () {
    global $page;
    $image = BASE_URL . md5(BASE_URL) . '/' . $this->uri->uri_string();
    $page->link('<script>(function(){var e=(new Date).getTimezoneOffset();var t=(new Date((new Date).getFullYear(),0,1)).getTimezoneOffset();var n=(new Date((new Date).getFullYear(),6,1)).getTimezoneOffset();var r=Math.abs(t-n);var i=e<Math.max(t,n)?1:0;var s=e/-60;if(i)s-=r/60;var o="";if(r)o=t>n?"N":"S";var u={dst:i,offset:e*60,timezone:s,hemisphere:o,referrer:document.referrer,height:window.innerHeight,width:window.innerWidth};var a=new XMLHttpRequest;a.open("GET","' . $image . '?"+Object.keys(u).reduce(function(e,t){e.push(t+"="+encodeURIComponent(u[t]));return e},[]).join("&"),true);a.setRequestHeader("X-Requested-With","XMLHttpRequest");a.send()})()</script><noscript><img height="1" width="1" style="border-style:none;" alt="" src="' . $image . '"></noscript>');
  }
  
  private function compile ($array, $i=0) {
    static $data = null;
    if (is_null($data)) $data = array('css'=>array(), 'javascript'=>array());
    foreach ($array as $key => $value) {
      if (empty($value)) continue;
      if ($key === 'css') $data['css'][] = (is_array($value)) ? implode("\n\t", $value) : $value;
      elseif ($key === 'javascript') $data['javascript'][] = (is_array($value)) ? implode("\n\t", $value) : $value;
      elseif (!is_numeric($key)) $data[$key][] = (is_array($value)) ? implode("\n\t", $value) : $value;
      elseif (is_array($value)) $this->compile($value, $i + 1);
    }
    if ($i > 0) return;
    foreach ($data as $key => $value) {
      if (empty($value)) unset($data[$key]);
      else $data[$key] = "\n\t" . implode("\n\t", $data[$key]);
    }
    return $data;
  }
  
}

/* End of file Controller.php */
/* Location: ./application/controllers/Controller.php */