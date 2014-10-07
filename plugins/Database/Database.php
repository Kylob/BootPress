<?php

function ci_load_database ($driver, $database, $query_builder=false) {
  global $ci;
  $config = '';
  $insert = false;
  if (is_string($database)) {
    $query = $ci->db->query("SELECT config FROM ci_databases\nWHERE driver = ? AND database = ?", array($driver, $database));
    if ($query->num_rows() == 1) {
      list($config) = array_values($query->row_array());
    } elseif ($driver == 'sqlite3') { // The database (uri) is all we really need anyways, and it just hasn't been inserted yet
      $database = array('database'=>$database);
    }
  }
  if (is_array($database)) {
    if (isset($database[0]) && count($database) == 4) $database = array_combine(array('hostname', 'username', 'password', 'database'), $database);
    $config = array('hostname'=>'', 'username'=>'', 'password'=>'', 'database'=>'', 'dbdriver'=>'', 'dbprefix'=>'', 'pconnect'=>false, 'db_debug'=>true, 'cache_on'=>false, 'cachedir'=>'', 'char_set'=>'utf8', 'dbcollat'=>'utf8_general_ci');
    foreach ($config as $key => $value) if (isset($database[$key])) $config[$key] = $database[$key];
    $config['dbdriver'] = $driver;
    if (!empty($config['database'])) {
      $params = array($config['dbdriver'], $config['database']);
      $config = serialize($config);
      $database = '';
      $query = $ci->db->query("SELECT config FROM ci_databases\nWHERE driver = ? AND database = ?", $params);
      if ($query->num_rows() == 1) $database = array_shift($query->row_array());
      if ($config != $database) {
        $params[] = $config;
        $insert = $params;
      }
    } else {
      $config = '';
    }
  }
  if (!empty($config)) {
    $config = unserialize($config);
    foreach (array('pconnect', 'db_debug', 'cache_on') as $key) $config[$key] = (bool) $config[$key];
    $name = 'db' . md5($config['dbdriver'] . ':' . $config['database']);
    if ($ci->$name = $ci->load->database($config, true, (bool) $query_builder)) {
      if ($insert) $ci->db->query('INSERT OR REPLACE INTO ci_databases (driver, database, config) VALUES (?, ?, ?)', $insert);
      return $ci->$name;
    }
  }
  return false;
}

class Database {
  
  public $ci;
  public $query = null; // the last database result object
  
  public function __construct ($db_connection) {
    $this->ci = $db_connection;
  }
  
  public function __destruct () {
    $this->ci->close();
  }
  
  public function query ($query, $values=array()) {
    global $ci;
    if (is_array($query)) $query = implode("\n", $query);
    $query = $this->ci->query(trim($query), $values);
    if (is_bool($query)) return $query; // a "write" type query
    if (!is_null($this->query)) $this->query->free_result();
    $this->query = $query;
    return $this->query; // a "read" type query
  }
  
  public function fetch ($return='row', $all=false) {
    if ($all) {
      $results = array();
      while ($row = $this->fetch($return)) $results[] = $row;
      return $results;
    }
    if (!$row = $this->query->unbuffered_row('array')) return false;
    return ($return == 'row') ? array_values($row) : $row;
  }
  
  public function row ($query, $values=array()) {
    if (is_array($query)) $query = implode("\n", $query);
    $query = $this->query($query, $values);
    if ($query->num_rows() > 0) $row = $query->row_array();
    $query->free_result();
    return (!empty($row)) ? $row : false;
  }
  
  public function value ($query, $values=array()) {
    return ($row = $this->row($query, $values)) ? array_shift($row) : false;
  }
  
  /* This method would be more useful if it didn't only work for MySQL, but it's still nice to look at so ...
  public function insert ($table, $insert, $and='', $or='') {
    $affected_rows = 0;
    $multiple = (isset($insert[0])) ? true : false;
    if (!$multiple) $insert = array($insert);
    $query = 'INSERT ' . $or . ' INTO ' . $table . ' (' . implode(', ', array_keys($insert[0])) . ") VALUES\n";
    foreach ($insert as $key => $values) $insert[$key] = '(' . implode(', ', array_map(array($this->ci, 'escape'), $values)) . ')';
    for ($i = 0, $total = count($insert); $i < $total; $i = $i + 100) {
      if ($this->query(array($query, implode(",\n", array_slice($insert, $i, 100)), $and))) $affected_rows += $this->ci->affected_rows();
    }
    return ($multiple) ? $affected_rows : $this->ci->insert_id();
  }
  */
  
  public function insert ($table, $insert, $and='', $or='') {
    $ids = array();
    $multiple = (isset($insert[0])) ? true : false;
    if (!$multiple) $insert = array($insert);
    $query = (!empty($or)) ? 'INSERT ' . $or . ' INTO ' . $table : 'INSERT INTO ' . $table;
    $query .= ' (' . implode(', ', array_keys($insert[0])) . ") VALUES (" . implode(', ', array_fill(0, count($insert[0]), '?')) . ') ' . $and;
    foreach ($insert as $values) {
      $ids[] = ($this->ci->query($query, $values)) ? $this->ci->insert_id() : 0;
    }
    return ($multiple) ? $ids : array_shift($ids);
  }
  
  public function update ($table, $column, $update, $and='') {
    $affected_rows = 0;
    $fields = array_slice($update, 0, 1);
    $fields = array_shift($fields);
    $query = 'UPDATE ' . $table . ' SET ' . implode(' = ?, ', array_keys($fields)) . ' = ? WHERE ' . $column . ' = ? ' . $and;
    foreach ($update as $id => $values) {
      $values[] = $id;
      if ($this->ci->query($query, $values)) $affected_rows += $this->ci->affected_rows();
    }
    return $affected_rows;
  }
  
  public function delete ($table, $column, $id, $and='') {
    $affected_rows = 0;
    $query = 'DELETE FROM ' . $table . ' WHERE ' . $column . ' = ? ' . $and;
    foreach ((array) $id as $value) {
      if ($this->ci->query($query, array($value))) $affected_rows += $this->ci->affected_rows();
    }
    return $affected_rows;
  }
  
}

?>