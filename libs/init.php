<?php
use \Samas\PHP7\Kit\{AppKit, DevKit, WebKit};

/*
support config extensions: php, json, yml, and xml
 */
AppKit::readConfig('/etc/sysconfig/config.yml');
AppKit::readConfig('/var/www/html/config/project-settings.php');

$GET_params = WebKit::getGETParams();
if (!empty($GET_params['sql']) || AppKit::config('sql_collection')) {
    AppKit::config('sql_collection', true);
    $sql_collection = [];
}

if (!defined('SYS_BOOT')) {
    define('SYS_BOOT', true);

    if (AppKit::config('env') == 'dev') {
        function is($var, bool $exit = false)
        {
            DevKit::is($var, $exit, 1);
        }

        function dump($var, bool $exit = false)
        {
            DevKit::dump($var, $exit, 1);
        }
    }
}
