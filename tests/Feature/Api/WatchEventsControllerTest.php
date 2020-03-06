<?php

namespace App\Tests\Feature\Api;

use App\Tests\TestBase;
use Symfony\Component\HttpFoundation\Response;

class WatchEventsControllerTest extends TestBase
{
    public function testWatch()
    {
        $this->client->request('GET', '/api/watch', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertCount(5, $content);
    }
}
