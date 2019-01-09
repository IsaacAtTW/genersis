<?php
namespace Samas\PHP7\Model;

use \RuntimeException;
use \Samas\PHP7\Kit\StrKit;
use \Samas\PHP7\Model\Database\DBAdapter;

/**
 * AbstractManagerClass / AbstractModelClass common trait
 */
trait TableAccessTrait
{
    protected $db_adapter;
    protected $data_source;
    protected $database = '';
    protected $table_name;

    /**
     * initialize properties
     * @param  array   $options  optional config
     *                           usable options:
     *                               'data_source': string, specific data source name
     *                               'prefix': string, table name prefixuse \\$1
     *                               'suffix': string, table name suffixuse \\$1
     * @return void
     */
    protected function init(array $options = [])
    {
        $suffix_length = strlen(self::MODEL_TYPE);

        if (empty($this->table_name)) {
            $table_camel = str_replace(__NAMESPACE__ . '\\', '', substr(get_class($this), 0, -1 * $suffix_length));
            $this->table_name = StrKit::convert($table_camel, StrKit::CASE_L_CHAR, StrKit::JOIN_UL);
        }

        $this->data_source = $options['data_source'] ?? '';

        if (!empty($options['prefix'])) {
            $this->table_name = $options['prefix'] . $this->table_name;
        }

        if (!empty($options['suffix'])) {
            $this->table_name = $this->table_name . $options['suffix'];
        }

        $this->db_adapter = new DBAdapter($this->data_source, $this->database, $this->table_name);
    }
}
