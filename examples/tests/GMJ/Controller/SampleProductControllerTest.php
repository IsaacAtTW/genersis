<?php
namespace tests\GEN\Controller;

use \Tests\BaseTestCase;
use \GEN\Controller\SampleProductController;
use \GEN\Service\SampleProductService;

class SampleProductControllerTest extends BaseTestCase
{
    public function testProductInfo()
    {
       $controller = (new class extends SampleProductController {
            public function getObj(string $className, ...$parameters): object
            {
                // mock...
                if (SampleProductService::class == $className) {
                    return (new class (...$parameters)
                    {
                        public function genProductInfo(int $pid): array
                        {
                            return [
                                'pid'          => $pid,
                                'product_name' => 'product',
                                'product_desc' => 'desc',
                                'other'        => 'service logic...',
                            ];
                        }
                    });
                }
                parent::getObj($className, ...$parameters);
            }
        });

        // success case
        $response = $this->runApp('GET', '/tests/productInfo', ['pid' => 1], $controller);
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertArrayHasKey('pid', $result);
        $this->assertArrayHasKey('product_name', $result);
        $this->assertArrayHasKey('product_desc', $result);
        $this->assertArrayHasKey('other', $result);
        $this->assertEquals($result['pid'], 1);
        $this->assertEquals($result['product_name'], 'product');
        $this->assertEquals($result['product_desc'], 'desc');
        $this->assertEquals($result['other'], 'service logic...');

        // error case
        $response = $this->runApp('GET', '/tests/productInfo');
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertArrayHasKey('error_msg', $result);
        $this->assertEquals($result['error_msg'], 'pid not exist');
    }
}