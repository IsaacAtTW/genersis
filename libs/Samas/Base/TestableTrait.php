<?php
namespace Samas\PHP7\Base;

use \Samas\PHP7\Kit\AppKit;

/**
 * common trait for mocking objects in testing
 */
trait TestableTrait
{
    /**
     * passage for mocking -Model object
     * @param  string     $table      table name, allow database name concated by "." before table name
     * @param  int/array  $init_data  integer => auto-increment field value, object will auto bind to the row data
     *                                array   => object properties will be set by the array content
     * @param  array      $options    optional config
     *                                usable options:
     *                                    'namespace': string, specific namespace of class to load
     *                                    'data_source': string, specific data source name
     *                                    'prefix': string, table name prefixuse \\$1
     *                                    'suffix': string, table name suffixuse \\$1
     * @return AbstractModelClass
     */
    public function getModel(string $table, $initial_data = null, array $options = []): object
    {
        return AppKit::model($table, $initial_data, $options);
    }

    /**
     * passage for mocking -Manager object
     * @param  string  $table    table name, allow database name concated by "." before table name
     * @param  array   $options  optional config
     *                           usable options:
     *                               'namespace': string, specific namespace of class to load
     *                               'data_source': string, specific data source name
     *                               'prefix': string, table name prefixuse \\$1
     *                               'suffix': string, table name suffixuse \\$1
     * @return AbstractManagerClass
     */
    public function getManager(string $table, array $options = []): object
    {
        return AppKit::manager($table, $options);
    }

    /**
     * passage for mocking object
     * @param  string  $class_name  class name, need includes namespace
     * @param  mixed   $parameters  parameters for __construct() of the object
     * @return object
     */
    public function getObj(string $class_name, ...$parameters): object
    {
        return new $class_name(...$parameters);
    }
}
