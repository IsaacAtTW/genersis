<?php
namespace Samas\PHP7\Model\Database;

use \ArrayObject;
use \Samas\PHP7\Kit\ArrayKit;

/**
 * object for operating JSON field
 */
class DBJSONColumn extends ArrayObject
{
    private $update_data = [];

    /**
     * alter specific field
     * @param  string/array  $field  field path key
     * @param  mixed         $value  value to replace
     * @return DBJSONColumn
     */
    public function alter($field, $value)
    {
        ArrayKit::set($this->update_data, $field, $value);
        $storage = $this->toArray();
        ArrayKit::set($storage, $field, $value);
        $this->exchangeArray($storage);
        return $this;
    }

    /**
     * when directly set array value, clear update data
     * @param  int/string  $key    element key
     * @param  mixed       $value  element value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->resetUpdateData();
        return parent::offsetSet($key, $value);
    }

    /**
     * clear update data
     * @return void
     */
    public function resetUpdateData()
    {
        $this->update_data = [];
    }

    /**
     * get update data
     * @return array
     */
    public function getUpdateData(): array
    {
        return $this->update_data;
    }

    /**
     * getArrayCopy() alias
     * @return array
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }
}
