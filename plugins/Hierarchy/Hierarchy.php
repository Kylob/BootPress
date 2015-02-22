<?php

// http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
// http://www.evanpetersen.com/item/nested-sets.html
// http://stackoverflow.com/questions/4048151/what-are-the-options-for-storing-hierarchical-data-in-a-relational-database
// http://vadimtropashko.wordpress.com/2008/08/09/one-more-nested-intervals-vs-adjacency-list-comparison/
// http://www.slideshare.net/billkarwin/models-for-hierarchical-data
// http://troels.arvin.dk/db/rdbms/links/#hierarchical

class Hierarchy {

  private $db;
  private $table;
  private $count;
  private $parents;
  public $update = array();
  
  /*
  The $db $table must have the following fields array(
    'id' => 'INTEGER PRIMARY KEY',
    'parent' => 'INTEGER NOT NULL DEFAULT 0',
    'level' => 'INTEGER NOT NULL DEFAULT 0',
    'lft' => 'INTEGER NOT NULL DEFAULT 0',
    'rgt' => 'INTEGER NOT NULL DEFAULT 0'
  )
  All you need to worry about is the 'id' and 'parent'.  This class will take care of the rest.
  */
  
  public function __construct (Database $db, $table) {
    $this->db = $db;
    $this->table = $table;
  }
  
  public function delete ($id) {
    $this->db->query(array(
      'SELECT node.id',
      'FROM ' . $this->table . ' AS node, ' . $this->table . ' AS parent',
      'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.id = ?',
      'ORDER BY node.lft'
    ), array($id));
    $deleted = $this->db->fetch('row', 'all');
    $this->db->delete($this->table, 'id', $deleted);
    return $deleted;
  }
  
  public function id ($field, array $values) {
    $from = array();
    $where = array();
    foreach (range(1, count($values)) as $level => $num) {
      $from[] = (isset($previous)) ? "{$this->table} AS t{$num} ON t{$num}.parent = t{$previous}.id" : "{$this->table} AS t{$num}";
      $where[] = "t{$num}.level = {$level} AND t{$num}.{$field} = ?";
      $previous = $num;
    }
    return $this->db->value("SELECT t{$num}.id \nFROM " . implode("\n  LEFT JOIN ", $from) . "\nWHERE " . implode("\n  AND ", $where), $values);
  }
  
  public function path (array $fields, array $options) {
    if (!isset($options['where'])) return array();
    list($field, $id) = array_map('trim', explode('=', $options['where'])); // eg. 'id = 1'
    $path = array();
    $this->db->query(array(
      'SELECT parent.id, ' . $this->fields('parent', $fields),
      'FROM ' . $this->table . ' AS node',
      'INNER JOIN ' . $this->table . ' AS parent',
      'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.' . $field . ' = ?',
      'ORDER BY node.lft'
    ), array($id));
    while ($row = $this->db->fetch('assoc')) $path[array_shift($row)] = $row;
    return $path;
  }
  
  public function children ($id, $fields) { // no grand children
    $single = (!is_array($fields)) ? $fields : false;
    $children = array();
    $this->db->query('SELECT id, ' . implode(', ', (array) $fields) . ' FROM ' . $this->table . ' WHERE parent = ? ORDER BY lft', array($id));
    while ($row = $this->db->fetch('assoc')) $children[array_shift($row)] = ($single) ? $row[$single] : $row;
    return $children;
  }
  
  public function level ($depth, $fields) {
    $single = (!is_array($fields)) ? $fields : false;
    $level = array();
    $this->db->query('SELECT id, ' . implode(', ', (array) $fields) . ' FROM ' . $this->table . ' WHERE level = ? ORDER BY lft', array($depth));
    while ($row = $this->db->fetch('assoc')) $level[array_shift($row)] = ($single) ? $row[$single] : $row;
    return $level;
  }
  
  public function counts ($table, $match, $id=null) {
    if (!is_null($id)) {
      return $this->db->value(array(
        'SELECT COUNT(' . $table . '.' . $match . ') AS count',
        'FROM ' . $this->table . ' AS node',
        'INNER JOIN ' . $this->table . ' AS parent',
        'INNER JOIN ' . $table,
        'WHERE parent.id = ' . (int) $id . ' AND node.lft BETWEEN parent.lft AND parent.rgt AND node.id = ' . $table . '.' . $match,
        'GROUP BY parent.id',
        'ORDER BY parent.lft'
      ));
    } else {
      $counts = array();
      $this->db->query(array(
        'SELECT parent.id, COUNT(' . $table . '.' . $match . ') AS count',
        'FROM ' . $this->table . ' AS node',
        'INNER JOIN ' . $this->table . ' AS parent',
        'INNER JOIN ' . $table,
        'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.id = ' . $table . '.' . $match,
        'GROUP BY parent.id',
        'ORDER BY parent.lft'
      ));
      while (list($id, $count) = $this->db->fetch('row')) $counts[$id] = $count;
      return $counts;
    }
  }
  
  public function tree (array $fields, $options=array()) {
    $tree = array();
    $having = (isset($options['having'])) ? ' HAVING ' . $options['having'] : null; // eg. 'depth <= 1'
    if (isset($options['where'])) {
      list($field, $id) = array_map('trim', explode('=', $options['where'])); // eg. 'id = 1'
      $this->db->query(array(
        'SELECT node.id, ' . $this->fields('node', (array) $fields) . ', node.parent, (COUNT(parent.id) - (sub_tree.sub_depth + 1)) AS depth',
        'FROM ' . $this->table . ' AS node',
        'INNER JOIN ' . $this->table . ' AS parent',
        'INNER JOIN ' . $this->table . ' AS sub_parent',
        'INNER JOIN (',
        '  SELECT node.id, (COUNT(parent.id) - 1) AS sub_depth',
        '  FROM ' . $this->table . ' AS node',
        '  INNER JOIN ' . $this->table . ' AS parent',
        '  WHERE node.lft BETWEEN parent.lft AND parent.rgt',
        '  AND node.' . $field . ' = ?',
        '  GROUP BY node.id',
        '  ORDER BY node.lft',
        ') AS sub_tree',
        'WHERE node.lft BETWEEN parent.lft AND parent.rgt',
        '  AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt',
        '  AND sub_parent.id = sub_tree.id',
        'GROUP BY node.id' . $having,
        'ORDER BY node.lft'
      ), array($id));
    } else {
      $this->db->query(array(
        'SELECT node.id, ' . $this->fields('node', (array) $fields) . ', node.parent, (COUNT(parent.id) - 1) AS depth',
        'FROM ' . $this->table . ' AS node',
        'INNER JOIN ' . $this->table . ' AS parent',
        'WHERE node.lft BETWEEN parent.lft AND parent.rgt',
        'GROUP BY node.id' . $having,
        'ORDER BY node.lft'
      ));
    }
    while ($row = $this->db->fetch('assoc')) $tree[array_shift($row)] = $row;
    return $tree;
  }
  
  public function lister ($tree, $nest=null) {
    if (is_null($nest)) return $this->lister($tree, $this->nestify($tree));
    $list = array();
    foreach ($nest as $id => $values) {
      if (!empty($values)) {
        $list[array_shift($tree[$id])] = $this->lister($tree, $values);
      } else {
        $list[] = array_shift($tree[$id]);
      }
    }
    return $list;
  }
  
  // http://semlabs.co.uk/journal/converting-nested-set-model-data-in-to-multi-dimensional-arrays-in-php
  public function nestify ($tree) {
    $nested = array();
    $children = array();
    foreach ($tree as $id => $fields) {
      if ($fields['depth'] == 0) {
        $nested[$id] = array(); // $fields;
        $children[$fields['depth'] + 1] = $id;
      } else {
        $parent =& $nested;
        for ($i=1; $i <= $fields['depth']; $i++) {
          if (isset($children[$i])) $parent =& $parent[$children[$i]];
        }
        $parent[$id] = array(); // $fields;
        $children[$fields['depth'] + 1] = $id;
      }
    }
    return $nested;
  }
  
  // http://stackoverflow.com/questions/16999530/how-do-i-format-nested-set-model-data-into-an-array
  function flatten ($nest, $related=array()) {
    $children = array();
    foreach ($nest as $id => $values) {
      $parents = $related;
      $parents[] = $id;
      if (!empty($values)) {
        foreach ($this->flatten($values, $parents) as $nest) $children[] = $nest;
      } else {
        $children[] = $parents;
      }
    }
    return $children;
  }
  
  // this should be called any time you insert into or delete from your hierarchical table
  // http://stackoverflow.com/questions/4664517/how-do-you-convert-a-parent-child-adjacency-table-to-a-nested-set-using-php-an
  // http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
  public function refresh ($order='id') { // $hier->renest, reorder, order, refresh
    $this->count = 0;
    $this->parents = array();
    $this->update = array();
    $this->db->query('SELECT id, parent FROM ' . $this->table . ' ORDER BY ' . $order);
    while (list($id, $parent) = $this->db->fetch('row')) {
      if (!isset($this->parents[$parent])) $this->parents[$parent] = array();
      $this->parents[$parent][] = $id;
    }
    $this->traverse(0);
    unset($this->update[0]);
    ksort($this->update);
    $this->db->update($this->table, 'id', $this->update);
    foreach ($this->update as $id => $fields) unset($this->update[$id]['level']);
  }
  
  private function traverse ($id, $level=-1) {
    $lft = $this->count;
    $this->count++;
    if (isset($this->parents[$id])) {
      foreach ($this->parents[$id] as $child) $this->traverse($child, $level + 1);
      unset($this->parents[$id]);
    }
    $rgt = $this->count;
    $this->count++;
    $this->update[$id] = array('level'=>$level, 'lft'=>$lft, 'rgt'=>$rgt);
  }
  
  private function fields ($prefix, array $fields) {
    return $prefix . '.' . implode(', ' . $prefix . '.', $fields);
  }
  
}

?>