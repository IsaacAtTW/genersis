<?php
namespace Samas\PHP7\Model\Database;

use \RuntimeException;
use \Samas\PHP7\Kit\StrKit;

/**
 * ORM(Object-Relational-Mapping) SQL builder
 */
class SQLBuilder
{
    private $connector    = null;
    private $union_cnt    = 0;
    private $sql_segments = [];
    private $database;

    /**
     * __construct
     * @param  PDOConnector  &$connector  PDOConnector object
     * @param  string        $database    database name
     */
    public function __construct(PDOConnector &$connector, string $database = '')
    {
        $this->connector = $connector;
        $this->database  = $database;
    }

    /*******************************************************************************************************************
     * condition functions
     ******************************************************************************************************************/
    /**
     * set insert or update data
     * @param  array   $data  data to insert or update
     *                        the array keys stand for table column name
     * @return SQLBuilder
     */
    public function data(array $data): self
    {
        if (!isset($this->sql_segments['data'])) {
            $this->sql_segments['data'] = [];
        }
        $this->sql_segments['data'] += $data;
        return $this;
    }

    /**
     * set select field
     * @param  string/array  $field  field
     * @return SQLBuilder
     */
    public function field($field): self
    {
        if (!isset($this->sql_segments['field'])) {
            $this->sql_segments['field'] = [];
        }
        if (!is_array($field)) {
            $field = [$field];
        }
        foreach ($field as $instance_field) {
            if (is_array($instance_field)) {
                foreach ($instance_field as $column => $json_field) {
                    $field_expression = StrKit::getDBJSONPath($json_field);
                    $this->sql_segments['field'][] = StrKit::getDBTargetStr($column) . "->>'$field_expression'";
                }
            } else {
                $this->sql_segments['field'][] = $instance_field;
            }
        }
        return $this;
    }

    /**
     * set table field
     * @param  string  $name   table name
     * @param  string  $alias  table alias
     * @return SQLBuilder
     */
    public function table(string $name, string $alias = ''): self
    {
        $this->sql_segments['table'] = [
            'name'  => $name,
            'alias' => $alias
        ];
        return $this;
    }

    /**
     * set "JOIN" data
     * @param  string  $table  table name
     * @param  string  $on     join condition
     * @param  string  $alias  table alias
     * @param  string  $type   join type
     *                         allow values: ['inner' | 'cross' | 'left' | 'right' | 'natural' |
     *                                        'natural left' | 'natural right']
     * @return SQLBuilder
     */
    public function join(string $table, string $on, string $alias = '', string $type = 'inner'): self
    {
        $this->sql_segments['join'][] = [
            'type'  => $type,
            'table' => $table,
            'on'    => $on,
            'alias' => $alias
        ];
        return $this;
    }

    /**
     * set where condition
     *     where(string $sql, array $params = [])
     *     where(array $and_list)
     *     where(string $type = ['and', 'or'], array $params)
     *     where(DBsyntax $obj, array $params = [])
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @return SQLBuilder
     */
    public function where($where, array $params = []): self
    {
        if (is_array($where)) {
            $params = $where;
            $where  = 'and';
        }
        if (is_string($where) && in_array(strtolower($where), ['and', 'or'])) {
            $list   = [];
            $new_params = [];
            foreach ($params as $column => $value) {
                if (is_array($value)) {
                    foreach ($value as $json_key => $json_value) {
                        $field_expression = StrKit::getDBJSONPath($json_key);
                        $key = StrKit::getDBTargetStr($column) . "->'$field_expression'";
                        if (is_object($json_value) && $json_value instanceof DBSyntax) {
                            $list[] = "$key " . $json_value->getVal();
                        } else {
                            $bind_key = str_replace('.', '_', ":$column.$json_key");
                            $list[] = "$key = $bind_key";
                            $new_params[$bind_key] = $json_value;
                        }
                    }
                } elseif (is_object($value) && $value instanceof DBSyntax) {
                    $list[] = "$column " . $value->getVal();
                } else {
                    $list[] = "$column = :$column";
                    $new_params[":$column"] = $value;
                }
            }
            $this->sql_segments['where']        = implode(' ' . strtoupper($where) . ' ', $list);
            $this->sql_segments['where_params'] = $new_params;
        } else {
            $this->sql_segments['where']        = (is_object($where) && $where instanceof DBSyntax) ?
                                                  $where->getVal() :
                                                  $where;
            $this->sql_segments['where_params'] = $params;
        }
        return $this;
    }

    /**
     * set "GROUP BY" data
     * @param  string/array  $group  fields to group by
     * @return SQLBuilder
     */
    public function group($group): self
    {
        if (!isset($this->sql_segments['group'])) {
            $this->sql_segments['group'] = [];
        }
        if (!is_array($group)) {
            $group  = [$group];
        }
        foreach ($group as $instance_group) {
            $this->sql_segments['group'][] = $instance_group;
        }
        return $this;
    }

    /**
     * set "HAVING" condition
     * @param  string  $having  having expression
     * @return SQLBuilder
     */
    public function having(string $having): self
    {
        $this->sql_segments['having'] = $having;
        return $this;
    }

    /**
     * set "ORDER BY" condition
     * @param  string/array  $order  sort field, if pass a array,
     *                               the array keys stand for field, and the values stand for rule
     * @param  string        $rule   sort rule
     *                               allow values: ['ASC', 'DESC']
     *                               this parameter will be ignore when $order is an array
     * @return SQLBuilder
     */
    public function order($order, string $rule = 'ASC'): self
    {
        if (!is_array($order)) {
            $order = [$order => $rule];
        }
        foreach ($order as $field => $rule) {
            $this->sql_segments['order'][$field] = $rule;
        }
        return $this;
    }

    /**
     * set "OFFSET" condition
     * @param  int  $offset  row numbers to skip
     * @return SQLBuilder
     */
    public function offset(int $offset): self
    {
        $this->sql_segments['offset'] = $offset;
        return $this;
    }

    /**
     * set "LIMIT" condition
     * @param  int  $length  limit row numbers
     * @return SQLBuilder
     */
    public function length(int $length): self
    {
        $this->sql_segments['length'] = $length;
        return $this;
    }

    /*******************************************************************************************************************
     * execution functions
     ******************************************************************************************************************/
    /**
     * process count action
     * @return int/bool  false on failure, count numbers on success
     */
    public function count()
    {
        $this->sql_segments['field'] = ['COUNT(1) AS cnt'];
        $result = $this->buildSQL('select');
        $this->connector->useDB($this->database);
        $query_instance = $this->connector->execSelect($result['sql'], $result['params']);
        if (count($query_instance)) {
            return $query_instance[0]['cnt'];
        }
        return $result === false ? false : 0;
    }

    /**
     * process select action
     * @return array/bool  false on failure, result array on success
     */
    public function select()
    {
        $result = $this->buildSQL('select');
        $this->connector->useDB($this->database);
        $query_instance = $this->connector->execSelect($result['sql'], $result['params']);
        return $query_instance === false ? false : $query_instance;
    }

    /**
     * process union action
     * @param  string/SQLBuilder  $target  expression to union
     * @param  array              $params  parameters to bind with where condition
     *                                     this parameter will be ignored when $target is a SQLBuilder object
     * @return array/bool  false on failure, result array on success
     */
    public function union($target, array $params = [])
    {
        if (is_object($traget) && $traget instanceof SQLBuilder) {
            $union_info = $target->buildSQL('union');
        } else {
            $union_info = [
                'sql'    => $target,
                'params' => $params
            ];
        }
        $result = $this->buildSQL('select');
        $sql_segments = "({$result['sql']}) UNION ({$union_info['sql']})";
        $params = array_merge($result['params'], $union_info['params']);
        $this->connector->useDB($this->database);
        return $this->connector->execSelect($result['sql'], $result['params']);
    }

    /**
     * process insert action
     * @return int/bool  false on failure, last insert id on success
     */
    public function insert()
    {
        $result = $this->buildSQL('insert');
        $this->connector->useDB($this->database);
        return $this->connector->execInsert($result['sql'], $result['params']);
    }

    /**
     * process update action
     * @return int/bool  false on failure, affected rows count on success
     */
    public function update()
    {
        $result = $this->buildSQL('update');
        $this->connector->useDB($this->database);
        return $this->connector->execUpdate($result['sql'], $result['params']);
    }

    /**
     * process delete action
     * @return int/bool  false on failure, affected rows count on success
     */
    public function delete()
    {
        $result = $this->buildSQL('delete');
        $this->connector->useDB($this->database);
        return $this->connector->execDelete($result['sql'], $result['params']);
    }

    /*******************************************************************************************************************
     * operation funcitons
     ******************************************************************************************************************/
    /**
     * reset all data
     * @return SQLBuilder
     */
    public function reset(): self
    {
        $this->union_cnt = 0;
        $this->sql_segments = [];
        return $this;
    }

    /**
     * build SQL sentence and binding parameters array
     * @param  string  $action  action type
     *                          allow values: ['insert', 'union', 'select', 'update', 'delete']
     * @return array [
     *     'sql'     => string,
     *     'paramse' => array
     * ]
     */
    public function buildSQL(string $action): array
    {
        $error = $this->validateBuild($action);
        if (!empty($error)) {
            throw new RuntimeException($error);
        }

        $cmd_segments = [];
        $params       = [];

        switch ($action) {
            case 'insert':
                $insert_data    = $this->buildInsertData($params);
                $cmd_segments[] = 'INSERT INTO ' . $this->buildTable();
                $cmd_segments[] = "({$insert_data['insert_field']})";
                $cmd_segments[] = 'VALUES';
                $cmd_segments[] = "({$insert_data['insert_statement']})";
                break;
            case 'union':
                $this->union_cnt++;
                // continue with 'select' logic
            case 'select':
                $cmd_segments[] = 'SELECT ' . $this->buildField();
                $cmd_segments[] = 'FROM ' . $this->buildTable();
                $cmd_segments[] = $this->buildTableAlias();
                $cmd_segments[] = $this->buildJoin();
                $cmd_segments[] = $this->buildWhere($params);
                $cmd_segments[] = $this->buildGroup();
                $cmd_segments[] = $this->buildHaving();
                $cmd_segments[] = $this->buildOrder();
                $cmd_segments[] = $this->buildLimit();
                $cmd_segments[] = $this->buildOffset();
                break;
            case 'update':
                $cmd_segments[] = 'UPDATE ' . $this->buildTable();
                $cmd_segments[] = 'SET ' . $this->buildUpdateData($params);
                $cmd_segments[] = $this->buildWhere($params);
                $cmd_segments[] = $this->buildOrder();
                $cmd_segments[] = $this->buildLimit();
                break;
            case 'delete':
                $cmd_segments[] = 'DELETE FROM ' . $this->buildTable();
                $cmd_segments[] = $this->buildWhere($params);
                $cmd_segments[] = $this->buildOrder();
                $cmd_segments[] = $this->buildLimit();
                break;
        }
        $cmd_segments = array_filter($cmd_segments, function ($var) {
            return $var != '';
        });
        return ['sql' => implode(' ', $cmd_segments), 'params' => $params];
    }

    /*******************************************************************************************************************
     * building functions
     ******************************************************************************************************************/
    /**
     * validate data before building
     * @param  string  $action  action type
     *                          allow values: ['insert', 'union', 'select', 'update', 'delete']
     * @return string  empty string with all valid situation
     */
    private function validateBuild(string $action): string
    {
        if (empty($this->sql_segments['table']) || empty($this->sql_segments['table']['name'])) {
            return 'Empty target when executing query in SQLBuilder';
        }
        $error = '';
        switch ($action) {
            case 'insert':
                if (empty($this->sql_segments['data'])) {
                    $error = 'Empty data when executing insert query in SQLBuilder';
                }
                break;
            case 'union':
            case 'select':
                break;
            case 'update':
                if (empty($this->sql_segments['data'])) {
                    $error = 'Empty data when executing update query in SQLBuilder';
                }
                break;
            case 'delete':
                break;
        }
        return $error;
    }

    /**
     * build select field string
     * @return string
     */
    private function buildField(): string
    {
        if (empty($this->sql_segments['field'])) {
            return '*';
        }
        $field_list = [];
        foreach ($this->sql_segments['field'] as $field) {
            $field_list[] = StrKit::getDBTargetStr($field);
        }
        return implode(', ', $field_list);
    }

    /**
     * build "FROM" string
     * @return string
     */
    private function buildTable(): string
    {
        return StrKit::getDBTargetStr($this->sql_segments['table']['name']);
    }

    /**
     * build "AS" string of "FROM"
     * @return string
     */
    private function buildTableAlias(): string
    {
        return $this->sql_segments['table']['alias'] ?
               'AS ' . StrKit::getDBTargetStr($this->sql_segments['table']['alias']) :
               '';
    }

    /**
     * build insert data string
     * @return string
     */
    /**
     * build insert data string
     * @param  array   &$params  array for binding parameters to store
     * @return string
     */
    private function buildInsertData(array &$params): array
    {
        $insert_field_pool = [];
        $insert_statement_pool = [];
        foreach ($this->sql_segments['data'] as $column => $value) {
            $insert_field_pool[] = StrKit::getDBTargetStr($column);
            if (is_object($value) && $value instanceof DBSyntax) {
                $insert_statement_pool[] = $value->getVal();
            } else {
                $insert_statement_pool[] = ":ins_$column";
                $params[":ins_$column"] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
        }
        return [
            'insert_field'     => implode(', ', $insert_field_pool),
            'insert_statement' => implode(', ', $insert_statement_pool)
        ];
    }

    /**
     * build update data string
     * @param  array   &$params  array for binding parameters to store
     * @return string
     */
    private function buildUpdateData(array &$params): string
    {
        $update_statement_pool = [];
        foreach ($this->sql_segments['data'] as $column => $value) {
            if (is_array($value) && !empty($value)) {
                $json_statement = [StrKit::getDBTargetStr($column)];
                foreach ($value as $json_key => $json_value) {
                    $field_expression = StrKit::getDBJSONPath($json_key);
                    $json_statement[] = "'$field_expression'";
                    if (is_object($json_value) && $json_value instanceof DBSyntax) {
                        $json_statement[] = $json_value->getVal();
                    } else {
                        $bind_key = str_replace('.', '_', ":upd_$column.$json_key");
                        $json_statement[] = $bind_key;
                        $params[$bind_key] = is_array($json_value) ?
                                             json_encode($json_value, JSON_UNESCAPED_UNICODE) :
                                             $json_value;
                    }
                }
                $update_statement_pool[] = StrKit::getDBTargetStr($column) .
                                           ' = JSON_SET(' . implode(', ', $json_statement) . ')';
            } elseif (is_object($value) && $value instanceof DBSyntax) {
                $update_statement_pool[] = StrKit::getDBTargetStr($column) . $value->getVal();
            } else {
                $update_statement_pool[] = StrKit::getDBTargetStr($column) . " = :upd_$column";
                $params[":upd_$column"] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
        }
        return implode(', ', $update_statement_pool);
    }

    /**
     * build "JOIN" string
     * @return string
     */
    private function buildJoin(): string
    {
        $join = '';
        if (!empty($this->sql_segments['join'])) {
            $join_pool = [];
            foreach ($this->sql_segments['join'] as $data) {
                $join_segment = strtoupper($data['type']) . ' JOIN ' . StrKit::getDBTargetStr($data['table']) . ' ';
                $join_segment .= $data['alias'] ? "AS '{$data['alias']}' " : '';
                $join_segment .= "ON {$data['on']}";
                $join_pool[] = $join_segment;
            }
            $join = implode(' ', $join_pool);
        }
        return $join;
    }

    /**
     * build "WHERE" string
     * @param  array   &$params  array for binding parameters to store
     * @return string
     */
    private function buildWhere(array &$params): string
    {
        if (empty($this->sql_segments['where'])) {
            return '';
        }
        $where = "WHERE {$this->sql_segments['where']}";
        foreach ($this->sql_segments['where_params'] as $key => $value) {
            if (is_object($value) && $value instanceof DBSyntax) {
                $where = str_replace($key, $value->getVal(), $where);
            } else {
                if ($this->union_cnt > 0) {
                    $key .= '_union' . $this->union_cnt;
                }
                $params[$key] = $value;
            }
        }
        return $where;
    }

    /**
     * build "GROUP BY" string
     * @return string
     */
    private function buildGroup(): string
    {
        if (empty($this->sql_segments['group'])) {
            return '';
        }
        $group_list = [];
        foreach ($this->sql_segments['group'] as $group) {
            $group_list[] = StrKit::getDBTargetStr($group);
        }
        return 'GROUP BY ' . implode(', ', $group_list);
    }

    /**
     * build "HAVING" string
     * @return string
     */
    private function buildHaving(): string
    {
        return !empty($this->sql_segments['group']) && !empty($this->sql_segments['having']) ?
               "HAVING {$this->sql_segments['having']}" :
               '';
    }

    /**
     * build "ORDER BY" string
     * @return string
     */
    private function buildOrder(): string
    {
        $order = '';
        if (!empty($this->sql_segments['order'])) {
            $order_pool = [];
            foreach ($this->sql_segments['order'] as $field => $rule) {
                if (!empty($rule)) {
                    $order_pool[] = StrKit::getDBTargetStr(trim($field)) . ' ' . strtoupper(trim($rule));
                } else {
                    $order_pool[] = StrKit::getDBTargetStr(trim($field));
                }
            }
            $order = 'ORDER BY ' . implode(', ', $order_pool);
        }
        return $order;
    }

    /**
     * build "LIMIT" string
     * @return string
     */
    private function buildLimit(): string
    {
        $limit = '';
        if (isset($this->sql_segments['length']) && (int)$this->sql_segments['length'] >= 0) {
            $value = (int)$this->sql_segments['length'];
            $limit = "LIMIT $value";
        }
        return $limit;
    }

    /**
     * build "OFFSET" string
     * @return string
     */
    private function buildOffset(): string
    {
        $offset = '';
        if (isset($this->sql_segments['offset']) && (int)$this->sql_segments['offset'] >= 0) {
            $value = (int)$this->sql_segments['offset'];
            $offset = "OFFSET $value";
        }
        return $offset;
    }
}
