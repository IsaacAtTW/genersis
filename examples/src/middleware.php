<?php
// Application middleware

use \Samas\PHP7\Kit\AppKit;
use \Samas\PHP7\Tool\Logger;

/**
 * sql collection
 */
$app->add(function ($request, $response, $next) {
    $response = $next($request, $response);

    if (AppKit::config('sql_collection')) {
        global $sql_collection;
        $logger_obj = new Logger;
        $logger_obj->logData('SQL', [
            'request'        => WebKit::getRequestInfo(),
            'sql_collection' => $sql_collection
        ]);
    }

    return $response;
});
