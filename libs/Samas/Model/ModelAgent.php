<?php
namespace Samas\PHP7\Model;

use \RuntimeException;

/**
 * extended AbstractModelClass, build a virtual -Model class
 */
class ModelAgent extends AbstractModelClass
{
    /**
     * __construct, will pre-define some attributes before original __construct
     * @param  string     $reflect_table  table name, allow database name concated by "." before table name
     * @param  int/array  $init_data      integer => auto-increment field value, object will auto bind to the row data
     *                                    array   => object properties will be set by the array content
     * @param  array      $options        optional config
     *                                    usable options:
     *                                        'data_source': string, specific data source name
     *                                        'prefix': string, table name prefixuse \\$1
     *                                        'suffix': string, table name suffixuse \\$1
     * @return void
     */
    public function __construct(string $reflect_table, $init_data = null, array $options = [])
    {
        if (strpos($reflect_table, '.') !== false) {
            $segments = explode('.', $reflect_table);
            if (count($segments) != 2) {
                $pattern = '/^[[$database]?\.]?[$table_name]{1}$/';
                throw new RuntimeException("patteran of reflect table need match the pattern: $pattern");
            }
            $this->database   = $segments[0];
            $this->table_name = $segments[1];
        } else {
            $this->table_name = $reflect_table;
        }
        parent::__construct($init_data, $options);
    }
}
