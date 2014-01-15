<?php

function notify_me_of_errors ($link, $action='', $href='') {
  global $page;
  $comment = '<!-- notify_me_of_errors -->';
  if (empty($action) && $link != $page->get('url') . $page->get('uri')) {
    $page->filter('content', 'notify_me_of_errors', array('this', 'insert'));
    $page->filter('page', 'notify_me_of_errors', array('this', 'link', $link));
  } elseif ($action == 'insert') {
    return $comment . $link;
  } elseif ($action == 'link') {
    $db = Error::Handler();
    $count = $db->count();
    $errors = ($count > 0) ? '<a class="btn btn-danger" href="' . $href . '">View Errors (' . $count . ')</a><hr />' : '';
    return str_replace($comment, $errors, $link);
  }
}

class Error {

  private $db;
  private $domain = 0;
  
  private function __construct () {
    global $page;
    error_reporting(-1); // everything
    set_error_handler(array($this, 'my_error_handler'));
    $page->plugin('SQLite');
    $this->db = new SQLite('error_handler');
    if ($this->db->created) $this->create_tables();
    $this->domain = $this->db->value('SELECT id FROM domains WHERE domain = ?', array($page->get('domain')));
    if (empty($this->domain)) $this->domain = $this->db->insert('domains', array('domain'=>$page->get('domain')));
  }
  
  public static function Handler () {
    static $instance = null;
    if ($instance == null)  $instance = new Error;
    return $instance;
  }
  
  public function db () {
    return $this->db;
  }
  
  public function count () {
    $this->db->query("SELECT num FROM errors GROUP BY file, line, msg");
    return count($this->db->fetch('row', 'all'));
  }
  
  public function my_error_handler ($num, $msg, $file, $line, $vars) {
    if (!($num & error_reporting())) return true; // This error code is not included in the error_reporting level
    $debug = array();
    $backtraces = debug_backtrace(false);
    array_shift($backtraces); // get rid of my_error_handler reference
    foreach ($backtraces as $key => $info) {
      $debug[$key]['error_id'] = 0; // we'll update this later
      $debug[$key]['file'] = $info['file'];
      $debug[$key]['line'] = $info['line'];
      $debug[$key]['class'] = (isset($info['class'])) ? $info['class'] : '';
      $debug[$key]['function'] = $info['function'];
    }
    return $this->log($num, $msg, $file, $line, $vars, $debug);
  }
  
  private function log ($num, $msg, $file, $line, $vars, $debug) {
    $errors = array('domain_id'=>$this->domain, 'num'=>$num, 'file'=>$file, 'line'=>$line, 'msg'=>$msg);
    $id = $this->db->insert('errors', $errors);
    foreach ($debug as $key => $info) $debug[$key]['error_id'] = $id;
    $this->db->insert('backtraces', $debug);
    return true; // Don't execute PHP internal error handler
  }
  
  private function create_tables () {
    $table = 'domains';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['domain'] = 'TEXT NOT NULL UNIQUE COLLATE NOCASE';
    $this->db->create($table, $columns);
    $table = 'errors';  
    $columns = array();  
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['domain_id'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['num'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['file'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['line'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['msg'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['date'] = 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
    $this->db->create($table, $columns);
    $table = 'backtraces';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['error_id'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['file'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['line'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['class'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['function'] = 'TEXT NOT NULL DEFAULT ""';
    $this->db->create($table, $columns);
  }
  
}

Error::Handler(); // get the ball rolling ...

?>