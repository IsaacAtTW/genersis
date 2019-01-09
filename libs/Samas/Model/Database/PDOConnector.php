<?php
namespace Samas\PHP7\Model\Database;

use \PDO, \PDOStatement;
use \Samas\PHP7\Kit\{AppKit, ArrayKit};
use \Samas\PHP7\Model\Database\DBSyntax;

/**
 * PDO agent
 */
class PDOConnector
{
    private $current_database;
    private $connection;
    private $statement;
    private $config;

    /**
     * __construct
     * @param  string  $dsn         data source name
     * @param  string  $user        use
     * @param  string  $password    password
     * @param  array   $ssl_config  ssl config
     * @return void
     */
    public function __construct(string $dsn, string $user, string $password, array $ssl_config)
    {
        $cmd = "SET time_zone='" . date('P') . "';";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_WARNING,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => $cmd
        ];
        if (!empty($ssl_config)) {
            foreach ($ssl_config as $key => $val) {
                if (defined("PDO::$key")) {
                    $options[constant("PDO::$key")] = $value;
                }
            }
        }
        $this->connection = new PDO(
            $dsn,
            $user,
            $password,
            $options
        );
        $this->config = [
            'dsn'   => $dsn,
            'login' => "$user/$password",
            'ssl'   => $ssl_config
        ];
    }

    /**
     * get native PDO
     * @return PDO
     */
    public function getConnectionPDO(): PDO
    {
        return $this->connection;
    }

    /**
     * switch DB
     * @param  string  $target  target DB
     * @return bool
     */
    public function useDB(string $target): bool
    {
        if (empty($target) || $target == $this->current_database) {
            return true;
        }
        $sql = "USE `$target`;";
        $this->recordSQL($sql);
        $result = $this->connection->exec($sql);
        if ($result !== false) {
            $this->current_database = $target;
            return true;
        }
        return false;
    }

    /**
     * return error message
     * @return string
     */
    public function debug(): string
    {
        if (!empty($this->statement->errorInfo()[2])) {
            return $this->statement->errorInfo()[2];
        }
        return $this->connection->errorInfo()[2];
    }

    /**
     * execute sql command
     * @param  string  $sql  sql
     * @return bool
     */
    public function execCmd(string $sql): bool
    {
        $this->recordSQL($sql);
        return $this->connection->exec($sql) !== false ? true : false;
    }

    /**
     * execute insert sql
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return int/bool false on failure, last insert id on success
     */
    public function execInsert(string $sql, array $params = [])
    {
        $is_success = $this->executePreparedSQL($sql, $params);
        return $is_success ? $this->connection->lastInsertId() : false;
    }

    /**
     * execute select sql
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return array/bool false on failure, result array on success
     */
    public function execSelect(string $sql, array $params = [])
    {
        $is_success = $this->executePreparedSQL($sql, $params);
        return $is_success ? $this->statement->fetchAll() : false;
    }

    /**
     * execute update sql
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return int/bool false on failure, affected rows count on success
     */
    public function execUpdate(string $sql, array $params = [])
    {
        $is_success = $this->executePreparedSQL($sql, $params);
        return $is_success ? $this->statement->rowCount() : false;
    }

    /**
     * execute delete sql
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return int/bool false on failure, affected rows count on success
     */
    public function execDelete(string $sql, array $params = [])
    {
        $is_success = $this->executePreparedSQL($sql, $params);
        return $is_success ? $this->statement->rowCount() : false;
    }

    /**
     * start transaction
     * @return bool
     */
    public function transaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * commit transaction content
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * roll back transaction
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * (Beta) execute sql query
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return array/int/bool  false on failure
     *                         last insert id with insert sql
     *                         affected rows count with update / delete sql
     *                         result array with select sql
     */
    public function execQuery(string $sql, array $params = [])
    {
        $is_success = $this->executePreparedSQL($sql, $params);

        $check_sql = strtolower($sql);
        if (strpos($check_sql, ' into ') !== false) {
            return $is_success ? $this->connection->lastInsertId() : false;
        } elseif (in_array(substr($check_sql, 0, 6), ['update', 'delete'])) {
            return $is_success ? $this->statement->rowCount() : false;
        } else {
            return $is_success !== false ? $this->statement->fetchAll() : false;
        }
    }

    /**
     * execute prepared sql flow
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return bool
     */
    private function executePreparedSQL(string $sql, array $params): bool
    {
        $this->recordSQL($sql, $params);
        $this->statement = $this->connection->prepare($sql);
        foreach ($params as $key => &$param) {
            $this->statement->bindParam($key, $param, $this->getParamType($param));
        }
        return $this->statement->execute();
    }

    /**
     * get relative data type constant of parameter
     * @param  mixed  $param  parameter
     * @return int
     */
    private function getParamType($param): int
    {
        $param_type = PDO::PARAM_STR;
        if (is_bool($param)) {
            $param_type = PDO::PARAM_BOOL;
        } elseif ($param === null) {
            $param_type = PDO::PARAM_NULL;
        } elseif (is_int($param) || is_float($param)) {
            $param_type = PDO::PARAM_INT;
        }
        return $param_type;
    }

    /**
     * record execute SQL
     * @param  string  $sql     sql
     * @param  array   $params  parameters to bind
     * @return void
     */
    private function recordSQL(string $sql, array $params = [])
    {
        if (AppKit::config('sql_collection')) {
            global $sql_collection;
            if (empty($params)) {
                return ArrayKit::set($sql_collection, [''], $sql);
            }
            $replace = [];
            foreach ($params as $key => $val) {
                $replace[$key] = is_string($val) ? "'$val'" : ($val instanceof DBSyntax ? $val->getVal() : $val);
            }
            $readable_sql = strtr($sql, $replace);
            return ArrayKit::set($sql_collection, [''], $readable_sql);
        }
    }
}
