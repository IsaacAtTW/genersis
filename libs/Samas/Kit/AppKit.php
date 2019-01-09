<?php
namespace Samas\PHP7\Kit;

use \RuntimeException;
use \Samas\PHP7\Model\{AbstractManagerClass, AbstractModelClass, ManagerAgent, ModelAgent};

/**
 * Kit for processing application actions
 */
class AppKit
{
    private static $app_config = [];

    /**
     * read application configs and save them
     * @param  string  $config_path  file path of config file
     * @return void
     */
    public static function readConfig(string $config_path)
    {
        static::$app_config = array_merge(static::$app_config, self::parseConfig($config_path));
    }

    /**
     * operate config with overloading
     *     config()            => get all config
     *     config($key)        => get specific config by key
     *     config($key, value) => set value with specific key
     *     note: $key can be string or array, refer to ArrayKit::get() and ArrayKit::set()
     * @return mixed
     */
    public static function config()
    {
        $arg_num = func_num_args();
        if ($arg_num == 0) {
            return static::$app_config;
        }

        $key = func_get_arg(0);
        if ($arg_num == 1) {
            return ArrayKit::get(static::$app_config, $key);
        }
        return ArrayKit::set(static::$app_config, $key, func_get_arg(1));
    }

    /**
     * read config file and convert to array, supported extension: php, json, yml, xml
     * @param  string  $config_file_path  file path of config file
     * @return array
     */
    public static function parseConfig(string $config_file_path): array
    {
        $file_type = pathinfo($config_file_path, PATHINFO_EXTENSION);
        $config = [];
        switch ($file_type) {
            case 'php':
                $config = require $config_file_path;
                break;
            case 'json':
                if (!extension_loaded('json')) {
                    throw new RuntimeException('json extension is not loaded!');
                }
                $config = json_decode(file_get_contents($config_file_path), true);
                break;
            case 'yml':
                if (!extension_loaded('yaml')) {
                    throw new RuntimeException('yaml extension is not loaded!');
                }
                $config = yaml_parse(file_get_contents($config_file_path));
                break;
            case 'xml':
                if (!extension_loaded('libxml')) {
                    throw new RuntimeException('libxml extension is not loaded!');
                } elseif (!extension_loaded('simplexml')) {
                    throw new RuntimeException('simplexml extension is not loaded!');
                }
                $config = XMLKit::parse(simplexml_load_file($config_file_path, 'SimpleXMLElement', LIBXML_NOCDATA));
                break;
            default:
                throw new RuntimeException("type of config file '$config_file_path' not supports!");
                break;
        }
        return (array)$config;
    }

    /**
     * get -Model object, if class is not defined, return ModelAgent
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
    public static function model(string $table, $initial_data = null, array $options = []): AbstractModelClass
    {
        if (!empty($table)) {
            $table_name = $table;
            if (strpos($table, '.') !== false) {
                $segments = explode('.', $table);
                $table_name = array_pop($segments);
            }
            $project_namespace = self::config('project_namespace') ?? '';
            if (substr($project_namespace, 0, 1) != '\\') {
                $project_namespace = "\\$project_namespace";
            }
            if (substr($project_namespace, -1) != '\\') {
                $project_namespace .=  '\\';
            }
            $namespace = $options['namespace'] ?? $project_namespace;
            $class_name = $namespace . 'Model\\' .
                          StrKit::convert($table_name, StrKit::CASE_U_CAMEL) . AbstractModelClass::MODEL_TYPE;
            if (class_exists($class_name)) {
                $class = new $class_name($initial_data, $options);
                return $class;
            }
        }

        return new ModelAgent($table, $initial_data, $options);
    }

    /**
     * get -Manager object, if class is not defined, return ManagerAgent
     * @param  string  $table    table name, allow database name concated by "." before table name
     * @param  array   $options  optional config
     *                           usable options:
     *                               'namespace': string, specific namespace of class to load
     *                               'data_source': string, specific data source name
     *                               'prefix': string, table name prefixuse \\$1
     *                               'suffix': string, table name suffixuse \\$1
     * @return AbstractManagerClass
     */
    public static function manager(string $table = '', array $options = []): AbstractManagerClass
    {
        if (!empty($table)) {
            $table_name = $table;
            if (strpos($table, '.') !== false) {
                $segments = explode('.', $table);
                $table_name = array_pop($segments);
            }
            $project_namespace = self::config('project_namespace') ?? '';
            if (substr($project_namespace, 0, 1) != '\\') {
                $project_namespace = "\\$project_namespace";
            }
            if (substr($project_namespace, -1) != '\\') {
                $project_namespace .=  '\\';
            }
            $namespace = $options['namespace'] ?? $project_namespace;
            $class_name = $namespace . 'Manager\\' .
                          StrKit::convert($table_name, StrKit::CASE_U_CAMEL) . AbstractManagerClass::MODEL_TYPE;
            if (class_exists($class_name)) {
                $class = new $class_name($options);
                return $class;
            }
        }

        return new ManagerAgent($table, $options);
    }
}
