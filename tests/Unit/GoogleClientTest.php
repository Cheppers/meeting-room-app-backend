<?php

namespace App\Tests\Unit;

use App\Tests\TestBase;
use App\Utils\GoogleAPI\GoogleClient;

class GoogleClientTest extends TestBase
{
    public function testGoogleClient()
    {
        $googleClient = new GoogleClient($this->parameterBag);
        $googleClient->initClient();

        $client = $googleClient->getClient();
        $this->assertNotEmpty($client);

        $googleClient->setClient($client);

        $client = $googleClient->getClient();
        $this->assertNotEmpty($client);
    }
}
