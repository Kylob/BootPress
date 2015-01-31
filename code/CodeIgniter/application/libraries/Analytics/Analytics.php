<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Analytics extends CI_Driver_Library {

  public $db;
  
  public function __construct () {
    global $page;
    $this->db = $page->plugin('Database', 'sqlite', BASE_URI . 'blog/databases/analytics.db');
    if ($this->db->created) $this->create_tables();
  }
  
  public function process_hits () {
    $current = BASE_URI . 'blog/databases/analytics.csv';
    $file = BASE_URI . 'blog/databases/analytics-temp.csv';
    if (is_file($file)) {
      if ((time() - filemtime($file)) < 600) return; // we are already on it
      touch($file); // Houston, we had a problem
      $write = fopen($file, 'ab');
      $read = fopen($current, 'rb');
      while (!feof($read)) fwrite($write, fgets($read));
      fclose($read);
      fclose($write);
      unset($read, $write);
      rename($file, $current);
    }
    rename($current, $file);
    $insert = array(); // session::hits => array - to insert into hits (with load times if currently available)
    $times = array(); // session::hits => delivered
    $sessions = array(); // to update sessions
    $users = array(); // user_id => array($session_id => '') - for inserting into users_sessions
    $fp = fopen($file, 'rb');
    $this->db->ci->trans_start();
    while ($row = fgetcsv($fp)) {
      switch (array_shift($row)) {
        case 'sessions':
          list($session, $time, $agent, $platform, $browser, $version, $mobile, $robot, $uri, $query, $type, $referrer, $ip) = $row;
          $agent_id = $this->id('agents', $agent, array(
            'agent' => $agent,
            'platform' => $platform,
            'browser' => $browser,
            'version' => $version,
            'mobile' => $mobile,
            'robot' => $robot
          ));
          $uri_id = $this->id('uris', $uri, array('uri'=>$uri, 'type'=>$type));
          $session_id = $this->id('sessions', $session, array(
            'time' => $time,
            'agent_id' => $agent_id,
            'uri_id' => $uri_id,
            'query' => $query,
            'referrer' => $referrer,
            'ip' => $ip
          ));
          break;
        case 'hits':
          list($hits, $session, $time, $uri, $query, $type) = $row;
          $key = $session . '::' . $hits;
          $session_id = $this->id('sessions', $session, array('time' => (int) $time));
          $uri_id = $this->id('uris', $uri, array('uri'=>$uri, 'type'=>$type));
          $insert[$key] = array(
            'session_id' => $session_id,
            'time' => (int) $time,
            'uri_id' => $uri_id,
            'query' => $query,
            'referrer' => '',
            'loaded' => 0
          );
          $times[$key] = $time;
          if (!isset($sessions[$session_id])) $sessions[$session_id] = array();
          $sessions[$session_id]['hits'] = $hits;
          $sessions[$session_id]['duration'] = (int) $time;
          break;
        case 'users':
          list($hits, $session, $time, $referrer, $width, $height, $hemisphere, $timezone, $dst, $offset, $user_id, $admin) = $row;
          $key = $session . '::' . $hits;
          if (isset($insert[$key]) && empty($insert[$key]['loaded'])) {
            $insert[$key]['referrer'] = $referrer;
            $insert[$key]['loaded'] = round($time - $times[$key], 3);
          }
          if (isset($insert[$key]) && empty($insert[$key]['loaded'])) $insert[$key]['loaded'] = round($time - $times[$key], 3);
          $session_id = $this->id('sessions', $session, array('time' => (int) $time));
          if (isset($sessions[$session_id])) {
            $sessions[$session_id]['bot'] = 0;
            if (!empty($admin)) $sessions[$session_id]['admin'] = $admin;
            $sessions[$session_id]['width'] = $width;
            $sessions[$session_id]['height'] = $height;
            $sessions[$session_id]['hemisphere'] = $hemisphere;
            $sessions[$session_id]['timezone'] = $timezone;
            $sessions[$session_id]['dst'] = $dst;
            $sessions[$session_id]['offset'] = $offset;
          }
          if (!empty($user_id) && !empty($session_id)) $users[$user_id][$session_id] = '';
          break;
      }
    }
    if (!empty($insert)) $this->db->insert('hits', array_values($insert));
    if (!empty($sessions)) {
      foreach ($sessions as $session_id => $session) {
        $fields = str_replace('duration = ?', 'duration = ? - time', implode(' = ?, ', array_keys($session)) . ' = ?');
        $this->db->query('UPDATE sessions SET ' . $fields . ' WHERE id = ' . $session_id, array_values($session));
      }
    }
    if (!empty($users)) {
      foreach ($users as $user_id => $session) {
        foreach ($session as $session_id => $blank) {
          $this->db->query('INSERT OR IGNORE INTO users_sessions (user_id, session_id) VALUES (?, ?)', array($user_id, $session_id));
        }
      }
    }
    if ($id = $this->db->value('SELECT id FROM sessions WHERE time < ? ORDER BY id DESC LIMIT 1', array(time() - 1036800))) { // ie. a week old
      $this->db->query('DELETE FROM session_ids WHERE id <= ?', array($id));
    }
    $this->db->ci->trans_complete();
    fclose($fp);
    unlink($file);
    $html = '';
    $html .= '<pre>insert: ' . print_r($insert, true) . '</pre>';
    $html .= '<pre>times: ' . print_r($times, true) . '</pre>';
    $html .= '<pre>sessions: ' . print_r($sessions, true) . '</pre>';
    $html .= '<pre>users: ' . print_r($users, true) . '</pre>';
    return $html;
  }
  
  public function last_updated () {
    return $this->db->value('SELECT time FROM hits ORDER BY time DESC LIMIT 1');
  }
  
  public function page_views ($uri=null, $start=null, $stop=null) {
    global $ci;
    if ($id = $this->db->value('SELECT id FROM uris WHERE uri = ?', array(empty($uri) ? $ci->uri->uri_string() : $uri))) {
      if ($views = $this->db->value(array(
        'SELECT COUNT(h.id)',
        'FROM hits AS h INNER JOIN sessions AS s ON h.session_id = s.id',
        str_replace('time', 'h.time', $this->where($start, $stop, 'h.uri_id = ' . $id, 's.bot = 0', 's.admin != 1'))
      ))) {
        return $views;
      }
    }
    return 0;
  }
  
  public function user_hits ($start=null, $stop=null, $default=0) {
    $row = $this->db->row(array(
      'SELECT COUNT(DISTINCT h.session_id) AS user, COUNT(h.id) AS hits',
      'FROM hits AS h INNER JOIN sessions AS s ON h.session_id = s.id',
      str_replace('time', 'h.time', $this->where($start, $stop, 's.bot = 0', 's.admin != 1'))
    ));
    if (empty($row)) return array($default, $default);
    $row['user'] = (empty($row['user'])) ? $default : number_format($row['user']);
    $row['hits'] = (empty($row['hits'])) ? $default : number_format($row['hits']);
    return array_values($row);
  }
  
  public function robot_hits ($start=null, $stop=null, $default=0) {
    $row = $this->db->row(array(
      'SELECT COUNT(DISTINCT s.agent_id) AS bots, COUNT(h.id) AS hits',
      'FROM hits AS h INNER JOIN sessions AS s ON h.session_id = s.id',
      str_replace('time', 'h.time', $this->where($start, $stop, 's.bot = 1'))
    ));
    if (empty($row)) return array($default, $default);
    $row['bots'] = (empty($row['bots'])) ? $default : number_format($row['bots']);
    $row['hits'] = (empty($row['hits'])) ? $default : number_format($row['hits']);
    return array_values($row);
  }
  
  public function avg_load_times ($start=null, $stop=null, $default=0, $append='') {
    $avg = $this->db->value(array(
      'SELECT AVG(loaded) AS avg',
      'FROM hits AS h INNER JOIN sessions AS s ON h.session_id = s.id',
      str_replace('time', 'h.time', $this->where($start, $stop, 'h.loaded > 0', 's.admin != 1'))
    ));
    if (empty($avg)) return $default;
    $avg = explode('.', round($avg, 2));
    return trim(array_shift($avg) . '.' . str_pad(array_shift($avg), 2, 0) . ' ' . $append);
  }
  
  public function avg_session_duration ($start=null, $stop=null, $default=0, $append='') {
    $avg = $this->db->value('SELECT ROUND(AVG(duration)) / 60 AS avg FROM sessions ' . $this->where($start, $stop, 'bot = 0', 'admin != 1', 'duration > 0'));
    if (empty($avg)) return $default;
    $avg = explode('.', round($avg, 2));
    return trim(array_shift($avg) . '.' . str_pad(array_shift($avg), 2, 0) . ' ' . $append);
  }
  
  private function where ($start, $stop) {
    $params = array_slice(func_get_args(), 2);
    $where = array();
    if (!empty($start)) $where[] = 'time >= ' . (int) $start;
    if (!empty($stop)) $where[] = 'time <= ' . (int) $stop;
    if (empty($where)) $where[] = 'time > 0';
    foreach ($params as $value) $where[] = $value;
    return (!empty($where)) ? 'WHERE ' . implode(' AND ', $where) : '';
  }
  
  private function id ($table, $value, $insert=array()) {
    global $page;
    static $ids = array();
    if (isset($ids[$table][$value])) return $ids[$table][$value];
    if ($table == 'agents') {
      if (!$id = $this->db->value('SELECT id FROM agents WHERE agent = ?', array($value))) {
        $id = $this->db->insert('agents', $insert);
      }
    } elseif ($table == 'uris') {
      $row = $this->db->row('SELECT id, type FROM uris WHERE uri = ?', array($value));
      $id = (!empty($row)) ? $row['id'] : $this->db->insert('uris', $insert);
      if (isset($row['type']) && $row['type'] != $insert['type']) {
        $this->db->update('uris', 'id', array($id => array('type' => $insert['type'])));
      }
    } elseif ($table == 'sessions') {
      if (!$id = $this->db->value('SELECT id FROM session_ids WHERE session = ?', array($value))) {
        $id = $this->db->insert('sessions', $insert);
        $this->db->query('INSERT INTO session_ids (id, session) VALUES (?, ?)', array($id, $value));
      }
    }
    if (isset($id)) $ids[$table][$value] = $id;
    return (isset($ids[$table][$value])) ? $ids[$table][$value] : false;
  }
  
  private function create_tables () {
  
    $this->db->create('agents', array(
      'id' => 'INTEGER PRIMARY KEY',
      'agent' => 'TEXT UNIQUE NOT NULL DEFAULT ""',
      'platform' => 'TEXT NOT NULL DEFAULT ""',
      'browser' => 'TEXT NOT NULL DEFAULT ""',
      'version' => 'TEXT NOT NULL DEFAULT ""',
      'mobile' => 'TEXT NOT NULL DEFAULT ""',
      'robot' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('uris', array(
      'id' => 'INTEGER PRIMARY KEY',
      'uri' => 'TEXT UNIQUE NOT NULL DEFAULT ""',
      'type' => 'TEXT NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('session_ids', array(
      'id' => 'INTEGER PRIMARY KEY',
      'session' => 'TEXT UNIQUE NOT NULL DEFAULT ""'
    ));
    
    $this->db->create('users_sessions', array(
      'user_id' => 'INTEGER NOT NULL DEFAULT 0',
      'session_id' => 'INTEGER NOT NULL DEFAULT 0'
    ), array('unique'=>'user_id, session_id'));
    
    $this->db->create('sessions', array(
      'id' => 'INTEGER PRIMARY KEY',
      'bot' => 'INTEGER NOT NULL DEFAULT 1',
      'admin' => 'INTEGER NOT NULL DEFAULT 0',
      'hits' => 'INTEGER NOT NULL DEFAULT 0',
      'duration' => 'INTEGER NOT NULL DEFAULT 0',
      'time' => 'INTEGER NOT NULL DEFAULT 0',
      'agent_id' => 'INTEGER NOT NULL DEFAULT 0',
      'uri_id' => 'INTEGER NOT NULL DEFAULT 0',
      'query' => 'TEXT NOT NULL DEFAULT ""',
      'referrer' => 'TEXT NOT NULL DEFAULT ""',
      'width' => 'INTEGER NOT NULL DEFAULT 0',
      'height' => 'INTEGER NOT NULL DEFAULT 0',
      'hemisphere' => 'TEXT NOT NULL DEFAULT ""',
      'timezone' => 'TEXT NOT NULL DEFAULT ""',
      'dst' => 'INTEGER NOT NULL DEFAULT 0',
      'offset' => 'INTEGER NOT NULL DEFAULT 0',
      'ip' => 'TEXT NOT NULL DEFAULT ""'
    ), 'time, bot, admin, agent_id');
    
    $this->db->create('hits', array(
      'id' => 'INTEGER PRIMARY KEY',
      'session_id' => 'INTEGER NOT NULL DEFAULT 0',
      'loaded' => 'REAL NOT NULL DEFAULT 0',
      'time' => 'INTEGER NOT NULL DEFAULT 0',
      'uri_id' => 'INTEGER NOT NULL DEFAULT 0',
      'query' => 'TEXT NOT NULL DEFAULT ""',
      'referrer' => 'TEXT NOT NULL DEFAULT ""'
    ), 'time, uri_id');
    
  }
  
}

/* End of file Analytics.php */
/* Location: ./application/libraries/Analytics/Analytics.php */