<?php
namespace Samas\PHP7\Tool;

use \Psr\Http\Message\ResponseInterface;

class JSONResponse
{
    public $error_code = 0;
    public $message    = '';
    public $data       = '';

    private $response;

    /**
     * __construct
     * @param  ResponseInterface  $response  response object
     * @return void
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * response success in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function success(string $message = 'success', $data = null)
    {
        $this->error_code = 0;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
    }

    /**
     * response fail in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function fail(string $message = 'fail', $data = null)
    {
        $this->error_code = 1;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
    }

    /**
     * response invalid in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function invalid(string $message = 'invalid', $data = null)
    {
        $this->error_code = 2;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
        // return $this->renderJSON(400);
    }

    /**
     * response deny in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function deny(string $message = 'deny', $data = null)
    {
        $this->error_code = 3;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
        // return $this->renderJSON(403);
    }

    /**
     * response duplicate in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function duplicate(string $message = 'duplicate', $data = null)
    {
        $this->error_code = 4;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
    }

    /**
     * response exception in json type
     * @param  string  $message  message
     * @param  mixed   $data     return data
     * @return void
     */
    public function exception(string $message = 'exception', $data = null)
    {
        $this->error_code = 999;
        if (!empty($message)) {
            $this->message = $message;
        }
        if ($data !== null) {
            $this->data = $data;
        }
        return $this->renderJSON();
        // return $this->renderJSON(500);
    }

    /**
     * set json string into response object
     * @param  mixed  $status  http status
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function renderJSON($status = null)
    {
        return $this->response->withJson($this, $status);
    }
}
