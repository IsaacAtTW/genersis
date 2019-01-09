<?php
namespace Samas\PHP7\Model;

use \RuntimeException;
use \Samas\PHP7\Cache\APC;
use \Samas\PHP7\Kit\StrKit;
use \Samas\PHP7\Model\Database\DBJSONColumn;
use \Samas\PHP7\Model\Database\DBSyntax;

/**
 * prototype of -Model series class, which operate data by single row
 */
abstract class AbstractModelClass
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

    const MODEL_TYPE = 'Model';
    const DATA_STR   = 'string';
    const DATA_INT   = 'int';
    const DATA_BOOL  = 'bool';
    const MYSQL_LENGTH_STRING_TYPE_LIST = ['char', 'varchar', 'enum', 'set'];
    const MYSQL_NO_LENGTH_INT_TYPE_LIST = ['timestamp', 'year'];

    protected $auto_increment = '';
    protected $pk             = [];
    protected $table_field    = [];
    protected $field_value    = [];

    /**
     * __construct
     * @param  int/array  $init_data  integer => auto-increment field value, object will auto bind to the row data
     *                                array   => object properties will be set by the array content
     * @param  array      $options    optional config
     *                                usable options:
     *                                    'data_source': string, specific data source name
     *                                    'prefix': string, table name prefixuse \\$1
     *                                    'suffix': string, table name suffixuse \\$1
     * @return void
     */
    public function __construct($init_data = null, array $options = [])
    {
        $this->init($options);

        if (empty($this->table_field)) {
            $ignore_cache = $options['ignore_cache'] ?? false;
            $table_schema = $this->getTableSchema($ignore_cache);
            foreach ($table_schema as $instance_data) {
                $field = $instance_data['Field'];
                $this->table_field[$field] = $this->convertMysqlType($instance_data['Type']);
                if ($instance_data['Key'] == 'PRI') {
                    $this->pk[] = $field;
                }
                if ($instance_data['Extra'] == 'auto_increment') {
                    $this->auto_increment = $field;
                }
            }
        }

        if ($init_data) {
            $this->setData($init_data);
        }
    }

    /**
     * control "get" actions on object properties, properties are mapping to the table columns
     *     getter method is defined => call getter
     *     getter method is not defined => return the value directly
     * @param  string  $property_name  property name
     * @return mixed
     */
    public function __get(string $property_name)
    {
        $getter = 'get' . StrKit::convert($property_name, StrKit::CASE_U_CAMEL);

        if ($property_name == 'db_adapter') {
            return null;
        } elseif (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($property_name, $this->field_value)) {
            return $this->field_value[$property_name];
        } elseif (array_key_exists($property_name, $this->table_field)) {
            return null;
        } elseif (property_exists($this, $property_name)) {
            return $this->$property_name;
        } else {
            throw new RuntimeException("the property '$property_name' not exists");
        }
    }

    /**
     * control "set" actions on object properties, properties are mapping to the table columns
     *     setter method is defined => call setter
     *     setter method is not defined => save the value directly
     * @param  string  $property_name   property name
     * @param  mixed   $property_value  property value
     * @return mixed
     */
    public function __set(string $property_name, $property_value)
    {
        $setter = 'set' . StrKit::convert($property_name, StrKit::CASE_U_CAMEL);

        if (method_exists($this, $setter)) {
            return $this->$setter($property_value);
        } elseif (array_key_exists($property_name, $this->table_field)) {
            if ($this->table_field[$property_name] instanceof DBJSONColumn) {
                $this->table_field[$property_name]->exchangeArray($property_value);
                $this->table_field[$property_name]->resetUpdateData();
                return $property_value;
            }
            return $this->field_value[$property_name] = $property_value;
        } else {
            throw new RuntimeException("the property '$property_name' not exists");
        }
    }

    /**
     * return property exists or not when empty() or isset() is apply on it self
     * @param   string  $property_name  property name
     * @return  bool
     */
    public function __isset(string $property_name): bool
    {
        return array_key_exists($property_name, $this->table_field);
    }

    /**
     * when using the object as function, object will be set with the data
     * @param  int/array  $init_data  integer => auto-increment field value, object will auto bind to the row data
     *                                array   => object will be set to the array value
     * @return AbstractModelClass/bool
     */
    public function __invoke($init_data)
    {
        $this->setData($init_data);
    }

    /**
     * process "insert" or "update" actions base on auto-increment field is set or not
     * @return int/bool  when update occured or auto-increment field is not set => is success or not
     *                   when insert occured and auto-increment field is set=> last insert auto-increment id
     */
    public function save()
    {
        $object_data = [];
        foreach ($this->field_value as $field => $value) {
            if ($value === null) {
                $object_data[$field] = null;
            } elseif ($value instanceof DBJSONColumn) {
                $object_data[$field] = $value;
            } else {
                eval('$object_data[$field] = (' . $this->table_field[$field] . ')$value;');
            }
        }

        $pk_is_fill = true;
        foreach ($this->pk as $pk) {
            if (!array_key_exists($pk, $this->field_value) || empty($this->field_value[$pk])) {
                $pk_is_fill = false;
                break;
            }
        }

        array_walk($object_data, function (&$value, $key, $pk_is_fill) {
            if ($value instanceof DBJSONColumn) {
                $update_data = $value->getUpdateData();
                if ($pk_is_fill && !empty($update_data)) {
                    $value->resetUpdateData();
                    $value = $update_data;
                } else {
                    $value = json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
                }
            }
        }, $pk_is_fill);

        if ($pk_is_fill) {
            $pk_condition_array = [];
            $pk_value_array = [];
            foreach ($this->pk as $pk) {
                $pk_condition_array[]   = "`$pk` = :$pk";
                $pk_value_array[":$pk"] = $this->field_value[$pk];
            }

            $result = $this->db_adapter->update(
                $object_data,
                implode(' AND ', $pk_condition_array),
                $pk_value_array
            );
            return $result !== false ? true : false;
        } elseif (!empty($this->auto_increment)) {
            $result = $this->db_adapter->insert($object_data);
            if ($result === false) {
                return $result;
            }
            $this->field_value[$this->auto_increment] = (int)$result;
            return $this->field_value[$this->auto_increment];
        } else {
            // drivers may return a meaningless value when table has no auto-increament field
            return $this->db_adapter->insert($object_data) !== false ? true : false;
        }
    }

    /**
     * get fields data
     * @return array
     */
    public function getData(): array
    {
        return $this->field_value;
    }

    /**
     * set the value of properties by input
     * @param  int/array  $init_data  integer => auto-increment field value, object will auto bind to the row data
     *                                array   => object will be set to the array value
     * @return AbstractModelClass/bool  false on failure, AbstractModelClass on success
     */
    public function setData($init_data)
    {
        $this->resetData();
        $instance_data = $init_data;
        if (!is_array($init_data)) {
            if (empty($this->auto_increment)) {
                throw new RuntimeException('auto-increament field need to be set before using it to init data');
            }
            $field = $this->auto_increment;
            if (!StrKit::checkInt($init_data)) {
                throw new RuntimeException("instance $field input '$init_data' is illegal");
            }
            $instance_data = $this->db_adapter->find("$field = :$field", [":$field" => $init_data]);

            if (!$instance_data) {
                return false;
            }
        }

        foreach ($instance_data as $field => $value) {
            if (array_key_exists($field, $this->table_field)) {
                if ($value !== null && StrKit::isJSON($value)) {
                    $value = json_decode($value, true);
                    $this->field_value[$field] = new DBJSONColumn($value);
                } else {
                    $this->field_value[$field] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * clear properties
     * @return AbstractModelClass
     */
    public function resetData(): AbstractModelClass
    {
        $this->field_value = [];
        return $this;
    }

    /**
     * query the table to bind the first row data by condition
     *     bindBy(string $sql = '', array $params = [])
     *     bindBy(array $and_list)
     *     bindBy(string $type = ['and', 'or'], array $params)
     *     bindBy(DBsyntax $obj, array $params = [])
     * @param  mixed  $where   where condition (prepared statement, list type or DBsyntax)
     * @param  array  $params  parameters to bind with where condition
     * @return AbstractModelClass/bool  false on failure, AbstractModelClass on success
     */
    public function bindBy($where, array $params = [])
    {
        $instance_data = $this->db_adapter->find($where, $params);
        if (!$instance_data) {
            return false;
        }

        return $this->setData($instance_data);
    }

    /**
     * translate MySQL data type into PHP native data type name
     * @param  string  $mysql_type  MySQL data type
     * @return string
     */
    protected function convertMysqlType(string $mysql_type): string
    {
        $description_segments = explode(' ', $mysql_type);
        $main_attr = $description_segments[0];
        $attr_name = $main_attr;
        $length    = 0;
        if (strpos($main_attr, '(') !== false) {
            $attr_name = strstr($main_attr, '(', true);
            $length = str_replace(['(', ')'], '', strstr($main_attr, '('));
        }
        if ($length == 0) {
            $type = self::DATA_STR;
            if (in_array($attr_name, self::MYSQL_NO_LENGTH_INT_TYPE_LIST)) {
                $type = self::DATA_INT;
            }
        } else {
            $type = self::DATA_INT;
            if ($attr_name == 'tinyint' && $length == 1) {
                $type = self::DATA_BOOL;
            } elseif (in_array($attr_name, self::MYSQL_LENGTH_STRING_TYPE_LIST)) {
                $type = self::DATA_STR;
            }
        }
        return $type;
    }

    /**
     * get schema info, if info does not store in APC, query DB to get
     * @param  bool  $ignore_cache  ignore cache, query DB directly
     * @return array
     */
    protected function getTableSchema(bool $ignore_cache = false): array
    {
        $apc = new APC();
        $cache_key = $this->getCacheKey();
        if ($ignore_cache || !$apc->isEnabled() || ($schema = $apc->fetch($cache_key)) === false) {
            $table_expression = empty($this->database) ?
                                "`$this->table_name`" :
                                $this->database . '.' . $this->table_name;
            $sql = "SHOW COLUMNS FROM $table_expression";
            $schema = $this->db_adapter->query($sql);
            if (!$schema) {
                throw new RuntimeException("can not find table '$table_expression'");
            }
            if ($apc->isEnabled()) {
                $apc->store($cache_key, $schema);
            }
        }
        return $schema;
    }

    /**
     * get APC cache key
     * @return string
     */
    protected function getCacheKey(): string
    {
        return "{$this->database}.{$this->table_name}.schema";
    }
}
