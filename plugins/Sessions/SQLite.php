<?php

class Session {

  public $table;
  public $db;

  public function __construct () {
    global $page;
    $get = $page->get('params');
    $this->table = 'sessions';
    $this->db = new SQLite ($get['plugin-name'] . '/' . $page->get('domain'));
    if ($this->db->created) {
      $columns = array();
      $columns['id'] = 'TEXT UNIQUE';
      $columns['data'] = 'TEXT';
      $columns['last_accessed'] = 'TEXT DEFAULT CURRENT_TIMESTAMP';
      $this->db->create($this->table, $columns);
    }
  }

  function open ($save_path, $session_name) {
    return true;
  }

  function close () {
    return true;
  }

  function read ($id) {
    $data = $this->db->value("SELECT data FROM {$this->table} WHERE id= ?", (array) $id);
    return ($data) ? $data : '';
  }

  function write ($id, $data) {
    $result = $this->db->statement("INSERT OR REPLACE INTO {$this->table} (id, data, last_accessed) VALUES (?, ?, datetime('now'))", array($id, $data), 'insert');
    return ($result) ? 1 : 0;
  }

  function destroy ($id) {
    $this->db->delete($this->table, 'id', $id);
    return true;
  }

  function clean ($maxlifetime) {
    $this->db->query("DELETE FROM {$this->table} WHERE last_accessed < datetime('now', '-1 hours')");
    return true;
  }

}

$session = new Session();
session_set_save_handler(
  array(&$session,"open"), 
  array(&$session,"close"), 
  array(&$session,"read"), 
  array(&$session,"write"), 
  array(&$session,"destroy"), 
  array(&$session,"clean"));
register_shutdown_function("session_write_close");

?>