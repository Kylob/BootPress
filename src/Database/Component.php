<?php

namespace BootPress\Database;

class Component extends Driver
{
    protected $prepared = array();

    public function exec($query, $values = array())
    {
        if ($stmt = $this->prepare($query)) {
            $result = $this->execute($stmt, $values);
            $this->close($stmt);
        }

        return (isset($result)) ? $result : false;
    }

    public function query($select, $values = array(), $fetch = 'row')
    {
        if ($stmt = $this->prepare($select, $fetch)) {
            if ($this->prepared[$stmt]['type'] == 'SELECT' && $this->execute($stmt, $values)) {
                return $stmt;
            }
            $this->close($stmt);
        }

        return false;
    }

    public function all($select, $values = array(), $fetch = 'row')
    {
        $rows = array();
        if ($stmt = $this->query($select, $values, $fetch)) {
            while ($row = $this->fetch($stmt)) {
                $rows[] = $row;
            }
            $this->close($stmt);
        }

        return $rows;
    }

    public function ids($select, $values = array())
    {
        $ids = array();
        if ($stmt = $this->query($select, $values, 'row')) {
            while ($row = $this->fetch($stmt)) {
                $ids[] = (int) array_shift($row);
            }
            $this->close($stmt);
        }

        return (!empty($ids)) ? $ids : false;
    }

    public function row($select, $values = array(), $fetch = 'row')
    {
        if ($stmt = $this->query($select, $values, $fetch)) {
            $row = $this->fetch($stmt);
            $this->close($stmt);
        }

        return (isset($row) && !empty($row)) ? $row : false;
    }

    public function value($select, $values = array())
    {
        return ($row = $this->row($select, $values, 'row')) ? array_shift($row) : false;
    }

    public function insert($table, array $data, $and = '')
    {
        if (isset($this->prepared[$table])) {
            return $this->execute($table, $data);
        }
        $single = (count(array_filter(array_keys($data), 'is_string')) > 0) ? $data : false;
        if ($single) {
            $data = array_keys($data);
        }
        if (stripos($table, ' INTO ') !== false) { // eg. 'OR IGNORE INTO table'
            $query = "INSERT {$table} ";
        } else {
            $query = "INSERT INTO {$table} ";
        }
        $query .= '('.implode(', ', $data).') VALUES ('.implode(', ', array_fill(0, count($data), '?')).') '.$and;
        $stmt = $this->prepare($query);
        if ($single && $stmt) {
            $id = $this->insert($stmt, array_values($single));
            $this->close($stmt);

            return $id;
        }

        return $stmt;
    }

    public function update($table, $id, array $data, $and = '')
    {
        if (isset($this->prepared[$table])) {
            $data[] = $id;

            return $this->execute($table, $data);
        }
        $first = each($data);
        $single = (is_array($first['value'])) ? $first['value'] : false;
        if ($single) {
            $data = array_keys($single);
        }
        if (stripos($table, ' SET ') !== false) { // eg. 'table SET date = NOW(),'
            $query = "UPDATE {$table} ";
        } else {
            $query = "UPDATE {$table} SET ";
        }
        $query .= implode(' = ?, ', $data).' = ? WHERE '.$id.' = ? '.$and;
        $stmt = $this->prepare($query);
        if ($single && $stmt) {
            $affected = $this->update($stmt, $first['key'], array_values($single));
            $this->close($stmt);

            return $affected;
        }

        return $stmt;
    }

    public function upsert($table, $id, array $data)
    {
        if (isset($this->prepared[$table]['ref']) && $this->execute($table, $id)) {
            $data[] = $id;
            if ($row = $this->fetch($table)) {
                return ($this->execute($this->prepared[$table]['ref']['update'], $data)) ? array_shift($row) : false;
            } else {
                return $this->execute($this->prepared[$table]['ref']['insert'], $data);
            }
        }
        $first = each($data);
        $single = (is_array($first['value'])) ? $first['value'] : false;
        if ($single) {
            $data = array_keys($single);
        }
        if ($stmt = $this->prepare("SELECT {$id} FROM {$table} WHERE {$id} = ?", 'row')) {
            $this->prepared[$stmt]['ref']['update'] = $this->update($table, $id, $data);
            $this->prepared[$stmt]['ref']['insert'] = $this->insert($table, array_merge($data, array($id)));
        }
        if ($single && $stmt) {
            $id = $this->upsert($stmt, $first['key'], array_values($single));
            $this->close($stmt);

            return $id;
        }

        return $stmt;
    }

    public function prepare($query, $fetch = null)
    {
        $query = (is_array($query)) ? trim(implode("\n", $query)) : trim($query);
        $stmt = count(static::$logs[$this->id]) + 1;
        $start = microtime(true);
        $this->connection();
        $this->prepared[$stmt]['obj'] = $this->dbPrepare($query);
        static::$logs[$this->id][$stmt] = array(
            'sql' => $query,
            'count' => 0,
            'prepared' => microtime(true) - $start,
            'executed' => 0,
        );
        $this->prepared[$stmt]['params'] = substr_count($query, '?');
        $this->prepared[$stmt]['type'] = strtoupper(strtok($query, " \r\n\t"));
        if ($this->prepared[$stmt]['type'] == 'SELECT') {
            $this->prepared[$stmt]['style'] = $this->dbStyle(strtolower((string) $fetch));
        }
        if ($this->prepared[$stmt]['obj'] === false) {
            unset($this->prepared[$stmt]);
            if ($error = $this->dbPrepareError()) {
                static::$logs[$this->id][$stmt]['errors'][] = $error;
            }

            return false;
        }

        return $stmt;
    }

    public function execute($stmt, $values = null)
    {
        if (isset($this->prepared[$stmt])) {
            if (!is_array($values)) {
                $values = ($this->prepared[$stmt]['params'] == 1) ? array($values) : array();
            }
            $start = microtime(true);
            if ($this->dbExecute($this->prepared[$stmt]['obj'], array_values($values), $stmt)) {
                static::$logs[$this->id][$stmt]['executed'] += microtime(true) - $start;
                static::$logs[$this->id][$stmt]['count']++;
                switch ($this->prepared[$stmt]['type']) {
                    case 'SELECT':
                        return true;
                    break;
                    case 'INSERT':
                        return $this->dbInserted();
                    default:
                        return $this->dbAffected($this->prepared[$stmt]['obj']);
                }
            } elseif ($error = $this->dbExecuteError($this->prepared[$stmt]['obj'])) {
                static::$logs[$this->id][$stmt]['errors'][] = $error;
            }
        }

        return false;
    }

    public function fetch($stmt)
    {
        if (isset($this->prepared[$stmt]) && $this->prepared[$stmt]['type'] == 'SELECT') {
            return $this->dbFetch($this->prepared[$stmt]['obj'], $this->prepared[$stmt]['style'], $stmt);
        }

        return false;
    }

    public function close($stmt)
    {
        if (isset($this->prepared[$stmt])) {
            if (isset($this->prepared[$stmt]['ref'])) {
                foreach ($this->prepared[$stmt]['ref'] as $value) {
                    $this->close($value);
                }
            }
            $this->dbClose($this->prepared[$stmt]['obj'], $stmt);
            unset($this->prepared[$stmt]);
        }
    }

    public function debug($query, $values = array())
    {
        $query = (is_array($query)) ? trim(implode("\n", $query)) : trim($query);
        if (!is_array($values)) {
            $values = (!empty($values)) ? array($values) : array();
        }
        foreach ($values as $string) {
            if (false !== $replace = strpos($query, '?')) {
                $query = substr_replace($query, $this->dbEscape($string), $replace, 1);
            }
        }

        return $query;
    }

    public function log($value = null) // 'sql', 'count', 'prepared', 'executed', 'errors'?, 'average', 'total', 'time'
    {
        $log = (is_numeric($value)) ? static::$logs[$this->id][$value] : end(static::$logs[$this->id]);
        if (isset($log['errors'])) {
            $log['errors'] = array_count_values($log['errors']);
        }
        $log['average'] = ($log['count'] > 0) ? $log['executed'] / $log['count'] : 0;
        $log['total'] = $log['prepared'] + $log['executed'];
        $log['time'] = round($log['total'] * 1000).' ms';
        if ($log['count'] > 1) {
            $log['time'] .= ' (~'.round($log['average'] * 1000).' ea)';
        }
        if (is_null($value) || is_numeric($value)) {
            return $log;
        }

        return (isset($log[$value])) ? $log[$value] : null;
    }
}
