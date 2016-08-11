<?php

namespace BootPress\Database;

class Driver
{
    protected $id = 0;
    protected static $drivers = array();
    protected static $logs = array();
    protected $connection;
    private $construct;

    /**
     * Either implement an established PDO instance, or set up a lazy database connection that we only connect to if and when you actually use it.  Every $dsn string with a '**dbname**' is saved in a databases.yml file so that you only need to spell everything out once, and then just refer to the 'dbname' in your code.
     * 
     * @param string|object $dsn Either a PDO Instance, a DSN string that contains the information required to connect to the database, or the 'dbname' saved in the databases.yml file.  Some examples are:
     * 
     * - [MySQL](http://php.net/manual/en/ref.pdo-mysql.connection.php "mysql")
     *     - mysql:host=[name];port=[number];dbname=[database];unix_socket=[instead of host or port];charset=[utf-8]
     *     - MySQL v. <= 4.1 not supported in PHP v. >= 5.4.0
     *     - charset is ignored in PHP v. < 5.3.6
     * - [SQLite](http://php.net/manual/en/ref.pdo-sqlite.connection.php "sqlite")
     *     - sqlite:[filepath OR ':memory:']
     * - [PostgreSQL](http://php.net/manual/en/ref.pdo-pgsql.connection.php "pgsql")
     *     - pgsql:host=[name];port=[number];dbname=[database];user=[name];password=[secret]
     * - [Oracle](http://php.net/manual/en/ref.pdo-oci.connection.php "oci")
     *     - oci:dbname=[database];charset=[utf-8]
     * - [MSSQL on Windows](http://php.net/manual/en/ref.pdo-sqlsrv.connection.php "sqlsrv")
     *     - sqlsrv:Server=[name,port];Database=[name] ... and a bunch of other options
     * - [MSSQL on Linux / Unix](http://php.net/manual/en/ref.pdo-dblib.connection.php "dblib")
     *     - sybase:host=[name];dbname=[database];charset=[utf-8];appname=[application];secure=[currently unused]
     * - [Sybase](http://php.net/manual/en/ref.pdo-dblib.connection.php "sybase")
     *     - sybase:host=[name];dbname=[database];charset=[utf-8];appname=[application];secure=[currently unused]
     * - [Microsoft SQL Server](http://php.net/manual/en/ref.pdo-dblib.connection.php "mssql")
     *     - mssql:host=[name];dbname=[database];charset=[utf-8];appname=[application];secure=[currently unused]
     * - [Cubrid](http://php.net/manual/en/ref.pdo-cubrid.connection.php "cubrid")
     *     - cubrid:host=[name];port=[number];dbname=[database];
     * @param string|null $username The user name for the DSN string. This parameter is optional for some PDO drivers.
     * @param string|null $password The password for the DSN string. This parameter is optional for some PDO drivers.
     * @param array       $options  An ``array('key'=>'value', ...)`` of driver-specific connection options.
     * @param array       $exec     Queries you would like to execute upon connecting to the database.
     * 
     * @see http://php.net/manual/en/pdo.construct.php
     */
    public function __construct($dsn, $username = null, $password = null, array $options = array(), array $exec = array())
    {
        if ($dsn instanceof \PDO) {
            $this->connection = $dsn;
            $this->driver('PDO');
        } else {
            $driver = strstr($dsn, ':', true);
            if (class_exists('BootPress\Page\Component') && class_exists('Symfony\Component\Yaml\Yaml')) {
                $page = \BootPress\Page\Component::html();
                $databases = $page->file('databases.yml');
                $yaml = (is_file($databases)) ? \Symfony\Component\Yaml\Yaml::parse(file_get_contents($databases)) : array();
                if ($driver === false) { // Has no semicolon, and so is assumed to be a dbname in the databases.yml
                    if (isset($yaml[$dsn]['dsn'])) {
                        extract($yaml[$dsn]);
                    } else {
                        throw new \LogicException('The "'.$dsn.'" database is undefined.');
                    }
                } elseif (preg_match('/dbname=([a-z0-9._\-]+);?/i', $dsn, $matches)) { // Save to the databases.yml
                    $dbname = $matches[1];
                    $config = array(
                        'dsn' => $dsn,
                        'username' => $username,
                        'password' => $password,
                        'options' => $options,
                        'exec' => $exec,
                    );
                    if (!isset($yaml[$dbname]) || $yaml[$dbname] != $config) {
                        $yaml[$dbname] = $config;
                        ksort($yaml);
                        $yaml = \Symfony\Component\Yaml\Yaml::dump($yaml, 3);
                        file_put_contents($databases, $yaml);
                    }
                }
            }
            $this->driver($driver);
            $this->construct = array($dsn, $username, $password, $options, $exec);
        }
    }

    /**
     * @return int The current database's id.
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param string $name Pass a value to set the database driver's name.
     * 
     * @return string|null The current database driver's name.
     */
    public function driver($name = null)
    {
        if (empty($this->id)) {
            $this->id = count(static::$drivers) + 1;
            static::$logs[$this->id] = array();
        }
        if (is_string($name)) {
            static::$drivers[$this->id] = $name;
        }

        return (isset(static::$drivers[$this->id])) ? static::$drivers[$this->id] : null;
    }

    /**
     * @param int $id To only return the data for a specific database connection.
     * 
     * @return array Debug, error, and profile data for all of your database queries.
     */
    public static function logs($id = null)
    {
        $logs = array();
        $ids = (is_null($id)) ? array_keys(static::$logs) : array($id);
        foreach ($ids as $key) {
            if (isset(static::$logs[$key])) {
                $logs[$key] = array(
                    'driver' => (isset(static::$drivers[$key])) ? static::$drivers[$key] : null,
                    'duration' => 0,
                    'queries' => array(),
                );
                foreach (static::$logs[$key] as $log) { // 'sql', 'count', 'errors'?, 'duration'
                    $log['duration'] = $log['prepared'] + $log['executed'];
                    unset($log['prepared'], $log['executed']);
                    if (isset($log['errors'])) {
                        $log['errors'] = array_count_values($log['errors']);
                    }
                    $logs[$key]['duration'] += $log['duration'];
                    $logs[$key]['queries'][] = $log;
                }
            }
        }

        return (is_null($id)) ? $logs : array_shift($logs);
    }

    /**
     * @return array All of the errors generated from all of your database connections.
     */
    public static function errors()
    {
        $errors = self::logs();
        foreach ($errors as $id => $db) {
            foreach ($db['queries'] as $num => $log) {
                if (isset($log['errors'])) {
                    $errors[$id]['queries'][$num] = array_intersect_key($log, array(
                        'sql' => '',
                        'errors' => '',
                    ));
                } else {
                    unset($errors[$id]['queries'][$num]);
                }
            }
            if (empty($errors[$id]['queries'])) {
                unset($errors[$id]);
            } else {
                unset($errors[$id]['total']);
            }
        }

        return $errors;
    }

    /**
     * @return object The database connection.  This is how we create lazy connections.
     */
    public function connection()
    {
        if (is_null($this->connection) && !is_null($this->construct)) {
            list($dsn, $username, $password, $options, $exec) = $this->construct;
            try {
                $this->connection = new \PDO($dsn, $username, $password, $options);
            } catch (\PDOException $e) {
                throw new \Exception($e->getMessage());
            }
            foreach ($exec as $sql) {
                $this->connection->exec($sql);
            }
        }

        return $this->connection;
    }

    protected function dbPrepare($query)
    {
        return $this->connection->prepare($query); // returns (mixed) $stmt object or false
    }

    protected function dbPrepareError()
    {
        return ($info = $this->connection->errorInfo()) ? "Code: {$info[0]} Error: ({$info[1]}) {$info[2]}" : false; // returns (string) error or false
    }

    protected function dbExecute($stmt, array $values, $reference)
    {
        return $stmt->execute($values); // returns (bool) true or false
    }

    protected function dbExecuteError($stmt)
    {
        return ($info = $stmt->errorInfo()) ? "Code: {$info[0]} Error: ({$info[1]}) {$info[2]}" : false; // returns (string) error or false
    }

    protected function dbStyle($fetch)
    {
        switch ($fetch) {
            case 'obj':
                return \PDO::FETCH_OBJ;
            break;
            case 'assoc':
                return \PDO::FETCH_ASSOC;
            break;
            case 'named':
                return \PDO::FETCH_NAMED;
            break;
            case 'both':
                return \PDO::FETCH_BOTH;
            break;
            default:
                return \PDO::FETCH_NUM;
            break;
        }
    }

    protected function dbFetch($stmt, $style, $reference)
    {
        return $stmt->fetch($style); // returns (mixed) $style or false
    }

    protected function dbInserted()
    {
        return (int) $this->connection->lastInsertId(); // returns (int) last inserted row id or sequence value
    }

    protected function dbAffected($stmt)
    {
        return $stmt->rowCount(); // returns (int) number of rows affected by last $stmt
    }

    protected function dbClose($stmt, $reference)
    {
        return $stmt->closeCursor(); // returns (bool) true or false
    }

    protected function dbEscape($string)
    {
        return $this->connection->quote($string);
    }
}
