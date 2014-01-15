<?php

class Session {

  function open ($save_path, $session_name) {
    return true;
  }

  function close () {
    return true;
  }

  function read ($session_id) {
    $result = db_query('SELECT data FROM sessions WHERE id="' . db_escape($session_id) . '"');
    if ($result->num_rows == 1) {
      list($data) = $result->fetch_row();
      return $data;
    } else {
      return '';
    }
  }

  function write ($session_id, $session_data) {
    global $mysqli;
    db_query('REPLACE INTO sessions (id, data, last_accessed) VALUES ("' . db_escape($session_id) . '", "' . db_escape($session_data) . '", NOW())');
    return $mysqli->affected_rows;
  }

  function destroy ($session_id) {
    return db_query('DELETE FROM sessions WHERE id="' . db_escape($session_id) . '"');
  }

  function clean ($maxlifetime) {
    return db_query("DELETE FROM sessions WHERE last_accessed < SUBDATE(NOW(), INTERVAL 1 HOUR)");
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