<?php

namespace Tests;

use \Slim\App;
use \Slim\Http\{Request, Response, Environment};
use \Samas\PHP7\Kit\AppKit;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Use middleware when running application?
     *
     * @var bool
     */
    protected $withMiddleware = true;
    protected $withHeaders = [];

    /**
     * set header
     * @param string $key 
     * @param string $value 
     * @return void
     */
    public function setHeader(string $key, string $value): void
    {
        $this->withHeaders[$key] = $value;
    }

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array|object|null $requestData the request data
     * @return \Slim\Http\Response
     */
    public function runApp(string $requestMethod, string $requestUri, $requestData = null, $controller = null)
    {
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => $requestMethod,
                'REQUEST_URI' => $requestUri
            ]
        );

        // Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);

        // Add request header
        if (!empty($this->withHeaders) && is_array($this->withHeaders)) {
            foreach ($this->withHeaders as $key => $value) {
                $request = $request->withHeader($key, $value);
            }
        }

        // Add request data, if it exists
        if (isset($requestData)) {
            $request = $request->withParsedBody($requestData);
        }

        // Set up a response object
        $response = new Response();

        // Init SamasLibs
        require __DIR__ . '/../libs/Samas/PHP7/init.php';

        // Instantiate the app
        $app = new App(AppKit::config('slim'));

        // Register middleware
        if ($this->withMiddleware) {
            require __DIR__ . '/../src/middleware.php';
        }

        // Register routes
        require __DIR__ . '/../src/routes.php';

        // Process the application
        $response = $app->process($request, $response);

        // Return the response
        return $response;
    }
}
