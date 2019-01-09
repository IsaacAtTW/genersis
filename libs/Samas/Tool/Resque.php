<?php
namespace Samas\PHP7\Tool;

use \RuntimeException;
use \Resque as PhpResque;
use \Samas\PHP7\Kit\AppKit;

/**
 * composer require: chrisboulton/php-resque
 */
class Resque
{
    private static $data_source_config = [];

    public static function enqueue($queue, $class, $args = null, $trackStatus = false)
    {
        self::initDataSourceRedisResqueConfig();
        $host = self::$data_source_config['host'] ?? 'localhost';
        $port = self::$data_source_config['port'] ?? 6379;
        PhpResque::setBackend("[$host]:$port");
        PhpResque::enqueue($queue, $class, $args, $trackStatus);
    }

    /**
     * initialize data source config
     * @return void
     */
    private static function initDataSourceRedisResqueConfig()
    {
        if (empty(self::$data_source_config)) {
            if (empty(AppKit::config('redis'))) {
                throw new RuntimeException("data source 'redis' field missing!");
            } elseif (empty(AppKit::config(['redis', 'resque']))) {
                throw new RuntimeException("data source 'redis.resque' field missing!");
            }
            self::$data_source_config = AppKit::config(['redis', 'resque']);
        }
    }
}
