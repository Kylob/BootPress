<?php

class SQLite extends Database {

  public $fts;
  public $created = false; // whether or not this is a new database
  private $info = array();
  
  public function __construct ($file='', $query_builder=false) {
    if (strpos($file, BASE) !== false) {
      if (!file_exists($file)) {
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
        $this->created = true;
      }
    } else {
      $file = ':memory:';
      $this->created = true;
    }
    parent::__construct(ci_load_database('sqlite3', $file, $query_builder));
    $this->ci->simple_query("PRAGMA foreign_keys = ON");
    $this->fts = new FTS($this);
  }
  
  public function create ($table, $fields, $index=array(), $changes=array()) {
    $columns = array();
    foreach ($fields as $name => $type) $columns[] = (is_int($name)) ? $type : $name . ' ' . $type;
    $columns = implode(", \n\t", $columns);
    $query = 'CREATE TABLE ' . $table . ' (' . $columns . ')';
    $executed = $this->info('tables', $table);
    // See http://www.sqlite.org/fileformat2.html - 2.5 Storage Of The SQL Database Schema
    if (preg_replace('/(\'|")/', '', $query) == preg_replace('/(\'|")/', '', $executed)) {
      $this->index($table, $index); // make sure they are all correct also
      return false; // the table has already been created in it's requested state
    }
    $this->info('tables', $table, $query); // to either add or update
    if ($executed) { // then this table is being altered in some way
      $this->index($table, '');
      $this->alter($table, $fields, $changes, $columns);
      trigger_error("<pre>Just FYI, an SQLite table was changed:\n\nFrom: {$executed}\n\nTo: {$query}</pre>");
    } else {
      $this->ci->simple_query($query); // We should only get here once
    }
    $this->index($table, $index);
    return true; // the table has been created (or altered)
  }
  
  public function settings () {
    switch (func_num_args()) {
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
        if ($update) $this->query('UPDATE database_settings SET settings = ?', array(serialize($this->info['settings'])));
        break;
    }
  }
  
  public function info ($master) { // only made public so that $this->fts can call it
    if ($master == 'settings') {
      if (!isset($this->info['settings'])) {
        if ($this->create('database_settings', array('settings'=>'TEXT NOT NULL DEFAULT ""'))) {
          $this->insert('database_settings', array('settings'=>serialize(array())));
        }
        $this->info['settings'] = array();
        if ($settings = $this->value('SELECT settings FROM database_settings')) {
          $this->info['settings'] = unserialize($settings);
        }
      }
    } elseif (!isset($this->info[$master])) { // 'tables' or 'indexes'
      $this->query("SELECT type, name, tbl_name, sql FROM sqlite_master");
      while (list($type, $name, $table, $sql) = $this->fetch('row')) {
        switch ($type) {
          case 'table': $this->info['tables'][$table] = $sql; break;
          case 'index': if (!empty($sql)) $this->info['indexes'][$table][$name] = $sql; break;
        }
      }
    }
    switch (func_num_args()) {
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
  
  public function insert ($table, $insert, $and='', $or='') {
    $ids = array();
    $multiple = (isset($insert[0])) ? true : false;
    if (!$multiple) $insert = array($insert);
    $query = (!empty($or)) ? 'INSERT ' . $or . ' INTO ' . $table : 'INSERT INTO ' . $table;
    $query .= ' (' . implode(', ', array_keys($insert[0])) . ") VALUES (" . implode(', ', array_fill(0, count($insert[0]), '?')) . ') ' . $and;
    if ($multiple) $this->ci->trans_start();
    $stmt = $this->ci->conn_id->prepare($query);
    foreach ($insert as $values) {
      foreach (array_values($values) as $key => $value) $stmt->bindValue($key + 1, $value);
      $ids[] = ($stmt->execute()) ? $this->ci->insert_id() : 0;
    }
    if ($multiple) $this->ci->trans_complete();
    return ($multiple) ? $ids : array_shift($ids);
  }
  
  public function update ($table, $column, $update, $and='') {
    $affected_rows = 0;
    $fields = array_slice($update, 0, 1);
    $fields = array_shift($fields);
    $this->ci->trans_start();
    $stmt = $this->ci->conn_id->prepare('UPDATE ' . $table . ' SET ' . implode(' = ?, ', array_keys($fields)) . ' = ? WHERE ' . $column . ' = ? ' . $and);
    foreach ($update as $id => $values) {
      foreach (array_values($values) as $key => $value) $stmt->bindValue($key + 1, $value);
      $stmt->bindValue($key + 2, $id);
      if ($stmt->execute()) $affected_rows += $this->ci->affected_rows();
    }
    $this->ci->trans_complete();
    return $affected_rows;
  }
  
  private function alter ($table, $fields, $changes, $columns) {
    $map = array();
    if ($compare = $this->row('SELECT * FROM ' . $table . ' LIMIT 1')) {
      foreach ($changes as $old => $new) {
        if (isset($fields[$new]) && isset($compare[$old])) $map[$old] = $new; // legitimate changes
      }
      foreach (array_keys($compare) as $field) {
        if (isset($fields[$field]) && !isset($map[$field])) $map[$field] = $field; // old fields that match the new
      }
    }
    $this->ci->simple_query("PRAGMA foreign_keys = OFF");
    $this->ci->trans_start();
    $this->ci->simple_query('CREATE TABLE ' . $table . '_copy (' . $columns . ')');
    if (!empty($map)) {
      $this->ci->simple_query('INSERT INTO ' . $table . '_copy (' . implode(', ', array_values($map)) . ') SELECT ' . implode(', ', array_keys($map)) . ' FROM ' . $table);
    }
    $this->ci->simple_query('DROP TABLE ' . $table);
    $this->ci->simple_query('ALTER TABLE ' . $table . '_copy RENAME TO ' . $table);
    $this->ci->trans_complete();
    $this->ci->simple_query("PRAGMA foreign_keys = ON");
  }
  
  private function index ($table, $columns) {
    $queries = array();
    $outdated = $this->info('indexes', $table);
    if (empty($outdated)) $outdated = array();
    if (!empty($columns)) {
      foreach ((array) $columns as $key => $indexes) {
        $unique = (!is_int($key) && strtolower($key) == 'unique') ? ' UNIQUE ' : ' ';
        $indexes = array_map('trim', explode(',', $indexes));
        $name = $table . '_' . implode('_', $indexes);
        $sql = 'CREATE' . $unique . 'INDEX ' . $name . ' ON ' . $table . ' (' . implode(', ', $indexes) . ')';
        $queries[$name] = $sql;
        if (!isset($outdated[$name]) || $outdated[$name] != $sql) {
          if (isset($outdated[$name])) $this->ci->simple_query('DROP INDEX ' . $name);
          $this->ci->simple_query($sql);
        }
      }
    }
    foreach ($outdated as $name => $sql) if (!isset($queries[$name])) $this->ci->simple_query('DROP INDEX ' . $name);
    $this->info('indexes', $table, $queries);
  }
  
}

class FTS {

  private $db;
  
  public function __construct ($db) {
    $this->db = $db;
  }
  
  public function create ($table, $columns, $tokenize='porter') {
    $columns = implode(', ', (array) $columns);
    $query = "CREATE VIRTUAL TABLE {$table} USING fts4({$columns}, tokenize={$tokenize})";
    $executed = $this->db->info('tables', $table);
    if ($query == $executed) return false; // the table has already been created
    if ($executed !== false) $this->db->ci->simple_query('DROP TABLE ' . $table);
    $this->db->ci->simple_query($query);
    $this->db->info('tables', $table, $query); // add or update
    return true; // the table has been created anew
  }
  
  public function upsert ($table, $columns, $values) {
    $columns = (array) $columns;
    $docids = implode(',', array_keys($values));
    $this->db->query("SELECT docid FROM {$table} WHERE docid IN ({$docids})");
    $docids = array(); // reset
    while (list($docid) = $this->db->fetch('row')) $docids[$docid] = '';
    $this->db->ci->trans_start();
    foreach ($values as $docid => $upsert) {
      if (!is_array($upsert)) $upsert = array($upsert);
      if (isset($docids[$docid])) {
        if (!isset($update)) {
          $update = array();
          foreach ($upsert as $key => $value) $update[$columns[$key]] = $value;
          $update = array($docid => $update); // with column names
        } else {
          $update[$docid] = $values; // without
        }
      } else {
        if (!isset($insert)) {
          $insert = array('docid' => $docid);
          foreach ($upsert as $key => $value) $insert[$columns[$key]] = $value;
          $insert = array($insert); // with column names
        } else {
          array_unshift($upsert, $docid);
          $insert[] = $upsert; // without
        }
      }
    }
    if (isset($update)) $this->db->update($table, 'docid', $update);
    if (isset($insert)) $this->db->insert($table, $insert);
    $this->db->ci->trans_complete();
  }

  public function count ($table, $search, $where='') {
    if (!empty($where) && stripos($where, 'WHERE') === false) $where = 'WHERE ' . $where;
    if (!empty($search)) {
      $where = (empty($where)) ? 'WHERE' : $where . ' AND';
      return ($count = $this->db->value("SELECT COUNT(*) FROM {$table} AS s {$where} s.{$table} MATCH ?", array($search))) ? $count : 0;
    } else {
      return ($count = $this->db->value("SELECT COUNT(*) FROM {$table} AS s {$where}")) ? $count : 0;
    }
  }
  
  public function search ($table, $search, $limit='', $where='', $fields=array(), $weights=array()) {
    static $rank = null; // our helper function
    if (is_null($rank)) $rank = $this->db->ci->conn_id->createFunction('rank', array(&$this, 'rank'), 2);
    if (!empty($where)) {
      $where = (stripos($where, 'WHERE') === false) ? 'WHERE ' . $where . ' AND' : $where . ' AND';
    } else {
      $where = 'WHERE';
    }
    $fields = (!empty($fields)) ? implode(', ', $fields) .  ',' : '';
    $weights = "'" . implode(',', $weights) . "'"; // we pass this along to our rank function
    #-- Join, Order, Values --#
    $join = '';
    $order = 'rank';
    $values = array($search);
    if (!empty($limit)) {
      if (is_numeric($limit)) {
        $offset = 0;
        $length = $limit;
      } else {
        $limit = explode(',', preg_replace('/[^0-9,]/', '', $limit));
        $offset = (isset($limit[0])) ? (int) $limit[0] : 0;
        $length = (isset($limit[1])) ? (int) $limit[1] : 10;
      }
      $join = implode("\n", array(
        "JOIN (",
        "  SELECT s.docid, rank(matchinfo(s.{$table}), {$weights}) AS rank",
        "  FROM {$table} AS s {$where} s.{$table} MATCH ?",
        "  ORDER BY rank DESC LIMIT {$length} OFFSET {$offset}",
        ") AS ranktable USING (docid)"
      ));
      $order = 'ranktable.rank';
      $values[] = $search; // add one more to the MATCH
    }
    #-- Query --#
    $this->db->query(array(
      "SELECT s.docid, {$fields}",
      "  snippet(s.{$table}, '<b>', '</b>', '<b>...</b>', -1, 50) AS snippet,",
      "  offsets(s.{$table}) AS offsets,",
      "  rank(matchinfo(s.{$table}), {$weights}) AS rank",
      "FROM {$table} AS s {$join} {$where} s.{$table} MATCH ?",
      "ORDER BY {$order} DESC",
    ), $values);
    return $this->db->fetch('assoc', 'all');
  }
  
  public function words ($table, $search, $docid) {
    $words = array();
    $search = $this->search($table, $search, 1, 's.docid = ' . $docid);
    if (empty($search)) return $words;
    $row = array_shift($search);
    $offsets = explode(' ', $row['offsets']);
    if (empty($offsets)) return $words;
    $row = array_values($this->db->row("SELECT * FROM {$table} WHERE docid = ? LIMIT 1", array($row['docid'])));
    $prev = $next = 0; // to combine words
    for ($i=0; $i<(count($offsets) / 4); $i++) {
      list($col, $term, $byte, $size) = array_slice($offsets, $i * 4, 4);
      if (!empty($next) && $byte == $next) {
        $word = strtolower(substr($row[$col], $prev, $size + ($next - $prev)));
        array_pop($words);
      } else {
        $word = strtolower(substr($row[$col], $byte, $size));
        $prev = $byte;
      }
      $words[] = $word;
      $next = $byte + $size + 1;
    }
    $words = array_unique($words);
    rsort($words);
    return $words;
  }
  
  public function rank ($info, $weights) { // public so that it can be registered in $this->query()
    if (!empty($weights)) $weights = explode(',', $weights);
    $score = (float) 0.0; // the value to return
    $isize = 4; // the amount of string we need to collect for each integer
    $phrases = (int) ord(substr($info, 0, $isize));
    $columns = (int) ord(substr($info, $isize, $isize));
    $string = $phrases . ' ' . $columns . ' ';
    for ($p=0; $p<$phrases; $p++) {
      $term = substr($info, (2 + $p * $columns * 3) * $isize); // the start of $info for current phrase
      for ($c=0; $c<$columns; $c++) {
        $here = (float) ord(substr($term, (3 * $c * $isize), 1)); // total occurrences in this row and column
        $total = (float) ord(substr($term, (3 * $c + 1) * $isize, 1)); // total occurrences for all rows in this column
        $rows = (float) ord(substr($term, (3 * $c + 2) * $isize, 1)); // total rows with at least one occurence in this column
        $relevance = (!empty($total)) ? ($rows / $total) * $here : 0;
        $weight = (isset($weights[$c])) ? (float) $weights[$c] : 1;
        $score += $relevance * $weight;
        $string .= $here . $total . $rows . ' (' . round($relevance, 2) . '*' . $weight . ') ';
      }
    }
    // return $string . '- ' . $score; // to debug
    return $score;
  }
  
  static function fulltext ($string) {
    global $ci;
    $ci->load->helper('text');
    $string = str_replace(array("\r\n", "\r", "\n"), ' ', strip_tags(nl2br($string)));
    $string = convert_accented_characters(entities_to_ascii($string));
    return trim(preg_replace('/\s(?=\s)/', '', $string));
  }
  
}

?>