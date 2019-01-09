<?php
namespace Samas\PHP7\Cache;

use \RuntimeException, \APCIterator, \APCUIterator, \Iterator;

/**
 * Util of Alternative PHP Cache
 */
class APC
{
    const MODE_APCU = 1;
    const MODE_APC  = 2;

    private $mode;

    /**
     * __construct
     * @return void
     */
    public function __construct()
    {
        ini_set('apc.enabled', '1');
        if (extension_loaded('apcu')) {
            $this->mode = self::MODE_APCU;
        } elseif (extension_loaded('apc')) {
            $this->mode = self::MODE_APC;
        }
    }

    /**
     * check APC(or APCu) module is usable
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->mode);
    }

    /**
     * call functions of APC(or APCu) module directly
     * @param  string  $function_name       suffix of function
     * @param  array   $function_arguments  arguments of function
     * @return mixed
     */
    public function __call(string $function_name, array $function_arguments)
    {
        $function = $this->mode == self::MODE_APCU ? 'apcu_' . $function_name : 'apc_' . $function_name;
        if (function_exists($function)) {
            return call_user_func_array($function, $function_arguments);
        }
        throw new RuntimeException("$function_name() not exists");
    }

    /**
     * get APC(or APCu) cache data
     * @param  mixed  $search      search pattern
     * @param  int    $format      refer to APC_ITER_* constants
     * @param  int    $chunk_size  the chunk size
     * @param  int    $list        APC_LIST_ACTIVE or APC_LIST_DELETED
     * @return array
     */
    public function list(
        $search = null,
        int $format = APC_ITER_ALL,
        int $chunk_size = 100,
        int $list = APC_LIST_ACTIVE
    ): array {
        if ($this->mode == self::MODE_APCU) {
            $list = new APCUIterator($search, $format, $chunk_size, $list);
        } else {
            $list = new APCIterator('user', $search, $format, $chunk_size, $list);
        }
        $result = [];
        foreach ($list as $item) {
            $result[$item['key']] = $item;
        }
        return $result;
    }

    /**
     * clear all APC(or APCu) cache
     * @return bool
     */
    public function clearAll(): bool
    {
        $function = $this->mode == self::MODE_APCU ? 'apcu_clear_cache' : 'apc_clear_cache';
        return $function();
    }
}
