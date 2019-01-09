<?php
namespace Samas\PHP7\Model;

use \RuntimeException;

/**
 * Dummy class
 */
class Dummy
{
    protected $is_instance_object;
    protected $data;

    /**
     * __construct
     * @param  array  $data                base data
     * @param  bool   $is_instance_object  this parameter is used when iterating
     * @return void
     */
    public function __construct(array $data = [], bool $is_instance_object = false)
    {
        $this->setList($data);
        $this->is_instance_object = $is_instance_object;
    }

    /**
     * when using the object as function, object will be set with the data
     * @param  array   $data  base data
     * @return void
     */
    public function __invoke(array $data)
    {
        $this->setList($data);
    }

    /**
     * get property get when iterating, if getter is defined, use getter function
     * @param  string  $key  property name
     * @return mixed
     */
    public function __get($key)
    {
        if (!$this->is_instance_object) {
            throw new RuntimeException('list cannot access properties');
        }
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if (method_exists($this, $method)) {
            return $this->$method();
        } elseif (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            throw new RuntimeException('undefined property');
        }
    }

    /**
     * bind data by conditions
     * @param  string  $key_name       new key in base data array
     * @param  array   $inject_data    data to bind
     * @param  mixed   $default_value  default value if no mapping data to bind
     * @param  string  $mapping_index  specific key of base data to match inject data key
     * @return void
     */
    public function inject(string $key_name, array $inject_data, $default_value = null, string $mapping_index = '')
    {
        $params = [
            'key_name'      => $key_name,
            'inject_data'   => $inject_data,
            'default_value' => $default_value,
            'mapping_index' => $mapping_index
        ];
        array_walk($this->data, function (&$value, $key, $params) {
            $comparison = is_string($params['mapping_index']) && !empty($params['mapping_index']) ?
                          $value[$params['mapping_index']] :
                          $key;
            $value[$params['key_name']] = array_key_exists($comparison, $params['inject_data']) ?
                                          $params['inject_data'][$comparison] :
                                          $params['default_value'];
        }, $params);
    }

    /**
     * iterate data as array
     * @param  bool  $to_object  convert array to object or not
     * @return mixed
     */
    public function iterate($to_object = false)
    {
        if ($to_object) {
            $dummy_class = get_class($this);
            $dummy = new $dummy_class([], true);
        }
        foreach ($this->data as $index => $instance_data) {
            yield $index => $to_object ? $dummy($instance_data) : $instance_data;
        }
    }

    /**
     * set base data
     * @param  array  $data  base data
     * @return void
     */
    private function setList(array $data)
    {
        $this->data = $data;
    }
}
