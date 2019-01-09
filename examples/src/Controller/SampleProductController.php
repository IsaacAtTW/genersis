<?php
namespace GEN\Controller;

use \Samas\PHP7\Base\BaseController;
use \GEN\Service\SampleProductService;

class SampleProductController extends BaseController
{
    protected function actionProductInfo(): \Slim\Http\Response
    {
        $pid = (int)$this->request->getParam('pid');

        if (0 >= $pid) {
            return $this->response->withJson(['error_msg' => 'pid not exist'], 200);
        }
        $productService = $this->getObj(SampleProductService::class);
        return $this->response->withJson($productService->genProductInfo($pid), 200);
    }
}
