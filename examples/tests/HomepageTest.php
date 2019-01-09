<?php

namespace Tests;

class HomepageTest extends BaseTestCase
{
    public function testGetHomepage()
    {
        $response = $this->runApp('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('GOMAJI GO! GOMAJI GO! GOMAJI GO! GOMAJI GOMAJI GOMAJI GO GO GO!', (string)$response->getBody());
    }
}