<?php
namespace tests\GEN\Service;

use \Tests\BaseTestCase;
use \GEN\Service\SampleProductService;

class SampleProductServiceTest extends BaseTestCase
{
    public function testGenProductInfo()
    {
       $service = (new class extends SampleProductService {
            public function getModel(string $table, $initial_data = NULL, array $options = []): object
            {
                if ('example_products' == $table) {
                    return (new class ($table, $initial_data = NULL, $options = [])
                    {
                        public function getData()
                        {
                            return [
                                'pid'          => 2,
                                'product_name' => 'product name',
                                'product_desc' => 'desc'
                            ];
                        }
                    });
                }
                parent::getModel($table, $initial_data, $options);
            }
        });

        $result = $service->genProductInfo(2);
        $this->assertArrayHasKey('pid', $result);
        $this->assertArrayHasKey('product_name', $result);
        $this->assertArrayHasKey('product_desc', $result);
        $this->assertArrayHasKey('other', $result);
        $this->assertEquals($result['pid'], 2);
        $this->assertEquals($result['product_name'], 'product name');
        $this->assertEquals($result['product_desc'], 'desc');
        $this->assertEquals($result['other'], 'service logic...');
    }
}