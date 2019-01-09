<?php
namespace Samas\PHP7\Model\Database;

use \PDO, \RuntimeException;
use \Samas\PHP7\Kit\AppKit;
use \Samas\PHP7\Model\Database\DBSyntax;

/**
 * connections control center
 */
class DBAdapter
{
    private static $data_source_config = [];
    private static $connector_pool = [];
    private $connector;
    private $database;
    private $table;

    /**
     * __constrcut
     * @param  string  $data_source  data source name
     * @param  string  $database     database name
     * @param  string  $table        table name
     * @return void
     */
    public function __construct(string $data_source = '', string $database = '', string $table = '')
    {
        $this->initDataSourceDBConfig();
        if (empty($data_source)) {
            $data_source_list = array_keys(self::$data_source_config);
            $data_source = $data_source_list[0];
        } elseif (!array_key_exists($data_source, self::$data_source_config)) {
            throw new RuntimeException("data source 'database.$data_source' not defined!");
        }
        $this->connector = $this->getConnectorInstance($data_source);
        $this->database  = $database;
        $this->table     = $table;
    }

    /**
     * get native PDO from PDOConnector
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->connector->getConnectionPDO();
    }

    /**
     * return error message
     * @return string
     */
    public function debug(): string
    {
        return $this->connector->debug();
    }

    /**
     * get database list
     * @return array
     */
    public function getDatabaseList(): array
    {
        $sql = 'SHOW DATABASES;';
        $query_instance = $this->connector->execSelect($sql);
        if (!$query_instance) {
            return [];
        }
        $system_database = [
            'information_schema',
            'mysql',
            'performance_schema'
        ];
        return array_diff(array_column($query_instance, 'Database'), $system_database);
    }

    /**
     * get table list
     * @param  string  $target_database  specific database
     * @return array
     */
    public function getTableList(string $target_database = ''): array
    {
        $this->connector->useDB($this->database);
        $sql = 'SHOW TABLES;';
        if (!empty($target_database)) {
            $sql = "SHOW TABLES FROM `$target_database`;";
        }
        $query_instance = $this->connector->execSelect($sql);
        if (!$query_instance) {
            return [];
        }
        $table_list = [];
        foreach ($query_instance as $row) {
            $table_list = array_merge($table_list, array_values($row));
        }
        return $table_list;
    }

    /**
     * set table for clean DBAdapter
     * @param  string  $table  table to bind
     * @return string
     */
    public function setTable(string $table): string
    {
        return $this->table = $table;
    }

    /**
     * process DB command
     * @param  string  $sql  sql
     * @return bool
     */
    public function cmd(string $sql): bool
    {
        $this->connector->useDB($this->database);
        return $this->connector->execCmd($sql);
    }

    /**
     * insert data
     * @param  array  $data  data to insert, the array keys stand for table column name
     * @return int/bool  false on failure, last insert id on success
     */
    public function insert(array $data)
    {
        return $this->createSQL()->data($data)->insert();
    }

    /**
     * get count numbers by condition
     *     count(string $sql = '', array $params = [])
     *     count(array $and_list)
     *     count(string $type = ['and', 'or'], array $params)
     *     count(DBsyntax $obj, array $params = [])
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @return int/bool  false on failure, count numbers on success
     */
    public function count($where = '', array $params = [])
    {
        return $this->createSQL()->where($where, $params)->count();
    }

    /**
     * get one row data by condition
     *     find(string $sql = '', array $params = [])
     *     count(array $and_list)
     *     find(string $type = ['and', 'or'], array $params)
     *     find(DBsyntax $obj, array $params = [])
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @return array  empty array on failure, result array on success
     */
    public function find($where = '', array $params = []): array
    {
        $find_result = $this->createSQL()
                            ->where($where, $params)
                            ->length(1)
                            ->select();
        return $find_result[0] ?? [];
    }

    /**
     * get data by condition
     *     select(string $sql = '', array $params = [], int $offset = -1, int $length = -1)
     *     select(array $and_list, array $params = [], int $offset = -1, int $length = -1)
     *     select(string $type = ['and', 'or'], array $params, int $offset = -1, int $length = -1)
     *     select(DBsyntax $obj, array $params = [], int $offset = -1, int $length = -1)
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @param  int    $offset  offset
     * @param  int    $length  length
     * @return array/bool  false on failure, result array on success
     */
    public function select($where = '', array $params = [], int $offset = -1, int $length = -1)
    {
        return $this->createSQL()
                    ->where($where, $params)
                    ->offset($offset)
                    ->length($length)
                    ->select();
    }

    /**
     * update data by condition
     *     update(array $data, string $sql = '', array $params = [])
     *     update(array $data, array $and_list)
     *     update(array $data, string $type = ['and', 'or'], array $params)
     *     update(array $data, DBsyntax $obj, array $params = [])
     * @param  array  $data    data to insert, the array keys stand for table column name
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @return int/bool  false on failure, affected rows count on success
     */
    public function update(array $data, $where = '', array $params = [])
    {
        return $this->createSQL()
                    ->data($data)
                    ->where($where, $params)
                    ->update();
    }

    /**
     * delete data by condition
     *     delete(string $sql = '', array $params = [], int $limit = 0)
     *     delete(array $data, array $and_list)
     *     delete(string $type = ['and', 'or'], array $params, int $limit = 0)
     *     delete(DBsyntax $obj, array $params = [], int $limit = 0)
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @param  int    $limit         delete rows limit
     * @return int/bool  false on failure, affected rows count on success
     */
    public function delete($where = '', array $params = [], int $limit = 0)
    {
        return $this->createSQL()
                    ->where($where, $params)
                    ->length($limit)
                    ->delete();
    }

    /**
     * (Beta) process sql query
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @param  string  $index   use which column as index of result
     * @return array/int/bool  false on failure
     *                         last insert id with insert sql
     *                         affected rows count with update / delete sql
     *                         result array with select sql
     */
    public function query(string $sql, array $params = [], string $index = '')
    {
        $this->connector->useDB($this->database);
        $query_instance = $this->connector->execQuery($sql, $params);
        if (!empty($index) && isset($query_instance[0][$index])) {
            $query_instance = array_combine(
                array_column($query_instance, $index),
                $query_instance
            );
        }
        return $query_instance;
    }

    /**
     * start transaction
     * @return bool
     */
    public function transStart(): bool
    {
        $this->connector->useDB($this->database);
        return $this->connector->transaction();
    }

    /**
     * commit transaction content
     * @return bool
     */
    public function transCommit(): bool
    {
        $this->connector->useDB($this->database);
        return $this->connector->commit();
    }

    /**
     * roll back transaction
     * @return bool
     */
    public function transRollBack(): bool
    {
        $this->connector->useDB($this->database);
        return $this->connector->rollBack();
    }

    /**
     * start to create SQL, or in another word, get a new SQLBuilder object
     * @return SQLBuilder
     */
    public function createSQL(): SQLBuilder
    {
        $sql_builder = new SQLBuilder($this->connector, $this->database);
        if (!empty($this->table)) {
            $sql_builder->table($this->table);
        }
        return $sql_builder;
    }

    /**
     * initialize data source config
     * @return void
     */
    private function initDataSourceDBConfig()
    {
        if (empty(self::$data_source_config)) {
            if (empty(AppKit::config('database'))) {
                throw new RuntimeException("data source 'database' field missing!");
            }
            self::$data_source_config = AppKit::config('database');
        }
    }

    /**
     * get specific PDOConnector object, each object is isolated by data source name
     * @param  string  $data_source  data source name
     * @return PDOConnector
     */
    private function getConnectorInstance(string $data_source): PDOConnector
    {
        if (!array_key_exists($data_source, self::$connector_pool)) {
            $config = self::$data_source_config[$data_source];
            if (empty($config['dsn']) && empty($config['host'])) {
                throw new RuntimeException("data source 'database.$data_source' need dsn or host attribute!");
            }
            $dsn      = !empty($config['dsn']) ? $config['dsn'] : $this->getDSN($config);
            $user     = $config['user'] ?? '';
            $password = $config['password'] ?? '';
            $ssl      = !empty($config['ssl']) ? $config['ssl'] : [];
            self::$connector_pool[$data_source] = new PDOConnector($dsn, $user, $password, $ssl);
        }
        return self::$connector_pool[$data_source];
    }

    /**
     * compose dsn string
     * @param  array   $config  config array
     * @return string
     */
    private function getDSN(array $config): string
    {
        $attr = [];
        $attr[] = !empty($config['type']) ? "{$config['type']}:" : 'mysql:';
        $attr[] = "host={$config['host']};";
        $allow_key = ['port', 'dbname', 'charset', 'unix_socket'];
        foreach ($allow_key as $key) {
            if (!empty($config[$key]) || (isset($config[$key]) && $config[$key] === 0)) {
                $attr[] = "$key={$config[$key]};";
            }
        }
        return implode('', $attr);
    }
}
