<?php
namespace Samas\PHP7\Cache;

use \RuntimeException;
use \Predis\Client;
use \Samas\PHP7\Kit\AppKit;

/**
 * Util of Redis
 * composer require: predis/predis
 */
class Redis extends Client
{
    private static $data_source_config = [];

    /**
     * __construct
     * @return void
     */
    public function __construct()
    {
        $this->initDataSourceRedisCacheConfig();
        if (empty(self::$data_source_config['host']) || empty(self::$data_source_config['port'])) {
            throw new RuntimeException("data source 'redis.cache' need host and port attributes!");
        }
        parent::__construct(self::$data_source_config['host'] . ':' . self::$data_source_config['port']);
    }

    /**
     * initialize data source config
     * @return void
     */
    private function initDataSourceRedisCacheConfig()
    {
        if (empty(self::$data_source_config)) {
            if (empty(AppKit::config('redis'))) {
                throw new RuntimeException("data source 'redis' field missing!");
            } elseif (empty(AppKit::config(['redis', 'cache']))) {
                throw new RuntimeException("data source 'redis.cache' field missing!");
            }
            self::$data_source_config = AppKit::config(['redis', 'cache']);
        }
    }
}
