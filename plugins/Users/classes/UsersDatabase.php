<?php

include_once (BASE . 'params.php');

class UsersDatabase {

  protected $db;
  
  public function __construct () {
    global $page;
    if (!defined('ADMIN_NAME') || !defined('ADMIN_EMAIL') || !defined('ADMIN_PASSWORD') || constant('ADMIN_NAME') == '' || constant('ADMIN_EMAIL') == '' || constant('ADMIN_PASSWORD') == '') {
      die('Params are not set in the root bootpress folder');
    }
    $this->db = new SQLite (BASE_URI . 'users');
    if ($this->db->created) $this->create_tables();
    $this->update_admin();
  }
  
  public function info ($user_id) {
    $info = array();
    $users = (!is_bool($user_id) && !empty($user_id)) ? (array) $user_id : array();
    $where = ' approval="Y" AND confirmed="Y"';
    if (!empty($users)) $where = ' id IN(' . implode(', ', $users) . ') AND' . $where;
    $this->db->query('SELECT id, name, email FROM users WHERE' . $where);
    while (list($id, $name, $email) = $this->db->fetch('row')) $info[$id] = array('name'=>$name, 'email'=>$email);
    return (count($users) == 1) ? array_shift($info) : $info;
  }
  
  public function persistent_login () {
    global $page;
    if (isset($_COOKIE['user']) && !isset($_SESSION['user_id'])) {
      if (strlen($_COOKIE['user']) > 32) {
        $user_id = substr($_COOKIE['user'], 0, -32);
        $code = substr($_COOKIE['user'], -32);
        if ($this->db->value('SELECT id FROM users WHERE id = ? AND code = ?', array($user_id, $code))) {
          return $this->login($user_id);
        }
      }
      $this->user_cookie();
    }
  }
  
  protected function register ($name, $email) {
    $code = md5(sha1(date('DdMmY') . $email . ADMIN_PASSWORD . ADMIN_EMAIL . ADMIN_NAME));
    $new_user = ($user_id = $this->db->value('SELECT id FROM users WHERE email = ?', array($email))) ? false : true;
    if ($new_user) $user_id = $this->db->insert('users', array('name'=>$name, 'email'=>$email, 'code'=>$code));
    return array($new_user, $user_id);
  }
  
  protected function reset_password ($email) {
    $code = md5(sha1(date('DdMmY') . $email . ADMIN_PASSWORD . ADMIN_EMAIL . ADMIN_NAME));
    return $this->db->update('users', array('code'=>$code, 'password'=>''), 'email', $email); // 1 (true), or false
  }
  
  protected function activate ($string) {
    if (strlen($string) < 33) return false;
    $user_id = (int) substr($string, 0, -32);
    $code = substr($string, -32);
    if ($this->db->statement("UPDATE users SET confirmed = 'Y' WHERE id = ? AND code = ? AND approval = 'Y' AND password = ''", array($user_id, $code), 'update')) {
      return $user_id;
    }
    return false;
  }
  
  protected function login ($user_id, $cookie=false) {
    global $page;
    $this->db->query('SELECT code, name, password, admin FROM users WHERE id = ? AND approval = ? LIMIT 1', array($user_id, 'Y'));
    if (list($code, $name, $password, $admin) = $this->db->fetch('row')) {
      $_SESSION['user_id'] = $user_id;
      $_SESSION['name'] = $name;
      if ($admin > 0) $_SESSION['admin'] = $admin;
      if ($cookie) $this->user_cookie($user_id, $code);
    }
  }
  
  protected function logout ($eject='') {
    global $page;
    $_SESSION = array(); // Destroy the variables.
    session_destroy(); // Destroy the session itself.
    setcookie (session_id(), '', time()-300); // Destroy the cookie.
    $this->user_cookie();
    $page->eject ($eject);
  }
  
  private function user_cookie ($user_id='', $code='') {
    global $page;
    $value = $user_id . $code;
    $expires = (!empty($user_id)) ? time() + (60 * 60 * 24 * 30) : 1;
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
      setcookie('user', $value, $expires, '/', false, false, true);
    } else {
      $domain = '.' . implode('.', array_slice(explode('.', $page->get('domain')), -2));
      setcookie('user', $value, $expires, '/', $domain, false, true);
    }
  }
  
  private function update_admin () {
    $user = $this->db->row('SELECT * FROM users WHERE email = ?', array(ADMIN_EMAIL));
    $code = (isset($user['code']) && !empty($user['code'])) ? $user['code'] : md5(sha1(date('DdMmY') . ADMIN_EMAIL . ADMIN_PASSWORD . ADMIN_NAME));
    $password = sha1(sha1(ADMIN_PASSWORD . $code, true));
    if (empty($user)) { // insert
      $insert = array();
      $insert['code'] = $code;
      $insert['name'] = ADMIN_NAME;
      $insert['email'] = ADMIN_EMAIL;
      $insert['password'] = $password;
      $insert['confirmed'] = 'Y';
      $insert['admin'] = 1;
      $this->db->insert('users', $insert);
    } else {
      $update = array();
      $udpate['code'] = $code;
      $update['name'] = ADMIN_NAME;
      $update['password'] = $password;
      $update['approval'] = 'Y';
      $update['confirmed'] = 'Y';
      $update['admin'] = 1;
      $this->db->update('users', $update, 'email', ADMIN_EMAIL);
    }
  }
  
  private function create_tables () {
    $table = 'users';
    $columns = array();
    $columns['id'] = 'INTEGER PRIMARY KEY';
    $columns['code'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['name'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['email'] = 'TEXT UNIQUE COLLATE NOCASE';
    $columns['password'] = 'TEXT NOT NULL DEFAULT ""';
    $columns['approval'] = 'TEXT NOT NULL DEFAULT "Y"';
    $columns['confirmed'] = 'TEXT NOT NULL DEFAULT "N"';
    $columns['admin'] = 'INTEGER NOT NULL DEFAULT 0';
    $columns['registered'] = 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
    $this->db->create($table, $columns);
  }
  
}

?>