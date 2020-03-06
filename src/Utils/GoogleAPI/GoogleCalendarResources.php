<?php

namespace App\Utils\GoogleAPI;

use App\Utils\Cache\AbstractCache;
use Google_Service_Directory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleCalendarResources extends GoogleApiCalls
{
    /**
     * @var Google_Service_Directory
     */
    private $serviceDirectory;

    public function __construct(
        ParameterBagInterface $parameterBag,
        AbstractCache $appCache,
        $serviceDirectory
    ) {
        parent::__construct($parameterBag, $appCache);
        $this->serviceDirectory = $serviceDirectory;
    }

    public function getAll()
    {
        $cacheKey = 'rooms';

        return $this->appCache->saveIfNotExists($cacheKey, function () {
            return $this->listResources();
        }, 'PT12H')->get();
    }

    public function getByResourceId($resourceId)
    {
        $resources = $this->getAll();

        foreach ($resources as $resource) {
            if ($resource['resource_id'] === $resourceId) {
                return $resource;
            }
        }

        return [];
    }

    public function getByResourceEmail($resourceEmail)
    {
        $resources = $this->getAll();

        foreach ($resources as $resource) {
            if ($resource['resource_email'] === $resourceEmail) {
                return $resource;
            }
        }

        return [];
    }

    public function getEmailByResourceId($resourceId)
    {
        $resource = $this->getByResourceId($resourceId);
        return $resource['resource_email'] ?? null;
    }

    public function getResourceIdByEmail($resourceEmail)
    {
        $resource = $this->getByResourceEmail($resourceEmail);
        return $resource['resource_id'] ?? null;
    }

    private function listResources($type = ['room'])
    {
        $resources = [];

        $resourceList = $this->serviceDirectory->resources_calendars->listResourcesCalendars(self::DEFAULT_CUSTOMER);

        foreach ($resourceList as $resource) {
            if (!in_array($resource->resourceType, $type)) {
                continue;
            }

            $resources[] = [
                'building_id' => $resource->buildingId,
                'resource_id' => $resource->resourceId,
                'resource_name' => $resource->generatedResourceName,
                'resource_email' => $resource->resourceEmail,
                'resource_type' => $resource->resourceType,
            ];
        }

        return $resources;
    }
}
