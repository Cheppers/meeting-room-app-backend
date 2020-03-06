<?php

namespace App\Tests\Unit;

use App\Tests\TestBase;

class GoogleCalendarResourcesTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->initResources();
    }

    public function testGetAll()
    {
        $resources = $this->googleCalendarResources->getAll(self::DEFAULT_CUSTOMER);
        $this->assertCount(5, $resources);
        $this->assertCount(5, $this->cache->getItem('rooms')->get());

        for ($i = 0; $i < count($resources); $i++) {
            $this->resourceItemTest($resources[$i], $i);
        }
    }

    public function testGetByResourceId()
    {
        $resource = $this->googleCalendarResources->getByResourceId($this->resourcesArray[0]->getResourceId());
        $this->resourceItemTest($resource, 0);

        $resource = $this->googleCalendarResources
            ->getByResourceId('_' . $this->resourcesArray[0]
            ->getResourceId());
        $this->assertEmpty($resource);
    }

    public function testGetByResourceEmail()
    {
        $resource = $this->googleCalendarResources
            ->getByResourceEmail($this->resourcesArray[0]
            ->getResourceEmail());
        $this->resourceItemTest($resource, 0);

        $resource = $this->googleCalendarResources
            ->getByResourceEmail('_' . $this->resourcesArray[0]
            ->getResourceEmail());
        $this->assertEmpty($resource);
    }

    public function testGetEmailByResourceId()
    {
        $email = $this->googleCalendarResources->getEmailByResourceId($this->resourcesArray[0]->getResourceId());
        $this->assertEquals($this->resourcesArray[0]->getResourceEmail(), $email);
    }

    public function testGetResourceIdByEmail()
    {
        $resourceId = $this->googleCalendarResources
            ->getResourceIdByEmail($this->resourcesArray[0]
            ->getResourceEmail());
        $this->assertEquals($this->resourcesArray[0]->getResourceId(), $resourceId);
    }

    private function resourceItemTest($resource, $originalIndex)
    {
        $this->assertNotEmpty($resource);
        $this->assertCount(5, $resource);

        $this->assertArrayHasKey('building_id', $resource);
        $this->assertEquals($this->resourcesArray[$originalIndex]->getBuildingId(), $resource['building_id']);

        $this->assertArrayHasKey('resource_id', $resource);
        $this->assertEquals($this->resourcesArray[$originalIndex]->getResourceId(), $resource['resource_id']);

        $this->assertArrayHasKey('resource_name', $resource);
        $this->assertEquals(
            $this->resourcesArray[$originalIndex]->getGeneratedResourceName(),
            $resource['resource_name']
        );

        $this->assertArrayHasKey('resource_email', $resource);
        $this->assertEquals($this->resourcesArray[$originalIndex]->getResourceEmail(), $resource['resource_email']);

        $this->assertArrayHasKey('resource_type', $resource);
        $this->assertEquals($this->resourcesArray[$originalIndex]->getResourceType(), $resource['resource_type']);
    }
}
