<?php
namespace GEN\Model;

use \Samas\PHP7\Base\BaseModel;

class UserModel extends BaseModel
{
    protected $data_source    = 'genersis';
    protected $database       = 'genersis';
    protected $table_name     = 'user';
    protected $auto_increment = 'id';
    protected $pk             = ['id'];
    protected $table_field    = [
        'id'          => self::DATA_INT,
        'first_name'  => self::DATA_STR,
        'last_name'   => self::DATA_STR,
        'height'      => self::DATA_INT,
        'weight'      => self::DATA_INT,
        'inactive_ts' => self::DATA_STR,
        'json_column' => self::DATA_STR
    ];

    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getHeight()
    {
        return round($this->field_value['height'] / 100, 2);
    }
}
