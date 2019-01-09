<?php
namespace GEN\Service;

use \Samas\PHP7\Base\TestableClass;

class SampleProductService extends TestableClass
{
    public function genProductInfo(int $pid): array
    {
        // do something...
        $exampleProducts = $this->getModel('example_products', $pid);
        return array_merge($exampleProducts->getData(), ['other' => 'service logic...']);
    }
}