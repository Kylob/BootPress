<?php

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
    if ($executed !== false) $this->db->exec("DROP TABLE {$table}");
    $this->db->exec($query);
    $this->db->info('tables', $table, $query); // add or update
    return true; // the table has been created anew
  }
  
  public function upsert ($table, $columns, $values) {
    $columns = (array) $columns;
    $docids = implode(',', array_keys($values));
    $this->db->query("SELECT docid FROM {$table} WHERE docid IN ({$docids})");
    $docids = array(); // reset
    while (list($docid) = $this->db->fetch('row')) $docids[$docid] = '';
    foreach ($values as $docid => $values) {
      $array = array('docid'=>$docid);
      foreach ($values as $key => $value) $array[$columns[$key]] = $value;
      if (isset($docids[$docid])) {
        if (!isset($update)) {
          $ids = array(array_shift($array));
          $update = array($array); // with column names
        } else {
          $ids[] = array_shift($array);
          $update[] = array_values($array); // without
        }
      } else {
        if (!isset($insert)) $insert = array($array); // with column names
        else $insert[] = array_values($array); // without
      }
    }
    $upserted = 0;
    if (isset($update)) $this->db->update($table, $update, 'docid', $ids);
    if (isset($insert)) $this->db->insert($table, $insert);
    return $upserted;
  }

  public function count ($table, $search='', $ids=array()) {
    if (!empty($search)) {
      $ids = (!empty($ids)) ? 'docid IN(' . implode(',', $ids) . ') AND' : '';
      return $this->db->value("SELECT COUNT(*) FROM {$table} WHERE {$ids} {$table} MATCH ?", array($search));
    } else {
      return $this->db->value("SELECT COUNT(*) FROM {$table}");
    }
  }
  
  public function search ($table, $search, $limit='', $weights=array(), $ids=array()) {
    static $rank = false; // our helper function
    if (!$rank) $rank = $this->db->db->sqliteCreateFunction('rank', array(&$this, 'rank'), 2);
    $ids = (!empty($ids)) ? 'docid IN(' . implode(',', $ids) . ') AND' : '';
    $weights = "'" . implode(',', $weights) . "'"; // we pass this along to our rank function
    #-- Join, Order, Values --#
    $join = '';
    $order = 'rank';
    $values = array($search);
    if (!empty($limit)) {
      $limit = explode(',', preg_replace('/[^0-9,]/', '', $limit));
      $offset = (isset($limit[0])) ? (int) $limit[0] : 0;
      $length = (isset($limit[1])) ? (int) $limit[1] : 10;
      $join = "JOIN (
                 SELECT docid, rank(matchinfo({$table}), {$weights}) AS rank
                 FROM {$table} WHERE {$ids} {$table} MATCH ?
                 ORDER BY rank DESC LIMIT {$length} OFFSET {$offset}
               ) AS ranktable USING (docid)";
      $order = 'ranktable.rank';
      $values[] = $search; // add one more to the MATCH
    }
    #-- Query --#
    $query = "SELECT docid,
                     snippet({$table}, '<b>', '</b>', '<b>...</b>', -1, 50) AS snippet,
                     offsets({$table}) AS offsets,
                     rank(matchinfo({$table}), {$weights}) AS rank
              FROM {$table} {$join}
              WHERE {$ids} {$table} MATCH ?
              ORDER BY {$order} DESC";
    return $this->db->query($query, $values);
  }
  
  public function words ($docid, $table, $search) {
    $words = array();
    $this->search($table, $search, '0,1', array(), array($docid));
    $row = $this->db->fetch('assoc');
    $offsets = explode(' ', $row['offsets']);
    if (empty($offsets)) return $words;
    $this->db->query("SELECT * FROM {$table} WHERE docid = ? LIMIT 1", array($docid));
    $row = $this->db->fetch('row');
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
  
  public function fulltext ($string) {
    $string = str_replace(array("\r\n", "\r", "\n"), ' ', strip_tags(nl2br($string)));
    // $string = strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES))); // decode named entities
    // $string = preg_replace('//e', 'chr(\\1)', $string); // decode numbered entities
    // $string = iconv("utf-8", "ascii//TRANSLIT", $string); // convert characters
    $string = preg_replace("/&#?[a-z0-9]{2,8};/i", '', $string); // remove html entities
    // $string = preg_replace('/[^a-z0-9\s]/i', '', $string); // make alpha numeric
    $string = preg_replace('/\s(?=\s)/', '', $string); // remove extraneous spaces
    return trim($string);
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
  
}

?>