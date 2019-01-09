<?php

use \Psr\Http\Message\{ServerRequestInterface as RequestI, ResponseInterface as ResponseI};
use \Samas\PHP7\Kit\AppKit;
use \GEN\Controller;

// Routes
$controller = $controller ?? null;

// helthy check, DO NOT remove!
$app->get('/', function (RequestI $request, ResponseI $response, array $args) {
    $slogan = ['msg' => 'GOMAJI GO! GOMAJI GO! GOMAJI GO! GOMAJI GOMAJI GOMAJI GO GO GO!'];
    return $response->withJson($slogan);
});

$app->get('/tests/productInfo', function (RequestI $request, ResponseI $response, array $args) use ($controller) {
    $controller = $controller ?? new Controller\SampleProductController();
    return $controller->action('actionProductInfo', $request, $response, $args);
});

// sample code
if (AppKit::config('env') == 'dev') {
    $app->group('/sample', function () use ($app, $controller) {

        $controller = $controller ?? new Controller\SampleController();

        $app->get('/{no}', function (RequestI $request, ResponseI $response, array $args) use ($controller) {
            $actionName = "actionSample{$args['no']}";
            return $controller->action($actionName, $request, $response, $args);
        });
    });
}
