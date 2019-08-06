<?php

namespace Tablebuilder;

/**
 * Database class.
 */

/**
 * Safe class for working with MySQL Databases using PDO.
 * 
 *  $a = Database::db(); //creates a Database instance
 *  $objects = $a
 *            ->table('test')
 *            ->select('id', 'name')
 *            ->where('id', 100, '>')
 *            ->order('name')
 *            ->limit(100)
 *            ->fetch();
 * 
 * @author: Shaian Maksim
 */
class Database 
{   
    /**
     * A connection singletone instance
     * @var Database 
     */
    private static $connection;
    
    
    /** BEGIN Data for a query building */
    
    /**
     * Limits number of results.
     * @var string
     */
    private $limit = '';
    
    /**
     * Main MySQL request (e.g. SELECT, TRANCUATE).
     * @var string 
     */
    private $main;
    
    /**
     * ORDER query part.
     * @var string
     */
    private $order = '';
    
    /**
     * Resulting query.
     * @var string
     */
    private $query = '';
    
    /**
     * Table name.
     * @var string
     */
    private $table;
    
    /**
     * WHERE query part.
     * @var string
     */
    private $where = '';
    
    /** END Data for a query building */
    
    /**
     * PDO instance.
     * @var PDO 
     */
    private $pdo;
    
    
    /** BEGIN Properties for DB connection*/
    
    /**
     * Database charset.
     * @var type string
     */
    private $charset = 'utf8';
    
    /**
     * Database name.
     * @var string
     */
    private $dbname;
    
    /**
     * Database IP or domain address.
     * @var string
     */
    private $host;
    
    /**
     * Database user login.
     * @var string
     */
    private $login;
    
    /**
     * Database user password.
     * @var string
     */
    private $pass;
    
    /** END Properties for DB connection*/
    
    
    /**
     * Closed for singletone pattern.
     */
    private function __clone() {}
    
    /**
     * Private, because the class uses singletone pattern.
     * 
     * Use Database::db() to get an instance.
     */
    private function __construct() 
    {
        $this->setSettings();
        $dsn = 'mysql:dbname=' . $this->dbname . ';host=' . $this->host .
                ';charset=' . $this->charset;
        try {
            $this->pdo = new \PDO($dsn, $this->login, $this->pass);
        } catch (\PDOException $e) {
            echo 'Connection failure: ' . $e->getMessage();
        }
    }
    
    /**
     * This method is used by fetch() method to build a result MySQL-query
     * @return string
     */
    private function buildQuery(): string
    {
        if ($this->main == 'TRUNCATE') {
            return $this->query;
        } elseif ($this->main == 'SELECT') {
            return $this->query . $this->where . $this->order . $this->limit;
        } else {
            return $this->query . $this->where;
        }
    }
    
    /**
     * Checks the existance of another main method like select, update.
     * @param string $main
     * @return void
     * @throws \Exception
     */
    private function checkQuery(string $main): void
    {
        try {
            if (isset($this->main)) {
                throw new \Exception($this->main . ' is already declared.');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        $this->main = $main;
    }
    
    /**
     * Checks the table declaration.
     * @return void
     * @throws \Exception
     */
    private function checkTable(): void
    {
        try {
            if (empty($this->table)) {
                throw new \Exception('Table is not declared. Use table(name).');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * Checks SQL Errors in PDO executions.
     * @param PDOStatement $pdo
     * @return void
     * @throws \Exception
     */
    private function checkSqlError(\PDOStatement $pdo): void
    {
        $error = $pdo->errorInfo();
        try {
            if (isset($error[1])) {
                throw new \Exception('#' . $error[1] . ': ' . $error[2]);
            }
        } catch (\Exception $ex) {
            $t = $ex->getTrace();
            echo $t[0]['file'] . ', line ' . $t[0]['line'] . ': ' . $ex->getMessage();
        }
    }
    
    /**
     * Builds columns SQL-query from a list of columns.
     * @param string $columns
     * @return string
     */
    private static function formColumnList(string $columns): string
    {
        $cols = '';
        $columns = explode(',', $columns);
        for ($i = 0; $i < count($columns); $i++) {
            $cols .= '`' . trim($columns[$i]) . '`, ';
        }
        return substr($cols, 0, -2);
    }
    
    /**
     * Determination of receiving DB connection settings.
     * @todo !!!CHANGE THIS METHOD TO SET MYSQL CONNECTION SETTINGS!!!
     * @return void
     */
    private function setSettings(): void
    {
        require_once './vendor/vanguardkras/tablebuilder/properties.php';
        $this->dbname = DB_NAME;
        $this->host = DB_HOST;
        $this->login = DB_LOGIN;
        $this->pass = DB_PASS;
    }
    
    /**
     * Returns a connection instance to subsequently build queries
     * and fetch results. 
     * Connects to a database only once.
     * @return \Database
     */
    public static function db(): Database
    {
        if (empty(self::$connection)) {
            self::$connection = new self;
        }
        return self::$connection;
    }
    
    /**
     * Delete from a table.
     * Usually, it is better to use where() to delete particular rows.
     * @return \Database
     */
    public function delete(): Database
    {
        $this->checkTable();
        $this->checkQuery('DELETE');
        $query = 'DELETE FROM ' . $this->table . ' ';
        $this->query = $query;
        return $this;
    }
    
    /**
     * Returns information about table's parameters.
     * @return \Database
     */
    public function describe(): Database
    {
        $this->checkTable();
        $this->checkQuery('DESCRIBE');
        $query = 'DESCRIBE ' . $this->table;
        $this->query = $query;
        return $this;
    }
    
    /**
     * Selects unique values from one column.
     * @param string $column
     * @return \Database
     */
    public function selectDistinct(string $column): Database
    {
        $this->checkTable();
        $this->checkQuery('SELECT');
        $query = 'SELECT DISTINCT(`' . $column . '`) ';
        $query .= ' FROM ' . $this->table . ' ';
        $this->query = $query;
        return $this;
    }
    
    /**
     * Prepares to the next request.
     * @return void
     */
    public function end(): void
    {
        $this->limit = '';
        unset($this->main);
        $this->order = '';
        unset($this->query);
        unset($this->table);
        $this->where = '';
    }
    
    /**
     * Sends an SQL request.
     * @param string $query
     * @return int
     */
    public function execute(string $query): int
    {
        return $this->pdo->exec($query);
    }
    
    /**
     * Methos for a resulting query fetching.
     * 
     * Run it as the last method to get the result.
     * @param int $one Set to one to receive only one row.
     * @param mixed $end Set to false to use the built query again.
     * @param mixed $column Set to PDO::FETCH_COLUMN or PDO::FETCH_NUM to get one column,
     * or numeric result respectively. 
     * 
     * Default: PDO::FETCH_ASSOC.
     * @return type
     */
    public function fetch(int $one = 0, $end = true, $column = \PDO::FETCH_ASSOC)
    {
        $query = $this->buildQuery();
        //print_r($query);
        $pdo = $this->pdo->prepare($query);
        $pdo->execute();
        if ($one === 0) {
            $res = $pdo->fetchAll($column);
        } else {
            $res = $pdo->fetch($column);
        }
        if ($end) {
            $this->end();
        }
        $this->checkSqlError($pdo);
        return $res;
    }
    
    /**
     * Inserts a new row to a table of the chosen database.
     * @param string $columns List of columns as a string with column names 
     * devided by a comma.
     * @param array $values An array of values in the same order as columns
     * @return \Database
     * @throws \Exception
     */
    public function insert(string $columns, array $values): Database
    {
        $this->checkTable();
        try {
            if (count(explode(',', $columns)) != count($values)) {
                throw new \Exception('Columns and values numbers do not match.');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        $this->checkQuery('INSERT');
        $query = 'INSERT INTO ' . $this->table . ' (';
        $query .= self::formColumnList($columns) . ') VALUES(';
        for($i = 0; $i < count($values); $i++) {
            $query .= '\'' . $values[$i] . '\', ';
        }
        $query = substr($query, 0, -2) . ') ';
        $this->query = $query;
        return $this;
    }
    
    /**
     * Limits the number of receiving results. Usually used with select().
     * @param int $limit Maximum number.
     * @param int $offset Starts selecting from this number.
     * @return \Database
     * @throws \Exception
     */
    public function limit(int $limit, int $offset = 0): Database
    {
        $this->checkTable();
        try {
            if ($this->limit != '') {
                throw new \Exception('limit() cannot be used more than once');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        $query = 'LIMIT ' . $limit . ' OFFSET ' . $offset . ' ';
        $this->limit = $query;
        return $this;
    }
    
    /**
     * Sorts the resulting array. Usually used with select().
     * @param string $column Name of sorting column.
     * @param string $desc Use any parameter to sort it in descending way.
     * @return \Database
     * @throws \Exception
     */
    public function order(string $column, $desc = ''): Database
    {
        $this->checkTable();
        try {
            if ($this->order != '') {
                throw new \Exception('order() cannot be used more than once');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        if($desc != '') {
            $desc = ' DESC';
        }
        $query = 'ORDER BY `' . trim($column) . '`' . $desc . ' ';
        $this->order = $query;
        return $this;
    }
    
    /**
     * Selects particular data from a table.
     * @param string $columns List of columns as a string with column names 
     * devided by a comma. 
     * 
     * If you use 'COUNT', it will return the rows number.
     * @return \Database
     */
    public function select(string $columns = ''): Database
    {
        $this->checkTable();
        $this->checkQuery('SELECT');
        $query = 'SELECT ';
        if ($columns === '') {
            $query .= '* ';
        } elseif ($columns === 'COUNT') {
            $query .= 'COUNT(*) ';
        } else {
            $query .= self::formColumnList($columns);
        }
        $query .= ' FROM ' . $this->table . ' ';
        $this->query = $query;
        return $this;
    }
    
    /**
     * Sets custom main request query.
     * 
     * It is possible to add additional selectors like where() and order();
     * 
     * @param string $query
     * @param bool $final Finalises a current query.
     * @return \Database
     */
    public function setQuery(string $query, $final = true): Database
    {
        if (isset($this->query)) {
            $this->query .= $query;
        } else {
            $this->query = $query;
        }
        if ($final) {
            $this->query .= ' FROM ' . $this->table . ' ';   
        }
        if (stristr($query, 'SELECT')) {
            $this->main = 'SELECT';
        }
        return $this;
    }
    
    /**
     * Sets an active table.
     * @param string $table Name of the table.
     * @return \Database
     */
    public function table(string $table): Database
    {
        $this->table = '`' . $table . '` ';
        return $this;
    }
    
    /**
     * Erases all data from a table chosen by table() method.
     * @return \Database
     */
    public function truncate(): Database
    {
        $this->checkTable();
        $this->checkQuery('TRUNCATE');
        $query = 'TRUNCATE TABLE ' . $this->table . ' ';
        $this->query = $query;
        return $this;
    }
    
    /**
     * Changes data of particular cells in the table.
     * @param string $columns List of columns as a string with column names 
     * devided by a comma.
     * @param array $values An array of values in the same order as columns.
     * @return \Database
     * @throws \Exception
     */
    public function update(string $columns, array $values): Database
    {
        $this->checkTable();
        $columns = explode(',', $columns);
        try {
            if (count($columns) != count($values)) {
                throw new \Exception('Columns and values numbers do not match.');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        $this->checkQuery('UPDATE');
        $this->checkTable();
        $query = 'UPDATE ' . $this->table . ' SET ';
        for ($i = 0; $i < count($columns); $i++) {
            $query .= '`' . trim($columns[$i]) . '`=\'' . $values[$i] . '\', ';
        }
        $query = substr($query, 0, -2);
        $this->query = $query . ' ';
        return $this;
    }
    
    /**
     * Filters required results.
     * @param string $col The name of the column.
     * @param mixed $val The column's data.
     * @param string $compare Compares $col and $val. 
     * 
     * Possible variants: '>', '<', '='.
     *  
     * Default: '='.
     * 
     * @param string $op If used several where(), 
     * you can choose other logic operators.
     * 
     * Examples: OR, AND.
     * 
     * Default: 'AND'.
     * 
     * @return \Database
     */
    public function where(string $col, $val, string $compare = '', $op = 'AND'): Database
    {
        $this->checkTable();
        if ($this->where != '') {
            $query = $op . ' ';
        } else {
            $query = 'WHERE ';
        }
        if ($compare == '') {
            $compare = '=';
        }
        $op = mb_strtoupper($op);
        $query .= '`' . $col . '` ' . $compare . ' \'' . $val . '\' ';
        $this->where .= $query;
        return $this;
    }
}
