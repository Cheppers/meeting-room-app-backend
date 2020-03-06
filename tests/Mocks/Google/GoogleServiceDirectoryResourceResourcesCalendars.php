<?php

namespace App\Tests\Mocks\Google;

use Google_Service_Directory_CalendarResource;
use Google_Service_Directory_CalendarResources;
use Google_Service_Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleServiceDirectoryResourceResourcesCalendars
{
    private $resourcesArray = [];

    public static $throwConnectException = false;
    public static $throwGoogleServiceException = false;

    public function initResources(): void
    {
        foreach (range(1, 6) as $id) {
            $resource = new Google_Service_Directory_CalendarResource();
            $resource->setBuildingId('Building' . $id);
            $resource->setResourceType($id === 6 ? 'device' : 'room');
            $resource->setResourceId('r' . $id);
            $resource->setGeneratedResourceName('Room' . $id);
            $resource->setResourceEmail('r' . $id . '@example.com');

            $this->resourcesArray[] = $resource;
        }
    }

    public function getResources(): array
    {
        return $this->resourcesArray;
    }

    public function listResourcesCalendars(): Google_Service_Directory_CalendarResources
    {
        $resources = new Google_Service_Directory_CalendarResources();
        $resources->setItems($this->getResources());

        if (self::$throwConnectException) {
            throw new ConnectException('', new Request('POST', '/'));
        }

        if (self::$throwGoogleServiceException) {
            throw new Google_Service_Exception(
                'Service unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                null,
                [[
                    'message' => 'Test error'
                ]]
            );
        }

        return $resources;
    }
}
