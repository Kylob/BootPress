<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Driver_Library {

  public $db;
  private $admin;
  
  public function __construct () {
    global $ci, $page;
    $this->db = $ci->load_database();
    if (!isset($ci->blog) || !$this->admin = $ci->blog->admin) return;
    foreach (array('name', 'email', 'password') as $value) if (empty($this->admin[$value])) exit('Admin params are not set in the root bootpress folder');
    $admin = array('name'=>$this->admin['name'], 'email'=>$this->admin['email'], 'password'=>sha1($this->admin['password']), 'admin'=>1, 'approved'=>'Y');
    $user = $this->db->row('SELECT * FROM ci_users WHERE email = ?', array($this->admin['email']));
    if (empty($user)) { // insert
      $this->db->insert('ci_users', $admin);
    } else {
      unset($admin['email']);
      foreach ($admin as $field => $value) {
        if ($user[$field] != $value) {
          $this->db->update('ci_users', 'email', array($this->admin['email'] => $admin));
          break;
        }
      }
    }
  }
  
  public function info ($user_id) {
    global $ci;
    $single = (is_array($user_id)) ? false : true;
    $users = array();
    foreach ((array) $user_id as $id) $users[$id] = array();
    $groups = $this->get_users_groups(array_keys($users));
    $this->db->query('SELECT id, name, email, admin, approved, registered FROM ci_users WHERE id IN(' . implode(', ', array_keys($users)) . ')');
    while ($row = $this->db->fetch('assoc')) {
      $users[$row['id']] = $row;
      $users[$row['id']]['registered'] = strtotime($row['registered']);
      $users[$row['id']]['last_activity'] = 0;
      $users[$row['id']]['groups'] = $groups[$row['id']];
    }
    $query = $ci->db->query('SELECT user_id, last_activity FROM ci_sessions WHERE last_activity > 0 AND user_id IN(' . implode(', ', array_keys($users)) . ') GROUP BY user_id ORDER BY last_activity DESC');
    if ($query->num_rows() > 0) {
      foreach ($query->result_array() as $row) {
        $users[$row['user_id']]['last_activity'] = $row['last_activity'];
      }
    }
    return ($single) ? array_shift($users) : $users;
  }
  
  public function count ($duration=600) { // 10 minutes (in seconds)
    global $ci;
    $query = $ci->db->query('SELECT COUNT(*) FROM ci_sessions WHERE last_activity >= ? AND user_id > ? GROUP BY user_id', array(time() - $duration, 0));
    return array_shift($query->row_array());
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
  
  public function check ($email, $password='') {
    $check = func_get_args();
    $email = array_shift($check);
    if (empty($check)) return $this->db->value('SELECT id FROM ci_users WHERE email = ?', array($email));
    $password = array_shift($check);
    if (strlen($password) == 0) return false;
    if (empty($check)) return $this->db->value('SELECT id FROM ci_users WHERE email = ? AND password = ?', array($email, sha1($password)));
    $add = array_shift($check);
    return $this->db->value('SELECT id FROM ci_users WHERE email = ? AND password = ? AND ' . $add, array($email, sha1($password)));
  }
  
  public function register ($name, $email, $password) {
    $new_user = ($user_id = $this->check($email)) ? false : true;
    if ($new_user) $user_id = $this->db->insert('ci_users', array('name'=>$name, 'email'=>$email, 'password'=>sha1($password)));
    return array($new_user, $user_id);
  }
  
  public function update ($user_id, $user=array()) {
    $update = array();
    foreach ($user as $key => $value) {
      switch ($key) {
        case 'name': $update[$key] = $value; break;
        case 'email': $update[$key] = $value; break;
        case 'password': if (!empty($value)) $update[$key] = sha1($value); break;
        case 'admin': $update[$key] = (is_numeric($value) && $value > 0) ? (int) $value : 0; break;
        case 'approved': $update[$key] = (empty($value) || strtoupper($value) == 'N') ? 'N' : 'Y'; break;
        default: $update[$key] = $value; break;
      }
    }
    if (!empty($update)) {
      $this->db->update('ci_users', 'id', array($user_id => $update));
      if (is_user($user_id) && isset($update['name'])) $ci->session->cookie->set_userdata('name', $update['name']);
      if (isset($update['approved']) && $update['approved'] == 'N') $this->logout($user_id);
    }
  }
  
  public function login ($user_id, $expires=false, $single=false) {
    global $ci, $page;
    $this->logout(($single !== false) ? $user_id : false);
    if ($user = $this->db->row('SELECT id AS user_id, name, admin FROM ci_users WHERE id = ? AND approved = ?', array($user_id, 'Y'))) {
      if (in_array($user['admin'], array(1,2)) && !$this->admin) $page->eject(ADMIN . '/users');
      if ($expires) {
        $seconds = $ci->config->item('sess_expiration');
        if (empty($seconds)) $seconds = 60 * 60 * 24 * 365 * 2; // ie. 2 years
        $days = floor($seconds / 86400);
        $user['relapse'] = (int) ($expires <= $days) ? $expires * 24 * 60 * 60 : min($expires, $seconds);
      }
      $ci->session->cookie->set_userdata($user);
    }
  }
  
  public function logout ($user_id=false) {
    global $ci;
    if ($user_id) { // then log them out of all of their sessions
      $ci->db->query('UPDATE ci_sessions SET user_data = ? WHERE last_activity > ? AND user_id = ?', array('', 0, $user_id));
      if ($user_id == is_user()) $this->logout(); // only for the sake of the current page
    } else { // only the current users session
      $user = $ci->session->cookie->userdata();
      unset($user['session_id'], $user['ip_address'], $user['user_agent'], $user['last_activity']);
      foreach ($user as $key => $value) $ci->session->cookie->unset_userdata($key);
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
    if (!empty($insert)) $this->db->insert('ci_user_groups', $insert);
  }
  
  public function remove_from_group ($user_id, $group) {
    $users = (array) $user_id;
    $groups = (array) $this->group_id($group);
    $this->db->ci->simple_query('DELETE FROM ci_user_groups WHERE user_id IN (' . implode(', ', $users) . ') AND group_id IN (' . implode(', ', $groups) . ')');
  }
  
  public function get_users_groups ($user_id) {
    $single = (is_array($user_id)) ? false : true;
    $users = array();
    foreach ((array) $user_id as $id) $users[$id] = array();
    $this->db->query('SELECT u.user_id, g.name FROM ci_user_groups AS u INNER JOIN ci_groups AS g ON u.group_id = g.id WHERE u.user_id IN(' . implode(', ', array_keys($users)) . ') ORDER BY g.name ASC');
    while (list($user_id, $group) = $this->db->fetch('row')) $users[$user_id][] = $group;
    return ($single) ? array_shift($users) : $users;
  }
  
  public function get_groups_users ($group) {
    $single = (is_array($group)) ? false : true;
    $groups = array();
    foreach ((array) $group as $id) $groups[$id] = array();
    $this->db->query('SELECT u.user_id, u.group_id, g.name FROM ci_user_groups AS u INNER JOIN ci_groups AS g ON u.group_id = g.id WHERE u.user_id > 0 AND u.group_id IN (' . implode(', ', $this->group_id(array_keys($groups))) . ') ORDER BY u.user_id, u.group_id ASC');
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
    $admin = $this->db->value('SELECT admin FROM ci_users WHERE id = ?', array($user_id));
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
        $group_id = $this->db->value('SELECT id FROM ci_groups WHERE name = ?', array($group));
        if (empty($group_id)) $group_id = $this->db->insert('ci_groups', array('name'=>$group));
      }
      $groups[$group] = $group_id;
    }
    return ($single) ? array_shift($groups) : $groups;
  }
  
}

/* End of file Auth.php */
/* Location: ./application/libraries/Auth/Auth.php */