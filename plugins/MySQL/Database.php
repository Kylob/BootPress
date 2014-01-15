<?php

class MySQL {

  public $db;
  public $info=array();
  public $executed = array();
  public $count = 0;
  
  public static function Database () {
    static $instance = null;
    if ($instance == null)  $instance = new MySQL;
    return $instance;
  }
  
  private function __construct () {
    global $page;
    $get = $page->get('params');
    $page->plugin('SQLite');
    $db = new SQLite($get['plugin-name']);
    if ($db->created) {
      $columns = array();
      $columns['domain'] = 'TEXT UNIQUE';
      $columns['server'] = 'TEXT NOT NULL DEFAULT ""';
      $columns['username'] = 'TEXT NOT NULL DEFAULT ""';
      $columns['password'] = 'TEXT NOT NULL DEFAULT ""';
      $columns['database'] = 'TEXT NOT NULL DEFAULT ""';
      $db->create('databases', $columns);
    }
    if (isset($get['db'])) {
      list($server, $username, $password, $database) = $get['db'];
      $db->statement("INSERT OR REPLACE INTO databases (domain, server, username, password, database) VALUES (?, ?, ?, ?, ?)", array($page->get('domain'), $server, $username, $password, $database), 'insert');
    }
    $db->query('SELECT server, username, password, database FROM databases WHERE domain = ? LIMIT 1', array($page->get('domain')));
    $this->info = $db->fetch('assoc');
    $this->db = new mysqli($this->info['server'], $this->info['username'], $this->info['password'], $this->info['database']);
    if (mysqli_connect_errno()) {
      trigger_error ('DB Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
      exit ('There was a problem connecting to the database');
    }
  }
  
  public function exec ($query, $values=array()) {
    return $this->statement($query, $values);
  }
  
  public function query ($query, $values=array()) {
    return $this->statement($query, $values, 'select');
  }
  
  public function insert ($table, $array, $add='') {
    $multiple = (isset($array[0])) ? true : false;
    $columns = ($multiple) ? array_keys($array[0]) : array_keys($array);
    $params = array();
    foreach ($columns as $value) $params[] = '?';
    if ($multiple) {
      $values = array();
      foreach ($array as $data) $values[] = array_values($data);
    } else {
      $values = array_values($array);
    }
    return $this->statement('INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $params) . ') ' . $add, $values, 'insert');
  }
  
  public function update ($table, $array, $column, $id, $add='') {
    $multiple = (isset($array[0]) && is_array($id)) ? true : false;
    $columns = ($multiple) ? array_keys($array[0]) : array_keys($array);
    foreach ($columns as $key => $value) $columns[$key] = '`' . $value . '` = ?';
    if ($multiple) {
      $values = array();
      foreach ($array as $set) {
        list(, $where) = each($id);
        $values[] = array_merge(array_values($set), array($where));
      }
    } else {
      $values = array_merge(array_values($array), array($id));
    }
    return $this->statement('UPDATE `' . $table . '` SET ' . implode(', ', $columns) . ' WHERE `' . $column . '` = ? ' . $add, $values, 'update');
  }
  
  public function delete ($table, $column, $id, $add='') {
    return $this->statement('DELETE FROM `' . $table . '` WHERE `' . $column . '` = ? ' . $add, (array) $id, 'delete');
  }
  
  public function statement ($query, $values, $type='') { // select, insert, update, delete
    $start = microtime(true);
    $stmt = $this->db->prepare($query);
    if ($stmt === false) {
      trigger_error("MySQL failed to prepare statement: {$query}<br /><br />Error: {$this->db->error}");
      return false;
    }
    if (!empty($values) && is_array($values[0])) {
      $return = 0;
      foreach ($values as $data) {
        call_user_func_array(array($stmt, 'bind_param'), $this->params($data));
        if ($stmt->execute()) $return += $stmt->affected_rows;
      }
    } else {
      $return = false;
      if (!empty($values)) call_user_func_array(array($stmt, 'bind_param'), $this->params($values));
      if ($stmt->execute()) {
        switch ($type) {
          case 'select': $return = $this->results($stmt); break;
          case 'insert': $return = $this->db->insert_id; break;
          case 'update':
          case 'delete': $return = $stmt->affected_rows; break;
          default: $return = true; break;
        }
      } else {
        trigger_error("MySQL Statement: {$query}<br /><br />Error: {$stmt->error}");
      }
    }
    $stmt->close();
    $this->executed[$query] = round(microtime(true) - $start, 3);
    return $return;
  }
  
  private function params ($values) {
    $types = '';
    foreach ($values as $key => $value) {
      if (is_int($value)) {
        $types .= 'i'; // integer
      } elseif (is_float($value)) {
        $types .= 'd'; // double
      } elseif (is_string($value)) {
        $types .= 's'; // string
      } else {
        $types .= 'b'; // blob and unknown
      }
    }
    $ref = array($types); // Reference is required for PHP 5.3+ 
    foreach ($values as $key => $value) $ref[] = &$values[$key];
    return $ref;
  }
  
  private function results ($stmt) {
    $sqlite = new SQLite;
    $columns = array();
    $params = array();
    $meta = $stmt->result_metadata();
    while ($field = $meta->fetch_field()) {
      $params[] = &$row[$field->name];
      $columns[] = preg_replace('/[^a-z0-9_-]/i', '', $field->name);
    }
    $sqlite->db->exec('CREATE TABLE "results" (' . implode(", ", $columns) . ')');
    $statement = $sqlite->db->prepare('INSERT INTO results (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
    $sqlite->db->beginTransaction();
    call_user_func_array(array($stmt, 'bind_result'), $params);
    $count = 0;
    while ($stmt->fetch()) {
      $fetch = array();
      foreach ($row as $key => $value) $fetch[] = $value;
      $statement->execute($fetch);
      $count++;
    }
    $this->count = $count;
    $sqlite->db->commit();
    $sqlite->query('SELECT * FROM results');
    return $sqlite;
  }
  
  public function __destruct () {
    $this->db->close();
  }
  
}

function db_exec ($query, $values=array()) {
  $db = MySQL::Database();
  return $db->exec($query, $values); // true or false
}
  
function db_query ($query, $values=array()) {
  $db = MySQL::Database();
  return $db->query($query, $values); // SQLite:memory: results table
}

function db_value ($query, $values=array()) {
  $db = MySQL::Database();
  $result = $db->query($query, $values);
  $values = $result->fetch('row', 'all');
  unset($result);
  return (isset($values[0][0])) ? $values[0][0] : false;
}

function db_row ($query, $values=array()) {
  $db = MySQL::Database();
  $result = $db->query($query, $values);
  $values = $result->fetch('assoc', 'all');
  unset($result);
  return (isset($values[0])) ? $values[0] : false;
}

function db_insert ($table, $array, $add='') {
  $db = MySQL::Database();
  return $db->insert($table, $array, $add); // id if single, affected_rows if multiple
}

function db_update ($table, $array, $column, $id, $add='') {
  $db = MySQL::Database();
  return $db->update($table, $array, $column, $id, $add); // number of affected rows
}

function db_delete ($table, $column, $id, $add='') {
  $db = MySQL::Database();
  return $db->delete($table, $column, $id, $add); // number of affected rows
}

function db_statement ($query, $values, $type='') {
  $db = MySQL::Database();
  return $db->statement($query, $values, $type); // depends on $type
}

function db_executed ($all=false) {
  $db = MySQL::Database();
  if ($all !== false) return $db->executed;
  $times = array_values($db->executed);
  $last = end($times); // 666
  return $last; // in seconds
}

function db_count () {
  $db = MySQL::Database();
  return $db->count; // db_query rows
}

function db_object () {
  $db = MySQL::Database();
  return $db->db; // the mysqli object itself
}

db_exec ("SET time_zone = '" . date('P') . "'");

?>