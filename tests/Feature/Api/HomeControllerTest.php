<?php

namespace App\Tests\Feature\Api;

use App\Tests\TestBase;

class HomeControllerTest extends TestBase
{
    public function testIndex()
    {
        $this->client->request('GET', '/', [], [], []);

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertCount(0, $content);
    }
}
