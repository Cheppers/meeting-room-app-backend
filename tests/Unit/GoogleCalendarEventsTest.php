<?php

namespace App\Tests\Unit;

use App\Tests\TestBase;
use App\Utils\GoogleAPI\GoogleApiCalendarInsertException;
use Carbon\Carbon;

class GoogleCalendarEventsTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->initResources();
        $this->initUsers();
        $this->initEventList();
        $this->initEventInsert();
        $this->initEventDelete();
        $this->initEventUpdate();
    }

    public function testCheckEventConflict()
    {
        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(10)->setMinute(0)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(10)->setMinute(15)->setSeconds(0)->format('c')
        );
        $this->assertCount(0, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(10)->setMinute(0)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(10)->setMinute(15)->setSeconds(0)->format('c')
        );
        $this->assertCount(0, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(1)->setMinute(5)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(1)->setMinute(10)->setSeconds(0)->format('c')
        );
        $this->assertCount(1, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(1)->setMinute(0)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(1)->setMinute(20)->setSeconds(0)->format('c')
        );
        $this->assertCount(1, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(1)->setMinute(0)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(1)->setMinute(10)->setSeconds(0)->format('c')
        );
        $this->assertCount(1, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(1)->setMinute(10)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(1)->setMinute(20)->setSeconds(0)->format('c')
        );
        $this->assertCount(1, $conflicts);

        $conflicts = $this->googleCalendarEvents->checkEventConflict(
            $this->resourcesArray[0]->getResourceId(),
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::now()->addHours(1)->setMinute(10)->setSeconds(0)->format('c'),
            Carbon::now()->addHours(2)->setMinute(20)->setSeconds(0)->format('c')
        );
        $this->assertCount(2, $conflicts);
    }

    public function testListEvents()
    {
        $events = $this->googleCalendarEvents->listEvents('fake@example.com');
        $this->assertCount(0, $events);
        $events = $this->googleCalendarEvents->listEvents($this->resourcesArray[0]->getResourceEmail());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );


        $events = $this->googleCalendarEvents->listEvents(
            $this->resourcesArray[0]->getResourceEmail(),
            Carbon::parse('tomorrow midnight')->addHours(1)->format('c'),
            Carbon::parse('tomorrow midnight')->addHours(2)->format('c')
        );
        $this->assertCount(1, $events);

        $this->assertCount(3, $events[0]['attendees']);
        $this->assertEquals('organizer@example.com', $events[0]['attendees'][0]['email']);
    }

    public function testListEventsFiltered()
    {
        $events = $this->googleCalendarEvents->getResourceEventsById('123');
        $this->assertCount(0, $events);
        $events = $this->googleCalendarEvents->getResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById('123');
        $this->assertCount(0, $events);
        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[1]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[1]->getResourceEmail()),
            $events
        );

        $events = $this->googleCalendarEvents->getResourceEventsByEmail('fake@example.com');
        $this->assertCount(0, $events);
        $events = $this->googleCalendarEvents->getResourceEventsByEmail($this->resourcesArray[0]->getResourceEmail());

        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsByEmail('fake@example.com');
        $this->assertCount(0, $events);
        $events = $this->googleCalendarEvents
            ->refreshResourceEventsByEmail($this->resourcesArray[1]->getResourceEmail());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[1]->getResourceEmail()),
            $events
        );
    }

    public function testInsertEvents()
    {
        $start = Carbon::now()->addHours(1)->setMinute(20)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $start = Carbon::now()->addHours(1)->setMinute(30)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test1',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()) + 2,
            $events
        );

        $this->expectException(GoogleApiCalendarInsertException::class);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(1)
        );
    }

    public function testDeleteEvents()
    {
        $start = Carbon::now()->addHours(1)->setMinute(20)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()) + 1,
            $events
        );

        $this->googleCalendarEvents->delete(
            'i0'
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );
    }

    public function testCancelEvents()
    {
        $start = Carbon::now()->addHours(1)->setMinute(20)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()) + 1,
            $events
        );

        $this->googleCalendarEvents->cancel(
            $this->resourcesArray[0]->getResourceEmail(),
            'i0'
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()) + 1,
            $events
        );
    }

    public function testCloseEvent()
    {
        $start = Carbon::now()->addHours(1)->setMinute(20)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()) + 1,
            $events
        );

        $this->googleCalendarEvents->closeEvent(
            $this->resourcesArray[0]->getResourceId(),
            'i0'
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );


        $start = Carbon::now()->subHours(1)->setMinute(20)->setSecond(0);
        $this->googleCalendarEvents->insert(
            $this->resourcesArray[0]->getResourceEmail(),
            'Test0',
            $start,
            Carbon::parse($start)->addMinutes(5)
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );

        $this->googleCalendarEvents->closeEvent(
            $this->resourcesArray[0]->getResourceId(),
            'i1'
        );

        $events = $this->googleCalendarEvents->refreshResourceEventsById($this->resourcesArray[0]->getResourceId());
        $this->assertCount(
            $this->resourceEvents->getEventsCount($this->resourcesArray[0]->getResourceEmail()),
            $events
        );
    }

    public function testRefreshToWebSocket()
    {
        $events = $this->googleCalendarEvents->refreshToWebsocket($this->resourcesArray[0]->getResourceId());

        $this->assertIsArray($events);
    }
}
