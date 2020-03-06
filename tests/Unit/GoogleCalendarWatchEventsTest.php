<?php

namespace App\Tests\Unit;

use App\Tests\TestBase;

class GoogleCalendarWatchEventsTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->initResources();
        $this->initEventWatch();
    }

    public function testWatch()
    {
        $watch = $this->googleCalendarWatchEvents->watch(
            $this->resourcesArray[0]->getResourceEmail(),
            $this->resourcesArray[0]->getResourceId()
        );

        $this->assertCount(4, $watch);
        $this->assertEquals($this->channel->getResourceId(), $watch['channel_resource_id']);
        $this->assertEquals([], $watch['errors']);

        $list = $this->googleCalendarWatchEvents->list(
            $this->resourcesArray[0]->getResourceId()
        );

        $this->assertCount(2, $list);
    }

    public function testStop()
    {
        $this->googleCalendarWatchEvents->watch(
            $this->resourcesArray[0]->getResourceEmail(),
            $this->resourcesArray[0]->getResourceId()
        );

        $stop = $this->googleCalendarWatchEvents->stop(
            $this->resourcesArray[0]->getResourceId()
        );
        $this->assertTrue($stop);

        $stop = $this->googleCalendarWatchEvents->stop(
            null
        );
        $this->assertFalse($stop);
    }
}
