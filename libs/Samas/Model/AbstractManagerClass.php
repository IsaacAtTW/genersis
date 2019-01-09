<?php
namespace Samas\PHP7\Model;

use \RuntimeException;
use \Samas\PHP7\Kit\StrKit;
use \Samas\PHP7\Model\Database\DBSyntax;

/**
 * prototype of -Manager series class, which operate data by condition
 */
abstract class AbstractManagerClass
{
    use TableAccessTrait;
    /**
     * properties and methods from TableAccessTrait:
     *
     * protected $db_adapter;
     * protected $data_source;
     * protected $database;
     * protected $table_name;
     *
     * protected function init(array $options = []): void {}
     */

    const MODEL_TYPE = 'Manager';

    /**
     * __construct
     * @param  array  $options  optional config
     *                          usable options:
     *                              'data_source': string, specific data source name
     *                              'prefix': string, table name prefixuse \\$1
     *                              'suffix': string, table name suffixuse \\$1
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->init($options);
    }

    /**
     * virtual methods:
     *     public function getBy{field}($value, $like_expression = false) {}
     * @param  string  $method_name       method name
     * @param  array   $method_arguments  method arguments
     * @return false on failure, result array on success
     */
    public function __call(string $method_name, array $method_arguments)
    {
        if (method_exists($this->db_adapter, $method_name)) {
            return call_user_func_array([$this->db_adapter, $method_name], $method_arguments);
        } elseif (substr($method_name, 0, 5) == 'getBy') {
            if (empty($this->table_name)) {
                throw new RuntimeException('methods of getBy() series need table name');
            } else {
                // public function getBy{field}($value, $like_expression = false)
                if (count($method_arguments) == 0) {
                    throw new RuntimeException('parameter $value is missing');
                }
                $value = $method_arguments[0];
                if (is_array($value) || is_object($value) || is_resource($value)) {
                    throw new RuntimeException("$method_name() parameter 1 should be scalar type");
                }
                $field = strtolower(implode('_', StrKit::parseCamel(substr($method_name, 5))));
                $where = "`$field` = :$field";
                if (!empty($method_arguments[1])) {
                    $where = "`$field` LIKE :$field";
                    $value = (string)$value;
                }
                $params = is_object($value) && $value instanceof DBSyntax ?
                          [":$field" => $value->getVal()] :
                          [":$field" => $value];
            }
            return $this->db_adapter
                        ->createSQL()
                        ->where($where, $params)
                        ->select();
        } else {
            throw new RuntimeException("the method $method_name() not exists");
        }
    }
}
