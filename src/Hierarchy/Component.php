<?php

namespace BootPress\Hierarchy;

use BootPress\Database\Component as Database;

// http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
// http://www.evanpetersen.com/item/nested-sets.html
// http://stackoverflow.com/questions/4048151/what-are-the-options-for-storing-hierarchical-data-in-a-relational-database
// http://vadimtropashko.wordpress.com/2008/08/09/one-more-nested-intervals-vs-adjacency-list-comparison/
// http://www.slideshare.net/billkarwin/models-for-hierarchical-data
// http://troels.arvin.dk/db/rdbms/links/#hierarchical

class Component
{
    private $db;
    private $id;
    private $table;
    private $count;
    private $parents;
    public $update = array();

    /*
    The $db $table must have the following fields array(
        $id => 'INTEGER PRIMARY KEY',
        'parent' => 'INTEGER NOT NULL DEFAULT 0',
        'level' => 'INTEGER NOT NULL DEFAULT 0',
        'lft' => 'INTEGER NOT NULL DEFAULT 0',
        'rgt' => 'INTEGER NOT NULL DEFAULT 0'
    )
    All you need to worry about is the 'id' and 'parent'.  This class will take care of the rest.
    */

    public function __construct(Database $db, $table, $id = 'id')
    {
        $this->db = $db;
        $this->id = $id;
        $this->table = $table;
    }

    // this should be called any time you insert into or delete from your hierarchical table
    // http://stackoverflow.com/questions/4664517/how-do-you-convert-a-parent-child-adjacency-table-to-a-nested-set-using-php-an
    // http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
    public function refresh($order = null)
    {
        if (is_null($order)) {
            $order = $this->id;
        }
        $this->count = 0;
        $this->parents = array();
        $this->update = array();
        if ($stmt = $this->db->query('SELECT '.$this->id.', parent FROM '.$this->table.' ORDER BY '.$order)) {
            while (list($id, $parent) = $this->db->fetch($stmt)) {
                if (!isset($this->parents[$parent])) {
                    $this->parents[$parent] = array();
                }
                $this->parents[$parent][] = $id;
            }
            $this->db->close($stmt);
        }
        $this->traverse(0);
        unset($this->update[0]);
        ksort($this->update);
        if ($stmt = $this->db->update($this->table, $this->id, array('level', 'lft', 'rgt'))) {
            foreach ($this->update as $id => $fields) {
                $this->db->update($stmt, $id, $fields);
                unset($this->update[$id]['level']);
            }
            $this->db->close($stmt);
        }
    }

    public function delete($id)
    {
        if ($ids = $this->db->ids(array(
            'SELECT node.id',
            'FROM '.$this->table.' AS node, '.$this->table.' AS parent',
            'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.'.$this->id.' = ?',
            'ORDER BY node.lft',
        ), $id)) {
            $this->db->exec('DELETE FROM '.$this->table.' WHERE '.$this->id.' IN('.implode(', ', $ids).')');

            return $ids;
        }

        return false;
    }

    public function id($field, array $values)
    {
        $from = array();
        $where = array();
        foreach (range(1, count($values)) as $level => $num) {
            $from[] = (isset($previous)) ? "{$this->table} AS t{$num} ON t{$num}.parent = t{$previous}.{$this->id}" : "{$this->table} AS t{$num}";
            $where[] = "t{$num}.level = {$level} AND t{$num}.{$field} = ?";
            $previous = $num;
        }

        return $this->db->value("SELECT t{$num}.{$this->id} \nFROM ".implode("\n  LEFT JOIN ", $from)."\nWHERE ".implode("\n  AND ", $where), $values);
    }

    public function path(array $fields, array $options)
    {
        if (!isset($options['where'])) {
            return array();
        }
        list($field, $id) = array_map('trim', explode('=', $options['where'])); // eg. 'id = 1'
        $path = array();
        if ($stmt = $this->db->query(array(
            'SELECT parent.'.$this->id.', '.$this->fields('parent', $fields),
            'FROM '.$this->table.' AS node',
            'INNER JOIN '.$this->table.' AS parent',
            'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.'.$field.' = ?',
            'ORDER BY node.lft',
        ), $id, 'assoc')) {
            while ($row = $this->db->fetch($stmt)) {
                $path[array_shift($row)] = $row;
            }
            $this->db->close($stmt);
        }

        return $path;
    }

    public function children($id, $fields) // The immediate subordinates of a node ie. no grand children
    {
        $single = (!is_array($fields)) ? $fields : false;
        $children = array();
        if ($stmt = $this->db->query('SELECT '.$this->id.', '.implode(', ', (array) $fields).' FROM '.$this->table.' WHERE parent = ? ORDER BY lft', $id, 'assoc')) {
            while ($row = $this->db->fetch($stmt)) {
                $children[array_shift($row)] = ($single) ? $row[$single] : $row;
            }
            $this->db->close($stmt);
        }

        return $children;
    }

    public function level($depth, $fields)
    {
        $single = (!is_array($fields)) ? $fields : false;
        $level = array();
        if ($stmt = $this->db->query('SELECT '.$this->id.', '.implode(', ', (array) $fields).' FROM '.$this->table.' WHERE level = ? ORDER BY lft', $depth, 'assoc')) {
            while ($row = $this->db->fetch($stmt)) {
                $level[array_shift($row)] = ($single) ? $row[$single] : $row;
            }
            $this->db->close($stmt);
        }

        return $level;
    }

    public function counts($table, $match, $id = null)
    {
        if (!is_null($id)) {
            return (int) $this->db->value(array(
                'SELECT COUNT('.$table.'.'.$match.') AS count',
                'FROM '.$this->table.' AS node',
                'INNER JOIN '.$this->table.' AS parent',
                'INNER JOIN '.$table,
                'WHERE parent.'.$this->id.' = '.(int) $id.' AND node.lft BETWEEN parent.lft AND parent.rgt AND node.'.$this->id.' = '.$table.'.'.$match,
                'GROUP BY parent.'.$this->id,
                'ORDER BY parent.lft',
            ));
        } else {
            $counts = array();
            if ($stmt = $this->db->query(array(
                'SELECT parent.'.$this->id.', COUNT('.$table.'.'.$match.') AS count',
                'FROM '.$this->table.' AS node',
                'INNER JOIN '.$this->table.' AS parent',
                'INNER JOIN '.$table,
                'WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.'.$this->id.' = '.$table.'.'.$match,
                'GROUP BY parent.'.$this->id,
                'ORDER BY parent.lft',
            ))) {
                while (list($id, $count) = $this->db->fetch($stmt)) {
                    $counts[$id] = (int) $count;
                }
                $this->db->close($stmt);
            }

            return $counts;
        }
    }

    public function tree(array $fields, array $options = array())
    {
        $tree = array();
        $having = (isset($options['having'])) ? ' HAVING '.$options['having'] : null; // eg. 'depth <= 1'
        if (isset($options['where'])) {
            list($field, $id) = array_map('trim', explode('=', $options['where'])); // eg. 'id = 1'
            $stmt = $this->db->query(array(
                'SELECT node.'.$this->id.', '.$this->fields('node', (array) $fields).', node.parent, (COUNT(parent.'.$this->id.') - (sub_tree.sub_depth + 1)) AS depth',
                'FROM '.$this->table.' AS node',
                'INNER JOIN '.$this->table.' AS parent',
                'INNER JOIN '.$this->table.' AS sub_parent',
                'INNER JOIN (',
                '  SELECT node.'.$this->id.', (COUNT(parent.'.$this->id.') - 1) AS sub_depth',
                '  FROM '.$this->table.' AS node',
                '  INNER JOIN '.$this->table.' AS parent',
                '  WHERE node.lft BETWEEN parent.lft AND parent.rgt',
                '  AND node.'.$field.' = ?',
                '  GROUP BY node.'.$this->id,
                '  ORDER BY node.lft',
                ') AS sub_tree',
                'WHERE node.lft BETWEEN parent.lft AND parent.rgt',
                '  AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt',
                '  AND sub_parent.'.$this->id.' = sub_tree.'.$this->id,
                'GROUP BY node.'.$this->id.$having,
                'ORDER BY node.lft',
            ), $id, 'assoc');
        } else {
            $stmt = $this->db->query(array(
                'SELECT node.'.$this->id.', '.$this->fields('node', (array) $fields).', node.parent, (COUNT(parent.'.$this->id.') - 1) AS depth',
                'FROM '.$this->table.' AS node',
                'INNER JOIN '.$this->table.' AS parent',
                'WHERE node.lft BETWEEN parent.lft AND parent.rgt',
                'GROUP BY node.'.$this->id.$having,
                'ORDER BY node.lft',
            ), '', 'assoc');
        }
        while ($row = $this->db->fetch($stmt)) {
            $tree[array_shift($row)] = $row;
        }
        $this->db->close($stmt);

        return $tree;
    }

    public function lister(array $tree, array $nest = null)
    {
        if (is_null($nest)) {
            return $this->lister($tree, $this->nestify($tree));
        }
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
    public function nestify(array $tree)
    {
        $nested = array();
        $children = array();
        foreach ($tree as $id => $fields) {
            if ($fields['depth'] == 0) {
                $nested[$id] = array(); // $fields;
                $children[$fields['depth'] + 1] = $id;
            } else {
                $parent = &$nested;
                for ($i = 1; $i <= $fields['depth']; ++$i) {
                    if (isset($children[$i])) {
                        $parent = &$parent[$children[$i]];
                    }
                }
                $parent[$id] = array(); // $fields;
                $children[$fields['depth'] + 1] = $id;
            }
        }

        return $nested;
    }

    // http://stackoverflow.com/questions/16999530/how-do-i-format-nested-set-model-data-into-an-array
    public function flatten(array $nest, array $related = array())
    {
        $children = array();
        foreach ($nest as $id => $values) {
            $parents = $related;
            $parents[] = $id;
            if (!empty($values)) {
                foreach ($this->flatten($values, $parents) as $nest) {
                    $children[] = $nest;
                }
            } else {
                $children[] = $parents;
            }
        }

        return $children;
    }

    private function traverse($id, $level = -1)
    {
        $lft = $this->count;
        ++$this->count;
        if (isset($this->parents[$id])) {
            foreach ($this->parents[$id] as $child) {
                $this->traverse($child, $level + 1);
            }
            unset($this->parents[$id]);
        }
        $rgt = $this->count;
        ++$this->count;
        $this->update[$id] = array('level' => $level, 'lft' => $lft, 'rgt' => $rgt);
    }

    private function fields($prefix, array $fields)
    {
        return $prefix.'.'.implode(', '.$prefix.'.', $fields);
    }
}
