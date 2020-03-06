<?php

namespace App\Tests\Feature\Api;

use App\Tests\TestBase;
use Symfony\Component\HttpFoundation\Response;

class AuthTest extends TestBase
{
    public function testUnauthorized()
    {
        $this->client->request('GET', '/api/resource', [], [], []);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer'
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer '
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer invalid_token'
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAuthorized()
    {
        $this->client->request('GET', '/api/resource', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->parameterBag->get('user.token')
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
