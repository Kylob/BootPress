<?php

namespace BootPress\SQLite;

class Fts
{
    private $db;
    private $rank;

    public function __construct(Component $db)
    {
        $this->db = $db;
    }

    public function create($table, array $fields, $tokenize = 'porter')
    {
        $fields = implode(', ', $fields);
        $query = "CREATE VIRTUAL TABLE {$table} USING fts4({$fields}, tokenize={$tokenize})";
        $executed = $this->db->info('tables', $table);
        if ($query == $executed) {
            return false; // the table has already been created
        }
        if ($executed !== false) {
            $this->db->exec('DROP TABLE '.$table);
        }
        $this->db->exec($query);
        $this->db->info('tables', $table, $query); // add or update
        return true; // the table has been created anew
    }

    public function count($table, $search, $where = '')
    {
        if (empty($where)) {
            $where = 'WHERE';
        } else {
            $where = (stripos($where, 'WHERE') === false) ? "WHERE {$where} AND" : "{$where} AND";
        }

        return $this->db->value("SELECT COUNT(*) FROM {$table} AS s {$where} s.{$table} MATCH ?", $search);
    }

    public function search($table, $search, $limit = '', $where = '', array $fields = array(), array $weights = array())
    {
        if (is_null($this->rank)) {
            $this->rank = $this->db->connection()->createFunction('rank', array(&$this, 'rank'), 2);
        }
        if (!empty($where)) {
            $where = (stripos($where, 'WHERE') === false) ? "WHERE {$where} AND" : "{$where} AND";
        } else {
            $where = 'WHERE';
        }
        $fields = (!empty($fields)) ? implode(', ', $fields).',' : '';
        $weights = "'".implode(',', $weights)."'"; // we pass this along to our rank function
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
                'JOIN (',
                "  SELECT s.docid, rank(matchinfo(s.{$table}), {$weights}) AS rank",
                "  FROM {$table} AS s {$where} s.{$table} MATCH ?",
                "  ORDER BY rank DESC LIMIT {$length} OFFSET {$offset}",
                ') AS ranktable USING (docid)',
            ));
            $order = 'ranktable.rank';
            $values[] = $search; // add one more to the MATCH
        }
        #-- Query --#
        $results = array();
        if ($stmt = $this->db->query(array(
            "SELECT s.docid, {$fields}",
            "  snippet(s.{$table}, '<b>', '</b>', '<b>...</b>', -1, 50) AS snippet,",
            "  offsets(s.{$table}) AS offsets,",
            "  rank(matchinfo(s.{$table}), {$weights}) AS rank",
            "FROM {$table} AS s {$join} {$where} s.{$table} MATCH ?",
            "ORDER BY {$order} DESC",
        ), $values, 'assoc')) {
            while ($row = $this->db->fetch($stmt)) {
                $results[] = $row;
            }
            $this->db->close($stmt);
        }

        return $results;
    }

    public function words($table, $search, $docid)
    {
        $words = array();
        $search = $this->search($table, $search, 1, 's.docid = '.$docid);
        if (empty($search)) {
            return $words;
        }
        $row = array_shift($search);
        $fields = $this->db->row("SELECT * FROM {$table} WHERE docid = ? LIMIT 1", $row['docid'], 'assoc');

        return $this->offset(array_merge($fields, $row), array_keys($fields));
    }

    public function offset(array $row, array $fields)
    {
        $words = array();
        $search = array();
        foreach ($fields as $value) {
            $search[] = (isset($row[$value])) ? $row[$value] : '';
        }
        $offsets = explode(' ', $row['offsets']);
        $combine = array();
        for ($i = 0; $i < (count($offsets) / 4); ++$i) {
            list($column, $term, $byte, $size) = array_slice($offsets, $i * 4, 4);
            $word = strtolower(substr($search[$column], $byte, $size));
            if ($combine == array($column, $term, $byte)) {
                $word = array_pop($words).' '.$word;
            }
            $words[] = $word;
            $combine = array($column, $term + 1, $byte + $size + 1); // same column, next term, one space away
        }
        $words = array_unique($words);
        rsort($words);

        return $words;
    }

    public function rank($info, $weights)
    {
        if (!empty($weights)) {
            $weights = explode(',', $weights);
        }
        $score = (float) 0.0; // the value to return
        $isize = 4; // the amount of string we need to collect for each integer
        $phrases = (int) ord(substr($info, 0, $isize));
        $columns = (int) ord(substr($info, $isize, $isize));
        $string = $phrases.' '.$columns.' ';
        for ($p = 0; $p < $phrases; ++$p) {
            $term = substr($info, (2 + $p * $columns * 3) * $isize); // the start of $info for current phrase
            for ($c = 0; $c < $columns; ++$c) {
                $here = (float) ord(substr($term, (3 * $c * $isize), 1)); // total occurrences in this row and column
                $total = (float) ord(substr($term, (3 * $c + 1) * $isize, 1)); // total occurrences for all rows in this column
                $rows = (float) ord(substr($term, (3 * $c + 2) * $isize, 1)); // total rows with at least one occurence in this column
                $relevance = (!empty($total)) ? ($rows / $total) * $here : 0;
                $weight = (isset($weights[$c])) ? (float) $weights[$c] : 1;
                $score += $relevance * $weight;
                $string .= $here.$total.$rows.' ('.round($relevance, 2).'*'.$weight.') ';
            }
        }
        // return $string . '- ' . $score; // to debug
        return $score;
    }
}
