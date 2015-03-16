<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// http://stackoverflow.com/questions/549/the-definitive-guide-to-form-based-website-authentication#477579
// http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice/
// http://jaspan.com/improved_persistent_login_cookie_best_practice
// http://security.stackexchange.com/questions/63435/why-use-an-authentication-token-instead-of-the-username-password-per-request/63438#63438

class Auth {
  
  public $db;
  private $session = array();
  private $user = array();
  
  public function __construct () {
    global $ci, $page;
    $this->db = $ci->load_database();
    if ($session = $ci->input->cookie('bootpress')) {
      $session = explode(':', base64_decode($session));
      $this->session['id'] = array_shift($session);
      $this->session['user_agent'] = trim(substr($ci->input->user_agent(), 0, 120));
      $this->session['series'] = array_shift($session);
      $this->session['token'] = array_shift($session);
      $this->session['time'] = time();
      if ($user = $this->db->row(array(
        'SELECT u.id, u.name, u.email, u.admin, s.adjourn, s.relapse, s.last_activity, s.login',
        'FROM user_sessions AS s',
        'INNER JOIN users AS u ON s.user_id = u.id',
        'WHERE s.id = ? AND s.user_agent = ? AND s.series = ? AND s.token = ? AND s.adjourn >= ? AND u.approved = ?'
      ), array($this->session['id'], $this->session['user_agent'], sha1($this->session['series']), sha1($this->session['token']), $this->session['time'], 'Y'))) {
        if (($user['last_activity'] + 300) <= $this->session['time'] && !$ci->input->is_ajax_request()) { // we update every 5 minutes
          $this->session['token'] = $this->salt();
          $this->db->update('user_sessions', 'id', array($this->session['id'] => array(
            'adjourn' => $this->session['time'] + $user['relapse'],
            'last_activity' => $this->session['time'],
            'ip_address' => $ci->input->ip_address(),
            'token' => sha1($this->session['token'])
          )));
          $this->db->update('users', 'id', array($user['id'] => array('last_activity' => $this->session['time'])));
          $this->set_cookie(base64_encode($this->session['id'] . ':' . $this->session['series'] . ':' . $this->session['token']), $user['relapse']);
        }
        $this->user = array_slice($user, 0, 4, true);
        $this->user['login'] = $this->session['time'] - strtotime($user['login']);
      } else {
        $this->logout($this->db->value('SELECT user_id FROM user_sessions WHERE id = ?', array($this->session['id'])));
      }
    } elseif ($bootpress = $ci->session->bootpress) { // A stolen (then deleted) cookie
      $session = explode(':', base64_decode($bootpress));
      $this->logout($this->db->value('SELECT user_id FROM user_sessions WHERE id = ?', array(array_shift($session))));
    } else {
      $verify = (isset($ci->blog)) ? (array) $ci->blog->admin : array();
      foreach (array('name', 'email', 'password') as $value) if (!isset($verify[$value]) || empty($verify[$value])) exit('Admin params are not set in the root bootpress folder');
      $admin = array('name'=>$verify['name'], 'email'=>$verify['email'], 'password'=>$verify['password'], 'admin'=>1, 'approved'=>'Y');
      $user = $this->db->row('SELECT * FROM users WHERE email = ?', array($admin['email']));
      if (!in_array(substr($admin['password'], 0, 4), array('$2a$', '$2y$'))) {
        $admin['password'] = ($user && $this->bcrypt($verify['password'], $user['password'])) ? $user['password'] : $this->bcrypt($admin['password']);
        log_message('error', "For security reasons, please change your admin password ({$verify['password']}) to it's encrypted counterpart:\n\n{$admin['password']}");
      }
      if (!$user) return $this->db->insert('users', $admin);
      foreach ($admin as $field => $value) if ($user[$field] != $value) return $this->db->update('users', 'id', array($user['id'] => $admin));
    }
  }
  
  public function user ($param=null) {
    if (is_null($param)) return $this->user;
    return (isset($this->user[$param])) ? $this->user[$param] : null;
  }
  
  public function info ($user_id) {
    global $ci;
    $single = (is_array($user_id)) ? false : true;
    $users = array();
    foreach ((array) $user_id as $id) $users[$id] = array();
    $groups = $this->get_users_groups(array_keys($users));
    $this->db->query('SELECT id, name, email, admin, approved, registered, last_activity FROM users WHERE id IN(' . implode(', ', array_keys($users)) . ')');
    while ($row = $this->db->fetch('assoc')) {
      $users[$row['id']] = $row;
      $users[$row['id']]['registered'] = strtotime($row['registered']);
      $users[$row['id']]['groups'] = $groups[$row['id']];
    }
    return ($single) ? array_shift($users) : $users;
  }
  
  public function count ($duration=600) { // 10 minutes (in seconds)
    global $ci;
    return $this->db->value('SELECT COUNT(*) FROM user_sessions WHERE last_activity >= ?', array(time() - $duration));
  }
  
  public function is_email ($address) {
    return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
  }
  
  public function random_password ($length=8) {
    $password = '';
    for ($i=0; $i<$length; $i++) {
      switch (rand(0,2)) {
        case 0: $password .= rand(0,9); break; // 0-9
        case 1: $password .= chr(rand(65,90)); break; // A-Z
        case 2: $password .= chr(rand(97,122)); break; // a-z
      }
    }
    return $password;
  }
  
  public function check ($email, $password=null) {
    $check = func_get_args();
    $email = array_shift($check);
    if (empty($check)) return $this->db->value('SELECT id FROM users WHERE email = ?', array($email));
    $password = array_shift($check);
    $user = $this->db->row('SELECT id, password FROM users WHERE email = ?' . (!empty($check) ? ' AND ' . array_shift($check) : null), array($email));
    if ($this->bcrypt($password, $user['password'])) return $user['id'];
    return false;
  }
  
  public function register ($name, $email, $password) {
    $new_user = ($user_id = $this->check($email)) ? false : true;
    if ($new_user) $user_id = $this->db->insert('users', array('name'=>$name, 'email'=>$email, 'password'=>$this->bcrypt($password)));
    return array($new_user, $user_id);
  }
  
  public function update ($user_id, $user=array()) {
    global $ci;
    $update = array();
    foreach ($user as $key => $value) {
      switch ($key) {
        case 'name': $update[$key] = $value; break;
        case 'email': $update[$key] = $value; break;
        case 'password': if (!empty($value)) $update[$key] = $this->bcrypt($value); break;
        case 'admin': $update[$key] = (is_numeric($value) && $value > 0) ? (int) $value : 0; break;
        case 'approved': $update[$key] = (empty($value) || strtoupper($value) == 'N') ? 'N' : 'Y'; break;
        default: $update[$key] = $value; break;
      }
    }
    if (!empty($update)) {
      $this->db->update('users', 'id', array($user_id => $update));
      if (is_user($user_id)) foreach (array('name', 'email', 'admin') as $value) if (isset($update[$value])) $this->user[$value] = $update[$value];
      if (isset($update['approved']) && $update['approved'] == 'N') $this->logout($user_id);
    }
  }
  
  public function login ($user_id, $expires=7200, $single=false) {
    global $ci, $page;
    $this->logout(($single !== false) ? $user_id : null);
    if ($user = $this->db->row('SELECT id, name, email, admin FROM users WHERE id = ? AND approved = ?', array($user_id, 'Y'))) {
      if (ADMIN != '' && in_array($user['admin'], array(1,2)) && $single === false) $page->eject($page->url('admin', 'users'));
      $this->session = array(
        'id' => '',
        'user_agent' => trim(substr($ci->input->user_agent(), 0, 120)),
        'series' => $this->salt(),
        'token' => $this->salt(),
        'time' => time()
      );
      $relapse = ($expires <= 730) ? $expires * 24 * 60 * 60 : $expires;
      $this->session['id'] = $this->db->insert('user_sessions', array(
        'user_id' => $user['id'],
        'adjourn' => $this->session['time'] + $relapse,
        'relapse' => $relapse,
        'last_activity' => $this->session['time'],
        'ip_address' => $ci->input->ip_address(),
        'user_agent' => $this->session['user_agent'],
        'series' => sha1($this->session['series']),
        'token' => sha1($this->session['token'])
      ));
      $this->db->update('users', 'id', array($user['id'] => array('last_activity' => $this->session['time'])));
      $this->set_cookie(base64_encode($this->session['id'] . ':' . $this->session['series'] . ':' . $this->session['token']), $relapse);
      $this->user = $user;
    }
  }
  
  public function logout ($user_id=null) {
    global $ci;
    if ($user_id) {
      $this->db->delete('user_sessions', 'user_id', $user_id, 'OR adjourn <= ' . time());
    } elseif (isset($this->session['id'])) {
      $this->set_cookie();
      $this->db->delete('user_sessions', 'id', $this->session['id']);
      $this->session = array();
      $this->user = array();
    }
  }
  
  public function add_to_group ($user_id, $group) {
    $users = (array) $user_id;
    $groups = (array) $this->group_id($group);
    $this->remove_from_group($users, $groups);
    $insert = array();
    foreach ($users as $user_id) {
      foreach ($groups as $group_id) {
        if ($group_id > 0) $insert[] = array('user_id'=>$user_id, 'group_id'=>$group_id);
      }
    }
    if (!empty($insert)) $this->db->insert('user_groups', $insert);
  }
  
  public function remove_from_group ($user_id, $group) {
    $users = (array) $user_id;
    $groups = (array) $this->group_id($group);
    $this->db->ci->simple_query('DELETE FROM user_groups WHERE user_id IN (' . implode(', ', $users) . ') AND group_id IN (' . implode(', ', $groups) . ')');
  }
  
  public function get_users_groups ($user_id) {
    $single = (is_array($user_id)) ? false : true;
    $users = array();
    foreach ((array) $user_id as $id) $users[$id] = array();
    $this->db->query('SELECT u.user_id, g.name FROM user_groups AS u INNER JOIN user_group_names AS g ON u.group_id = g.id WHERE u.user_id IN(' . implode(', ', array_keys($users)) . ') ORDER BY g.name ASC');
    while (list($user_id, $group) = $this->db->fetch('row')) $users[$user_id][] = $group;
    return ($single) ? array_shift($users) : $users;
  }
  
  public function get_groups_users ($group) {
    $single = (is_array($group)) ? false : true;
    $groups = array();
    foreach ((array) $group as $id) $groups[$id] = array();
    $this->db->query('SELECT u.user_id, u.group_id, g.name FROM user_groups AS u INNER JOIN user_group_names AS g ON u.group_id = g.id WHERE u.user_id > 0 AND u.group_id IN (' . implode(', ', $this->group_id(array_keys($groups))) . ') ORDER BY u.user_id, u.group_id ASC');
    while (list($user_id, $group_id, $group) = $this->db->fetch('row')) {
      $key = (isset($groups[$group_id])) ? $group_id : $group;
      $groups[$key][] = $user_id;
    }
    return ($single) ? array_shift($groups) : $groups;
  }
  
  public function user_in_group ($user_id, $group, $check='all') { // or 'any'
    $groups = implode(',', $this->get_users_groups($user_id));
    if (empty($groups)) return false;
    $check = (in_array(strtolower($check), array('all', 'and', '&&'))) ? 'all' : 'any';
    foreach ((array) $group as $in) {
      if (stripos(",{$groups},", ",{$in},") !== false) {
        if ($check == 'any') return true; // there is no sense in checking any more beyond this
      } elseif ($check == 'all') {
        return false; // they are not in this group, so that is all we need to know
      }
    }
    return ($check == 'all') ? true : false;
  }
  
  public function user_is_admin ($user_id, $level=1) {
    $admin = $this->db->value('SELECT admin FROM users WHERE id = ?', array($user_id));
    return (!empty($admin) && $admin <= $level) ? $admin : false;
  }
    
  private function group_id ($name) {
    $single = (is_array($name)) ? false : true;
    $groups = array();
    foreach ((array) $name as $group) {
      if (empty($group)) {
        $group_id = 0;
      } elseif (is_numeric($group)) {
        $group_id = $group;
      } else {
        $group_id = $this->db->value('SELECT id FROM user_group_names WHERE name = ?', array($group));
        if (empty($group_id)) $group_id = $this->db->insert('user_group_names', array('name'=>$group));
      }
      $groups[$group] = $group_id;
    }
    return ($single) ? array_shift($groups) : $groups;
  }
  
  private function salt () {
    $raw_salt_len = 16;
    $buffer = '';
    $buffer_valid = false;
    if (function_exists('mcrypt_create_iv') && !defined('PHALANGER') && ($buffer = mcrypt_create_iv($raw_salt_len))) $buffer_valid = true;
    if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes') && ($buffer = openssl_random_pseudo_bytes($raw_salt_len))) $buffer_valid = true;
    if (!$buffer_valid && @is_readable('/dev/urandom')) {
      $f = fopen('/dev/urandom', 'r');
      $read = strlen($buffer);
      while ($read < $raw_salt_len) {
        $buffer .= fread($f, $raw_salt_len - $read);
        $read = strlen($buffer);
      }
      fclose($f);
      if ($read >= $raw_salt_len) $buffer_valid = true;
    }
    if (!$buffer_valid || strlen($buffer) < $raw_salt_len) {
      $bl = strlen($buffer);
      for ($i = 0; $i < $raw_salt_len; $i++) {
        if ($i < $bl) {
          $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
        } else {
          $buffer .= chr(mt_rand(0, 255));
        }
      }
    }
    $salt = strtr(rtrim(base64_encode($buffer), '='), 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/', './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
    return substr($salt, 0, 22);
  }
  
  private function bcrypt ($password, $compare=null) {
    global $ci;
    $ci->load->library('bcrypt', array('salt_prefix' => (version_compare(PHP_VERSION, '5.3.7', '<')) ? '$2a$' : '$2y$', 'rounds' => rand(5,9)));
    return ($compare) ? $ci->bcrypt->verify($password, $compare) : $ci->bcrypt->hash($password);
  }
  
  private function set_cookie ($value='', $expires=false) {
    global $ci;
    if (empty($value)) {
      unset($_SESSION['bootpress']);
    } else {
      $ci->session->bootpress = $value;
    }
    $ci->input->set_cookie('bootpress', $value, $expires, $ci->config->item('cookie_domain'), $ci->config->item('cookie_path'), '', $ci->config->item('cookie_secure'), true);
  }
  
}

/* End of file Auth.php */
/* Location: ./application/libraries/Auth/Auth.php */