<?php
namespace Samas\PHP7\Tool;

use \Closure;
use \RuntimeException;
use \Dakatsuka\MonologFluentHandler\FluentHandler;
use \Monolog\Logger as MonoLogger;
use \Monolog\Handler\StreamHandler;
use \Samas\PHP7\Kit\AppKit;
use \Samas\PHP7\Kit\StrKit;
use \Samas\PHP7\Kit\WebKit;

/**
 * fluentd logger control center
 * composer require: dakatsuka/monolog-fluent-handler, monolog/monolog
 */
class Logger
{
    private static $data_source_config = [];
    private static $logger_obj;
    private $tag_category;
    private $level = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency'
    ];

    /**
     * __construct
     * @param  string  $tag_category  tag category
     * @return void
     */
    public function __construct($tag_category = '')
    {
        $this->initDataSourceLogConfig();
        if (!array_key_exists('fluentd', self::$data_source_config)) {
            throw new RuntimeException("data source 'log.fluentd' not defined!");
        }
        if (!empty($tag_category)) {
            $this->tag_category = $tag_category;
        }
    }

    /**
     * log request with pattern: /^[$project_name\.]?[$env]\.[$tag_category\.]?[$function_name\.]?[$tag_attr\.]?$/
     * @param  string  $tag_attr  tag attribute
     * @param  mixed   $data      content to log
     * @param  string  $level     log level
     * @return bool
     */
    public function log(string $tag_attr = '', $data = null, string $level = 'info'): bool
    {
        /**
         * debug_backtrace() parameters
         * 0: "object" -> n, "args" -> y
         * 1: "object" -> y, "args" -> y (default)
         * 2: "object" -> n, "args" -> n
         * 3: "object" -> y, "args" -> n
         */
        $debug_backtrace = debug_backtrace(0);
        $log_location = $debug_backtrace[0];

        if (!empty($this->tag_category)) {
            $tag_list = [$this->tag_category];
        }

        $log_context = [
            'severity' => strtoupper($level),
            'request'  => WebKit::getRequestInfo(),
            'data'     => $data,
            'program'  => [
                'path' => isset($log_location['file']) ?
                          sprintf('%s:%s', $log_location['file'], $log_location['line']) :
                          ''
            ]
        ];
        if (isset($debug_backtrace[1])) {
            $log_process = $debug_backtrace[1];
            $log_context['program']['class']    = isset($log_process['class']) ? $log_process['class'] : '';
            $log_context['program']['type']     = isset($log_process['type']) ? $log_process['type'] : '';
            $log_context['program']['function'] = '';
            $log_context['program']['args']     = [];

            if (!empty($log_process['function'])) {
                $tag_list[] = $log_process['function'] == '{closure}' ? 'base' : $log_process['function'];
                $log_context['program']['function'] = $log_process['function'];
            }
            foreach ($log_process['args'] as $arg) {
                if (is_object($arg)) {
                    $log_context['program']['args'][] = '(Object)'.get_class($arg);
                } else {
                    $log_context['program']['args'][] = $arg;
                }
            }
            if (isset($debug_backtrace[2]) && isset($debug_backtrace[2]['file'])) {
                $log_context['program']['trace'] = sprintf(
                    '%s:%s',
                    $debug_backtrace[2]['file'],
                    $debug_backtrace[2]['line']
                );
            }
        }

        if (!empty($tag_attr)) {
            $tag_list[] = $tag_attr;
        }

        return $this->logData(implode('.', $tag_list), $log_context, $level);
    }

    /**
     * log data with pattern: /^[$project_name\.]?[$env]?\.[$tag]?$/
     * @param  string  $tag_attr  tag attribute
     * @param  mixed   $data      content to log
     * @param  string  $level     log level
     * @return bool
     */
    public function logData(string $tag = '', $log_context = null, string $level = 'info'): bool
    {
        $log_level = 'info';
        if (in_array($level, $this->level)) {
            $log_level = $level;
        }

        return $this->getLoggerInstance()->$level($tag, $log_context);
    }

    /**
     * initialize data source log config
     * @return void
     */
    private function initDataSourceLogConfig()
    {
        if (empty(self::$data_source_config)) {
            if (empty(AppKit::config('log'))) {
                throw new RuntimeException("data source 'log' field missing!");
            }
            self::$data_source_config = AppKit::config('log');
        }
    }

    /**
     * get MonoLogger object
     * @return MonoLogger
     */
    private function getLoggerInstance(): MonoLogger
    {
        if (self::$logger_obj === null) {
            $config = self::$data_source_config['fluentd'];
            if (empty($config['host']) || empty($config['port'])) {
                throw new RuntimeException("data source 'log.fluentd' need both host and port attribute!");
            }
            $tag_name = AppKit::config('project_name') ?? '';
            $tag_name .= $tag_name === '' ? AppKit::config('env') : '.' . AppKit::config('env');
            self::$logger_obj = new MonoLogger($tag_name);
            self::$logger_obj->pushHandler(
                new FluentHandler(null, $config['host'], $config['port'])
            );
        }
        return self::$logger_obj;
    }
}
