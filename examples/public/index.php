<?php

use \Slim\App;
use \Samas\PHP7\Kit\{AppKit, ErrorKit, WebKit};
use \Samas\PHP7\Tool\Logger;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

// Init SamasLibs
require __DIR__ . '/../vendor/gomaji/genersis/libs/init.php';

// Instantiate the app
$app = new App(AppKit::config('slim'));
$GET_params = WebKit::getGETParams();
$container = $app->getContainer();

if (!empty($GET_params['debug']) || AppKit::config('debug_mode')) {
    ini_set('display_errors', 1);

    unset($container['errorHandler']);
    unset($container['phpErrorHandler']);
    set_error_handler(function ($level, $message, $file, $line) {
        throw new ErrorException($message, 0, $level, $file, $line);
    });

    set_exception_handler(function ($error) {
        $error_info = ErrorKit::getErrorInfo($error, true);
        $logger = new Logger;
        $logger->log('error', $error_info, 'alert');

        // replace with dev error handler
        if (php_sapi_name() === 'cli') {
            echo json_encode($error_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        } else {
            WebKit::json($error_info);
        }
    });
} else {
    $container['errorHandler'] = function ($container) {
        return function ($request, $response, $exception) use ($container) {
            $content = [
                'error_code' => 999,
                'message'    => 'Exception',
                'data'       => ''
            ];
            return $response->withJson($content);
        };
    };
    $container['phpErrorHandler'] = function ($container) {
        return function ($request, $response, $error) use ($container) {
            $content = [
                'error_code' => 999,
                'message'    => 'Error',
                'data'       => ''
            ];
            return $response->withJson($content);
        };
    };
}

$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        $content = [
            'error_code' => 404,
            'message'    => 'Page not found',
            'data'       => ''
        ];
        return $response->withJson($content);
    };
};

$container['notAllowedHandler'] = function ($container) {
    return function ($request, $response, $methods) use ($container) {
        $content = [
            'error_code' => 405,
            'message'    => 'Method must be ' . implode(', ', $methods),
            'data'       => ''
        ];
        return $response->withJson($content);
    };
};

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
