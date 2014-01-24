<?php

define ('SQLITE_URI', str_replace('\\', '/', dirname(__FILE__)) . '/databases/');

class SQLite {

  public $created = false; // whether or not this is a new database
  public $db;
  private $executed = array();
  private $info = false;
  private $result;

  public function __construct ($database='') {
    if (empty($database)) {
      $path = ':memory:';
      $this->created = true;
    } else {
      $path = (strpos($database, BASE) === false) ? SQLITE_URI . $database . '.db3' : $database . '.db3';
      if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
      if (!file_exists($path)) $this->created = true;
    }
    if (!($this->db = new PDO ('sqlite:' . $path))) {
      $info = $this->db->errorInfo();
      trigger_error ("The SQLite Database ({$database}) connection failed: {$info[2]}");
      $this->db = false;
      return $this->db;
    }
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); // SILENT, WARNING, or EXCEPTION
    $this->pragma('foreign_keys', 'ON');
    return true;
  }
  
  public function attach ($database, $alias) {
    if ($this->db === false) return $this->db;
    $path = SQLITE_URI . $database . '.db3';
    return $this->exec('ATTACH DATABASE "' . $path . '" AS ' . $alias);
  }
  
  public function create ($table, $columns, $changes=array()) {
    if ($this->db === false) return $this->db;
    $add = '';
    if (isset($columns['add'])) { // eg. UNIQUE (field_one, field_two) ON CONFLICT REPLACE
      $add = ", \n\t" . $columns['add'];
      unset($columns['add']);
    }
    $fields = array();
    foreach ($columns as $name => $type) $fields[] = $name . ' ' . $type;
    $fields = implode(", \n\t", $fields) . $add;
    $query = 'CREATE TABLE "' . $table . '" (' . $fields . ')';
    $executed = $this->info('tables', $table);
    $quotes = array('"', "'");
    // See http://www.sqlite.org/fileformat2.html - 2.5 Storage Of The SQL Database Schema
    if (str_replace($quotes, '', $query) == str_replace($quotes, '', $executed)) {
      return false; // the table has already been created in it's requested state
    }
    $this->info('tables', $table, $query); // add or update
    if ($executed) { // then this table is being altered in some way
      $this->alter($table, $columns, $changes, $add);
      trigger_error("<pre>Just FYI, an SQLite table was changed:\n\nFrom: {$executed}\n\nTo: {$query}</pre>");
    } else {
      $this->exec($query); // We should only get here once
    }
    return true; // the table has been created (or altered)
  }
  
  public function index ($table, $suffix, $columns) {
    if ($this->db === false) return $this->db;
    if (!is_array($columns)) $columns = array($columns);
    $index = $table . '_index_' . $suffix;
    $query = "CREATE INDEX {$index} ON {$table} (" . implode(', ', $columns) . ")";
    $executed = $this->info('indexes', $index);
    if ($query == $executed) return true; // the index has already been created
    $this->info('indexes', $index, $query); // add
    if ($executed) {
      $this->query("DROP INDEX {$index}");
      trigger_error("<pre>Just FYI, an SQLite index was changed:\n\nFrom: {$executed}\n\nTo: {$query}</pre>");
    }
    return $this->exec($query);
  }
  
  public function pragma ($name, $value='', $all=false) {
    if ($this->db === false) return $this->db;
    if ($value == '') {
      $this->query("PRAGMA {$name}");
      $result = $this->fetch('assoc', 'all');
      if ($all !== false) return $result;
      if (isset($result[0])) return $result[0];
      return false;
    }
    return $this->exec("PRAGMA {$name} = {$value}");
  }
  
  public function settings () {
    if ($this->db === false) return $this->db;
    if ($this->info('settings') === false) { // then we haven't called or created it yet
      if ($this->create('database_settings', array('settings'=>'TEXT NOT NULL DEFAULT ""'))) {
        $this->insert('database_settings', array('settings'=>serialize(array())));
      }
      $this->info['settings'] = unserialize($this->value("SELECT settings FROM database_settings"));
    }
    $num = func_num_args();
    switch ($num) {
      case 0: // they want it all
        return $this->info('settings');
        break;
      case 1: // they want to retrieve a specific setting
        return $this->info('settings', func_get_arg(0));
        break;
      case 2: // they want to establish a setting
        $update = false;
        list($setting, $value) = func_get_args();
        $current = $this->info('settings', $setting);
        if ($value === false) { // then we don't want this in the database as "false" is the default value
          if ($current !== false) {
            unset($this->info['settings'][$setting]);
            $update = true;
          }
        } elseif ($current !== $value) {
          $this->info['settings'][$setting] = $value;
          $update = true;
        }
        if ($update) $this->statement("UPDATE database_settings SET settings = ?", array(serialize($this->info['settings'])));
        break;
    }
  }
  
  public function explain ($query) {
    return $this-query('EXPLAIN QUERY PLAN ' . $query);
  }
  
  public function query ($query, $values=array()) {
    if ($this->db === false) return $this->db;
    if (!empty($values)) return $this->statement($query, $values, 'select');
    $start = microtime(true);
    if (!($this->result = $this->db->query($query))) {
      $info = $this->db->errorInfo();
      trigger_error ("SQLite Query: {$query}<br /><br />Error: {$info[2]}");
      return false;
    }
    $this->executed[$query] = round(microtime(true) - $start, 3);
    return true;
  }
  
  public function exec ($query) {
    if ($this->db === false) return $this->db;
    $start = microtime(true);
    $rows = $this->db->exec($query);
    $this->executed[$query] = round(microtime(true) - $start, 3);
    if ($rows === false) {
      $info = $this->db->errorInfo();
      trigger_error ("SQLite Exec: {$query}<br /><br />Error: {$info[2]}");
    }
    return $rows;
  }
  
  public function statement ($query, $values, $type='') { // select, insert, update, delete
    if ($this->db === false) return $this->db;
    $start = microtime(true);
    $stmt = $this->db->prepare($query);
    if ($stmt === false) {
      $info = $this->db->errorInfo();
      trigger_error("SQLite failed to prepare statement: {$query}<br /><br />Error: {$info[2]}<pre>" . print_r($values, true) . '</pre>');
      return false;
    }
    if (is_array($values[0])) {
      $return = 0;
      $this->db->beginTransaction();
      foreach ($values as $data) {
        $data = array_map('trim', $data);
        if (!($success = $stmt->execute($data))) {
          $this->db->rollBack();
          break;
        } else {
          $return += $stmt->rowCount();
        }
      }
      if ($success) $this->db->commit();
    } else {
      $values = array_map('trim', $values);
      $success = $stmt->execute($values);
      $return = ($type == 'insert') ? $this->db->lastInsertId() : $stmt->rowCount(); // else 'update' || 'delete'
    }
    if (!$success) {
      $info = $stmt->errorInfo();
      trigger_error ("SQLite Statement: {$query}<br /><br />Error: {$info[2]}" . '<pre>' . print_r($info, true) . '</pre>' . $stmt->errorCode());
    }
    if ($type == 'select') { // returns true (or false if !$success), and sets $this->result
      $this->result = $stmt;
      $return = true;
    }
    unset ($stmt);
    $this->executed[$query] = round(microtime(true) - $start, 3);
    return ($success) ? $return : false;
  }
  
  public function mysql_upsert ($table, $select) { // make sure all fields are accounted for in the select, or this will default/erase the original value
    if ($this->db === false) return $this->db;
    $start = microtime(true);
    $db = db_object(); // mysqli
    if ($mysql = $db->prepare($select)) {
      $mysql->execute();
      $meta = $mysql->result_metadata();
      $columns = array();
      $params = array();
      while ($field = $meta->fetch_field()) {
        $columns[] = preg_replace('/[^a-z0-9_-]/i', '', $field->name);
        $params[] = &$row[$field->name];
      }
      $query = 'INSERT OR REPLACE INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
      $statement = $this->db->prepare($query);
      $this->db->beginTransaction();
      call_user_func_array(array($mysql, 'bind_result'), $params);
      while ($mysql->fetch()) {
        $fetch = array();
        foreach ($row as $key => $value) $fetch[] = $value;
        $statement->execute($fetch);
      }
      $this->db->commit();
      $mysql->close();
      $this->executed[$query] = round(microtime(true) - $start, 3);
      return true;
    }
    return false;
  }
  
  public function insert ($table, $array) {
    if ($this->db === false) return $this->db;
    $multiple = (isset($array[0])) ? true : false;
    $columns = ($multiple) ? array_keys($array[0]) : array_keys($array);
    $params = array();
    foreach ($columns as $count) $params[] = '?';
    if ($multiple) {
      $values = array();
      foreach ($array as $data) $values[] = array_values($data);
    } else {
      $values = array_values($array);
    }
    return $this->statement('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $params) . ')', $values, 'insert');
  }
  
  public function update ($table, $array, $column, $id, $add='') {
    if ($this->db === false) return $this->db;
    $multiple = (isset($array[0]) && is_array($id)) ? true : false;
    $columns = ($multiple) ? array_keys($array[0]) : array_keys($array);
    foreach ($columns as $key => $value) {
      $columns[$key] = $value . ' = ?';
    }
    if ($multiple) {
      $values = array();
      foreach ($array as $set) {
        list(, $where) = each($id);
        $values[] = array_merge(array_values($set), array($where));
      }
    } else {
      $values = array_merge(array_values($array), array($id));
    }
    return $this->statement('UPDATE ' . $table . ' SET ' . implode(', ', $columns) . ' WHERE ' . $column . ' = ? ' . $add, $values, 'update');
  }
  
  public function delete ($table, $column, $id, $add='') {
    if ($this->db === false) return $this->db;
    if (!is_array($id)) $id = array($id);
    return $this->statement('DELETE FROM ' . $table . ' WHERE ' . $column . ' = ? ' . $add, $id, 'delete');
  }
  
  public function fetch ($return='num', $all=false) {
    if ($this->db === false) return $this->db;
    if (!is_object($this->result)) return false;
    $style = (!empty($return)) ? 'PDO::FETCH_' . str_replace('ROW', 'NUM', strtoupper($return)) : '';
    return ($all !== false) ? $this->result->fetchAll(constant($style)) : $this->result->fetch(constant($style));
  }
  
  public function value ($query, $values=array()) {
    if ($this->db === false) return $this->db;
    $this->query($query, $values);
    $values = $this->fetch('row', 'all');
    return (isset($values[0][0])) ? $values[0][0] : false;
  }
  
  public function row ($query, $values=array()) {
    if ($this->db === false) return $this->db;
    $this->query($query, $values);
    $values = $this->fetch('assoc', 'all');
    return (isset($values[0])) ? $values[0] : false;
  }
  
  public function executed ($all=false) {
    if ($this->db === false) return $this->db;
    if ($all !== false) return $this->executed;
    $times = array_values($this->executed);
    $last = end($times); // 666
    return $last; // in seconds
  }
  
  public function info () {
    if ($this->db === false) return $this->db;
    if ($this->info === false) { // okay, fine, we'll do something
      $this->info = array();
      $this->query("SELECT type, name, tbl_name, sql FROM sqlite_master");
      while (list($type, $name, $table, $sql) = $this->fetch('row')) {
        switch ($type) {
          case 'table': $this->info['tables'][$table] = $sql; break;
          case 'index': $this->info['indexes'][$name] = $sql; break;
        }
      }
    }
    $num_args = func_num_args();
    switch ($num_args) {
      case 3:
        list($master, $name, $add) = func_get_args();
        $this->info[$master][$name] = $add;
        break;
      case 2:
        list($master, $name) = func_get_args();
        return (isset($this->info[$master][$name])) ? $this->info[$master][$name] : false;
        break;
      case 1:
        list($master) = func_get_args();
        return (isset($this->info[$master])) ? $this->info[$master] : false;
        break;
      default:
        return $this->info; // now they are just getting greedy
        break;
    }
  }
  
  private function alter ($table, $columns, $changes=array(), $add='') { // used in $this->create()
    if ($this->db === false) return $this->db;
    $this->query("SELECT * FROM {$table} LIMIT 1");
    $row = $this->fetch('assoc', 'all'); // 'all' so that we can drop this table later
    if (!empty($row)) $row = array_shift($row);
    $map = array();
    foreach ($changes as $old => $new) {
      if (isset($columns[$new]) && isset($row[$old])) $map[$old] = $new; // legitimate changes
    }
    foreach ($row as $key => $value) {
      if (isset($columns[$key]) && !isset($map[$key])) $map[$key] = $key; // old fields that match the new
    }
    $results = array();
    $this->pragma('foreign_keys', 'OFF');
    $this->db->beginTransaction();
    $copy = "{$table}_copy";
    $fields = array();
    foreach ($columns as $name => $type) $fields[] = $name . ' ' . $type;
    $fields = implode(", \n\t", $fields) . $add;
    $results[] = $this->exec('CREATE TABLE "' . $copy . '" (' . $fields . ')');
    if (!empty($map)) {
      $results[] = $this->exec('INSERT INTO ' . $copy . ' (' . implode(', ', array_values($map)) . ') SELECT ' . implode(', ', array_keys($map)) . ' FROM ' . $table);
    }
    $results[] = $this->exec("DROP TABLE {$table}");
    $results[] = $this->exec("ALTER TABLE {$copy} RENAME TO {$table}");
    foreach ($results as $result) {
      if ($result === false) {
        $this->db->rollBack();
        break; // $result now equals false below
      }
    }
    if ($result !== false) $this->db->commit();
    $this->pragma('foreign_keys', 'ON');
    return ($result !== false) ? true : false;
  }
  
}

?>