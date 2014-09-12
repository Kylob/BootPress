<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Controller extends CI_Controller {

  public $resource = array();
  private $errors = '';
  private $model;
  private $post;
  
  public function _remap ($model) {
    global $admin, $bp, $ci, $page;
    $this->benchmark->mark('page_cache_start');
    do { $this->errors .= ob_get_clean(); } while (ob_get_level());
    $this->model = $model;
    ob_start();
    $admin = array_merge(array('name'=>'', 'email'=>'', 'password'=>'', 'folder'=>''), (array) $admin);
    define('ADMIN', !empty($admin['folder']) ? $admin['folder'] : 'admin');
    if (!defined('BLOG')) define('BLOG', '');
    define('BASE_URL', $this->config->base_url());
    $ci = $this; // $ci =& get_instance(); doesn't cut it so ...
    if ($this->model == '#cache#') {
      $this->load_database();
      $file = func_get_arg(1);
      $type = array_pop($file);
      $this->load->driver('resources');
      return $this->resources->deliverer->view(implode('/', $file), $type);
    }
    $this->load->driver('sitemap');
    if ($html = $this->sitemap->cached()) {
      $this->benchmark->mark('page_cache_end');
      $this->benchmark->mark('page_setup_start');
      $this->load->driver('session');
      $this->benchmark->mark('page_setup_end');
      $this->benchmark->mark('page_display_start');
    } else {
      $this->benchmark->mark('page_cache_end');
      $this->benchmark->mark('page_setup_start');
      require_once(BASE . 'Page.php');
      if ($this->model == '#sitemap#') {
        $page = new Page;
        $this->load->driver('session');
        $this->benchmark->mark('page_setup_end');
        $this->benchmark->mark('page_content_start');
        $this->load_database();
        $params = func_get_arg(1);
        $method = array_shift($params);
        $html = $this->sitemap->$method(array_shift($params));
      } else {
        $page = new Page('folder');
        $page->charset = $this->config->item('charset');
        $page->load(BASE, 'BootPress/BootPress.php');
        $bp = new BootPress;
        $this->load->driver('session');
        $this->benchmark->mark('page_setup_end');
        $this->benchmark->mark('page_content_start');
        $this->load_database();
        $this->post();
        $html = '';
        if ($this->model == ADMIN) {
          $view = $page->uri('next', ADMIN);
          $this->load->driver('blog', array('page'=>'#admin#'));
          $this->load->driver('admin', array('file'=>$view));
          switch ($view) {
            case 'users':
            case 'errors':
            case 'setup':
            case 'php':
            case 'pages':
            case 'layouts':
            case 'resources':
            case 'databases':
            case 'analytics':
              $html = $this->admin->$view->view();
              break;
            default: show_404($page->url()); break;
          }
        } elseif ($file = $page->get('file')) { 
          $this->model = $page->get('folder');
          $this->load->driver('blog', array('page'=>$this->model));
          $html = $page->outreach($file);
          $template = substr($file, 0, -4) . '.tpl';
          if (file_exists($template)) $html = $this->blog->smarty(array('php'=>$html), file_get_contents($template));
        } else {
          $this->load->driver('blog', array('page'=>'#blog#'));
          $params = ($this->blog->get('uri') != '') ? explode('/', $this->blog->get('uri')) : array();
          $this->model = (!empty($params)) ? $params[0] : 'index';
          switch ($this->model) {
            case 'index': if ($this->input->get('search')) $this->model = 'search'; break;
            case 'atom.xml':
            case 'rss.xml': $this->model = substr($this->model, 0, -4); break;
            case 'archives':
            case 'authors':
            case 'tags': break;
            default:
              if ($category = $this->blog->db->row('SELECT id, url, category, tags FROM categories WHERE url = ?', array($this->model))) {
                $this->model = 'category';
                $params = $category;
              } elseif ($post = $this->blog->db->row('SELECT id, url FROM blog WHERE url = ?', array($page->get('uri')))) {
                $this->model = 'post';
                $params = $post;
              } else {
                show_404($page->url());
              }
              break;
          }
          $method = $this->model;
          $html = $this->blog->pages->$method($params);
          $this->blog->feeds();
        }
      } // end if ($this->model == '#sitemap#')
      $this->benchmark->mark('page_content_end');
      $this->benchmark->mark('page_display_start');
      if ($page->get('type') == 'html') {
        $html = $this->layout($html, $this->model);
        $page->plugin('CDN', 'link', 'json2/0.1/json2.min.js');
        $page->link(BASE_URL . 'CodeIgniter/application/libraries/templates/afterthought.js');
        $page->plugin('jQuery', 'code', "$('body').afterthought('{$this->post}', '{$this->blog->template}');");
        do { $this->errors .= ob_get_clean(); } while (ob_get_level());
        $html = $this->filter_links($page->display($html . $this->errors));
      }
      if ($this->output->get_content_type() == 'text/html') $this->output->set_content_type($page->get('type'));
      if (empty($this->errors)) $this->sitemap->update($html);
    }
    $this->log('hits');
    if (ENVIRONMENT == 'development' && $this->output->get_content_type() == 'text/html' && $this->model != ADMIN) {
      $this->session->select_driver('native');
      $this->output->enable_profiler(true);
    } elseif (empty($this->errors)) {
      $this->sitemap->may_change();
    }
    do { $this->errors .= ob_get_clean(); } while (ob_get_level()); // for compression's sake
    $this->load->view('view', array('html'=>$html));
    $this->benchmark->mark('page_display_end');
  }
  
  public function log ($type, $resource=false) { // made public so that we can log resources
    $file = fopen(BASE_URI . 'blog/databases/analytics.csv', 'a');
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
          $analytics['offset'] = $this->input->post('offset');
          $user_id = is_user();
          $admin = is_admin();
          fputcsv($file, array(
            'table' => 'users',
            'hits' => $analytics['hits'],
            'session_id' => $analytics['id'],
            'time' => microtime(true),
            'referrer' => $this->input->post('referrer'),
            'width' => $this->input->post('width'),
            'height' => $this->input->post('height'),
            'hemisphere' => $this->input->post('hemisphere'),
            'timezone' => $this->input->post('timezone'),
            'dst' => $this->input->post('dst'),
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
    }
  }
  
  public function filter_links ($html) { // made public so that it can be called elsewhere when ending the script prematurely if desired
    $url = str_replace(array('.', '/'), array('\.', '\/'), BASE_URL);
    $chars = $this->config->item('permitted_uri_chars');
    preg_match_all('/(' . $url . ')([' . $chars . '\/]+)(#[' . $chars . '\/]+)?/i', $html, $matches);
    $cache = array_flip(array('jpeg', 'jpg', 'gif', 'png', 'ico', 'js', 'css', 'pdf', 'ttf', 'otf', 'svg', 'eot', 'woff', 'swf', 'tar', 'tgz', 'gz', 'zip', 'csv', 'xlsx', 'xls', 'xl', 'word', 'docx', 'doc', 'ppt', 'mp3', 'mpeg', 'mpe', 'mpg', 'mov', 'qt', 'psd'));
    $resources = array(); // we'll run these through the cache machine
    $types = array(); // $resources['uri'] = '.ext';
    $links = array(); // improperly suffixed uri's
    asort($matches[2]); // sort array while maintaining index association
    foreach(array_unique($matches[2]) as $key => $uri) {
      $dot = strrpos($uri, '.');
      $ext = ($dot) ? substr($uri, $dot + 1) : '';
      if (isset($cache[$ext])) { // we want to cache this resource
        $resources[] = BASE_URL . $uri;
        $types[$uri] = '.' . $ext;
        if (substr($uri, 0, 15) == 'blog/resources/') {
          $id = substr($uri, 15, $dot - 15);
          if (is_numeric($id)) $this->resource[$id] = BASE_URL . $uri;
        }
      } elseif ($ext == 'txt' || $ext == 'xml') {
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
      if (!empty($this->resource)) {
        $this->blog->db->query('SELECT r.id, r.type, r.name, p.name AS parent
                                FROM resources AS r
                                LEFT JOIN resources AS p ON r.parent = p.id
                                WHERE r.id IN(' . implode("\n, ", array_keys($this->resource)) . ')');
        while (list($id, $type, $name, $parent) = $this->blog->db->fetch('row')) {
          if (empty($name)) $name = $parent;
          $cached[$this->resource[$id]] = str_replace("{$id}.{$type}", "{$name}.{$type}", $cached[$this->resource[$id]]);
        }
      }
      foreach ($cached as $key => $value) if ($key != $value) $rnr[$key] = $value;
      $rename = array();
      foreach ($matches[3] as $key => $frag) {
        $uri = $matches[2][$key];
        if (!empty($frag) && isset($rnr[BASE_URL . $uri]) && !isset($rename[BASE_URL . $uri . $frag])) {
          $replace = '$1$2/' . substr($frag, 1) . $types[$uri];
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
    if (!empty($rnr)) $html = str_replace(array_keys($rnr), array_values($rnr), $html);
    return $html;
  }
  
  private function blog_img ($html) { //  for php scripts that don't go directly through the smarty filter
    return preg_replace('/(\{\$blog\[.*img.*]})/', BASE_URL . 'blog/resources/', $html);
  }
  
  private function layout ($content, $view) {
    global $page;
    if (($preview = $this->session->native->tempdata('preview_layout')) && is_admin(2)) $this->template = $preview;
    $bootstrap = 'blog/templates/' . $this->blog->template;
    if ($this->blog->get('page') != '#admin#' && file_exists(BASE_URI . $bootstrap . '.css')) {
      $page->link(BASE_URL . $bootstrap . '.css#bootstrap', 'prepend');
      if (file_exists(BASE_URI . $bootstrap . '.js')) {
        $page->link(BASE_URL . $bootstrap . '.js#bootstrap', 'prepend');
        $page->plugin('jQuery');
      }
    } else {
      $page->plugin('CDN', 'links', array(
        'bootstrap/3.1.1/css/bootstrap.min.css',
        'bootstrap/3.1.1/js/bootstrap.min.js'
      ));
      $page->plugin('jQuery');
      if ($this->blog->get('page') == '#admin#') return $content;
    }
    if (!file_exists(BASE_URI . 'blog/templates/' . $this->blog->template . '.cache')) $this->sitemap->disable_caching();
    $content = $page->customize('content', $content);
    $layout = '';
    if (file_exists(BASE_URI . 'blog/templates/layout/' . $this->blog->template . '.php')) {
      $layout = $page->outreach(BASE_URI . 'blog/templates/layout/' . $this->blog->template . '.php', array('content'=>$content));
    }
    if (empty($layout) || is_array($layout)) {
      $vars = array('blog'=>$this->blog->get(), 'php'=>$layout);
      $export = array('content'=>$content);
      $export['header'] = $page->customize('header', $this->blog->smarty($vars, $this->blog->templates('header', $this->blog->template)));
      $export['sidebar'] = $page->customize('sidebar', $this->blog->smarty($vars, $this->blog->templates('sidebar', $this->blog->template)));
      $export['footer'] = $page->customize('footer', $this->blog->smarty($vars, $this->blog->templates('footer', $this->blog->template)));
      $layout = $this->blog->smarty(array_merge($vars, $export), $this->blog->templates('layout', $this->blog->template));
    }
    return $this->blog_img($page->customize('layout', $layout));
  }
  
  private function post () {
    global $page;
    $this->post = 'post{' . md5($page->get('uri')) . '}';
    if (isset($_POST[$this->post])) {
      $this->load->driver('blog', array('page'=>'#post#'));
      $this->log('users');
      foreach (array_keys($this->session->native->userdata()) as $key) {
        if ($pos = strpos($key, ':old:')) $this->session->native->keep_flashdata(substr($key, $pos + 5));
      }
      $data = array();
      $file = BASE_URI . 'blog/templates/post/' . $page->seo($_POST[$this->post]) . '.php';
      if ($this->model != ADMIN && file_exists($file)) {
        $export = $page->outreach($file);
        if (is_array($export)) $data = $this->compile($export);
      }
      $data = json_encode($data);
      $data = $this->filter_links($this->blog_img($data));
      header("Content-type: application/json");
      exit($data);
    }
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
  
  private function load_database () {
    $database = BASE_URI . 'CodeIgniter.db';
    $config['database'] = $database;
    $config['dbdriver'] = 'sqlite3';
    $config['dbprefix'] = '';
    $config['pconnect'] = FALSE;
    $config['db_debug'] = TRUE;
    $config['cache_on'] = FALSE;
    $config['cachedir'] = '';
    $config['char_set'] = 'utf8';
    $config['dbcollat'] = 'utf8_general_ci';
    if (file_exists($database)) {
      $this->load->database($config);
    } else { // we are creating if for the first time
      if (!is_dir(dirname($database))) mkdir(dirname($database), 0755, true);
      $this->load->database($config);
      $this->db->query('CREATE TABLE "ci_sessions" (
        session_id TEXT NOT NULL DEFAULT "",
        ip_address TEXT NOT NULL DEFAULT "",
        user_agent TEXT NOT NULL DEFAULT "",
        user_data TEXT NOT NULL DEFAULT "",
        user_id INTEGER NOT NULL DEFAULT 0,
        last_activity INTEGER NOT NULL DEFAULT 0,
        UNIQUE (session_id, user_agent) ON CONFLICT REPLACE
      )');
      $this->db->query('CREATE TABLE "ci_databases" (
        id INTEGER PRIMARY KEY,
        driver TEXT NOT NULL DEFAULT "",
        database TEXT NOT NULL DEFAULT "",
        config TEXT NOT NULL DEFAULT ""
      )');
      $this->db->query('CREATE TABLE "ci_full_paths" (
        id INTEGER PRIMARY KEY,
        path TEXT UNIQUE COLLATE NOCASE,
        tiny_id INTEGER NOT NULL DEFAULT 0,
        updated INTEGER NOT NULL DEFAULT 0
      )');
      $this->db->query('CREATE TABLE "ci_tiny_paths" (
        id INTEGER PRIMARY KEY,
        path TEXT UNIQUE NOT NULL DEFAULT "",
        full_id INTEGER NOT NULL DEFAULT 0
      )');
      $this->db->query('CREATE INDEX ci_sessions_idx ON ci_sessions (last_activity, user_id)');
      $this->db->query('CREATE UNIQUE INDEX ci_databases_idx ON ci_databases (driver, database)');
      $this->db->query('CREATE INDEX ci_tiny_paths_idx ON ci_tiny_paths (full_id)');
    }
  }
  
}

/* End of file Controller.php */
/* Location: ./application/controllers/Controller.php */