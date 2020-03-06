<?php

namespace App\Tests\Feature\Api;

use App\Tests\Mocks\Google\GoogleServiceCalendarResourceEvents;
use App\Tests\TestBase;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EventControllerTest extends TestBase
{
    public function testIndex()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $resourceEmail = $this->resourcesCalendars->getResources()[0]->getResourceEmail();

        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($this->resourceEvents->getEventsCount(
            $resourceEmail
        ), $content['data']);

        $this->assertCount(3, $content['data'][0]['attendees']);
        $this->assertEquals('organizer@example.com', $content['data'][0]['attendees'][0]['email']);
    }

    public function testIndexFails()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();


        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertEquals('Google API unavailable', $content['errors'][0]['detail']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testRefresh()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();

        $this->client->request('GET', '/api/event/refresh/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(0, $content);
    }

    public function testRefreshFails()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();


        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('GET', '/api/event/refresh/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('GET', '/api/event/refresh/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertEquals('Google API unavailable', $content['errors'][0]['detail']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testInsert()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $resourceEmail = $this->resourcesCalendars->getResources()[0]->getResourceEmail();
        $eventCount = $this->resourceEvents->getEventsCount($resourceEmail);

        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount, $content['data']);


        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);


        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertArrayHasKey('detail', $content['errors'][0]);
        $this->assertEquals('Event conflict', $content['errors'][0]['detail']);


        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount+1, $content['data']);
    }

    public function testInsertFails()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();


        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testDelete()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $resourceEmail = $this->resourcesCalendars->getResources()[0]->getResourceEmail();
        $eventCount = $this->resourceEvents->getEventsCount($resourceEmail);


        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);
        $eventId = $content['data']['id'];


        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount+1, $content['data']);


        $this->client->request('DELETE', '/api/event/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());


        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount, $content['data']);
    }

    public function testDeleteFails()
    {
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('DELETE', '/api/event/i0', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('DELETE', '/api/event/i0', [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testCancel()
    {
        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $resourceEmail = $this->resourcesCalendars->getResources()[0]->getResourceEmail();
        $eventCount = $this->resourceEvents->getEventsCount($resourceEmail);


        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => Carbon::now()->addHours(1)->setMinute(30),
            'event_length' => 1,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);
        $eventId = $content['data']['id'];


        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount+1, $content['data']);


        $this->client->request('GET', '/api/event/cancel/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(0, $content);


        $events = $this->resourceEvents->getEvents();
        $this->assertEquals('cancelled', $events[$resourceEmail][$eventId]->status);
    }

    public function testCancelFails()
    {
        $resourceId = 'r1';
        $eventId = 'e0';


        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('GET', '/api/event/cancel/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('GET', '/api/event/cancel/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testClose()
    {
        $this->resourceEvents->clearEvents();

        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $resourceEmail = $this->resourcesCalendars->getResources()[0]->getResourceEmail();
        $eventCount = $this->resourceEvents->getEventsCount($resourceEmail);


        $startTime = Carbon::now()->subMinutes(5)->setSecond(0);
        $endTime = Carbon::parse($startTime)->addMinutes(10);

        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => $startTime,
            'event_length' => 10,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);
        $eventId = $content['data']['id'];


        $this->client->request('GET', '/api/event/' . $resourceId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount($eventCount+1, $content['data']);
        $events = $this->resourceEvents->getEvents();
        $originalEndTime = $events[$resourceEmail][$eventId]->end->dateTime;
        $this->assertEquals($endTime->format('c'), $originalEndTime);


        $this->client->request('GET', '/api/event/close/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());


        $events = $this->resourceEvents->getEvents();
        $currentEndTime = $events[$resourceEmail][$eventId]->end->dateTime;
        $this->assertTrue($currentEndTime <= $originalEndTime);
    }

    public function testCloseFails()
    {
        $this->resourceEvents->clearEvents();

        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $startTime = Carbon::now()->subMinutes(5)->setSecond(0);

        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => $startTime,
            'event_length' => 10,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);
        $eventId = $content['data']['id'];


        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = true;
        $this->client->request('GET', '/api/event/close/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwGoogleServiceException = false;


        GoogleServiceCalendarResourceEvents::$throwConnectException = true;
        $this->client->request('GET', '/api/event/close/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        GoogleServiceCalendarResourceEvents::$throwConnectException = false;
    }

    public function testConfirm()
    {
        $this->resourceEvents->clearEvents();

        $resourceId = $this->resourcesCalendars->getResources()[0]->getResourceId();
        $startTime = Carbon::now()->subMinutes(5)->setSecond(0);

        $this->client->request('POST', '/api/event/' . $resourceId, [
            'summary' => 'Testing',
            'start_time' => $startTime,
            'event_length' => 10,
        ], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertCount(30, $content['data']);
        $eventId = $content['data']['id'];


        $this->client->request('GET', '/api/event/confirm/' . $resourceId . '/' . $eventId, [], [], [
            'HTTP_Authorization' => 'Bearer test'
        ]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $this->assertTrue($content['value']);
    }
}
