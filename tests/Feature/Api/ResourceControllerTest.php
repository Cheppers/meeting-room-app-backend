<?php

namespace App\Tests\Feature\Api;

use App\Tests\Mocks\Google\GoogleServiceDirectoryResourceResourcesCalendars;
use App\Tests\TestBase;
use Symfony\Component\HttpFoundation\Response;

class ResourceControllerTest extends TestBase
{
    public function testIndex()
    {
        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(5, $content['data']);
        $this->assertEquals('r1', $content['data'][0]['resource_id']);


        GoogleServiceDirectoryResourceResourcesCalendars::$throwGoogleServiceException = true;
        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertEquals('Test error', $content['errors'][0]['detail']);
        GoogleServiceDirectoryResourceResourcesCalendars::$throwGoogleServiceException = false;


        GoogleServiceDirectoryResourceResourcesCalendars::$throwConnectException = true;
        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertEquals('Google API unavailable', $content['errors'][0]['detail']);
        GoogleServiceDirectoryResourceResourcesCalendars::$throwConnectException = false;
    }
}
