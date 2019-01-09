<?php
namespace Samas\PHP7\Base;

use \RuntimeException;
use \Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use \Samas\PHP7\Kit\AppKit;
use \Samas\PHP7\Tool\{JSONResponse, Renderer};

/**
 * prototype of controllers
 */
class BaseController
{
    use TestableTrait;

    protected $action;
    protected $request;
    protected $response;
    protected $args;
    protected $result;
    protected $is_ajax = false;
    protected $is_ajax_header = false;

    /**
     * call JSONResponse function directly
     * @param  string  $method_name    method name of JSONResponse
     * @param  array   $method_params  method parameters of JSONResponse
     * @return mixed
     */
    public function __call(string $method_name, array $method_params)
    {
        if (method_exists($this->result, $method_name)) {
            return call_user_func_array([$this->result, $method_name], $method_params);
        } else {
            throw new RuntimeException('method not found');
        }
    }

    /**
     * action entry
     * @param  string                  $method_name  action method name
     * @param  ServerRequestInterface  $request      request
     * @param  ResponseInterface       $response     response
     * @param  array                   $args         arguments
     * @return ResponseInterface
     */
    public function action(
        string $method_name,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface {
        if (!method_exists($this, $method_name)) {
            throw new RuntimeException('action not found');
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower(getenv('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest'
        ) {
            $this->is_ajax_header = true;
        }

        $this->action   = $method_name;
        $this->request  = $request;
        $this->response = $response;
        $this->args     = $args;
        $this->result   = new JSONResponse($response);

        $before_action = $this->beforeAction();
        if ($before_action !== true) {
            return $before_action;
        }
        $action_result = $this->$method_name();
        $this->afterAction();
        return $action_result;
    }

    /**
     * since there is no sure-fire way of knowing that a request was made via Ajax, this method can process ajax for sure
     * @param  string                  $method_name  action method name
     * @param  ServerRequestInterface  $request      request
     * @param  ResponseInterface       $response     response
     * @param  array                   $args         arguments
     * @return ResponseInterface
     */
    public function ajax(
        string $method_name,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface {
        $this->is_ajax = true;
        return $this->action($method_name, $request, $response, $args);
    }

    /**
     * return the request is ajax or not
     * @param  bool  $trust_header  trust header or not
     * @return bool
     */
    public function isAjax(bool $trust_header = true): bool
    {
        return $this->is_ajax || ($trust_header && $this->is_ajax_header);
    }

    /**
     * this function will process before call protected actions
     * @return bool or ResponseInterface, enter the action with true, others return the ResponseInterface
     */
    public function beforeAction()
    {
        return true;
    }

    /**
     * this function will process after call protected actions
     * @return void
     */
    public function afterAction()
    {
    }

    /**
     * create page renderer
     * @param  array/null  $options  render options
     * @return Renderer
     */
    public function createPage($options = null)
    {
        $options = $options ?? $this->getPageOptions();
        return new Renderer($this->response, Renderer::TYPE_PAGE, $options);
    }

    /**
     * create block renderer
     * @param  array/null  $options  render options
     * @return Renderer
     */
    public function createBlock($options = null)
    {
        $options = $options ?? $this->getBlockOptions();
        return new Renderer($this->response, Renderer::TYPE_BLOCK, $options);
    }

    /**
     * get render page options
     * @return array
     */
    public function getPageOptions(): array
    {
        return [];
    }

    /**
     * get render block options
     * @return array
     */
    public function getBlockOptions(): array
    {
        return [];
    }
}
