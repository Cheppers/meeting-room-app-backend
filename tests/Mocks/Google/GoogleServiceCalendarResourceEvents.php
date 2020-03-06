<?php

namespace App\Tests\Mocks\Google;

use App\Utils\GoogleAPI\GoogleCalendarEvents;
use Carbon\Carbon;
use Google_Service_Calendar_Channel;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventOrganizer;
use Google_Service_Calendar_Events;
use Google_Service_Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleServiceCalendarResourceEvents
{
    private $channel;
    private $resources;
    public $resourcesArray = [];
    public static $eventsCount = [];
    public static $eventsArray = [];
    private $eventKey = 0;

    public static $throwConnectException = false;
    public static $throwGoogleServiceException = false;

    public function initEvents(): void
    {
        $this->resources = new GoogleServiceDirectoryResourceResourcesCalendars;
        $this->resources->initResources();
        $this->resourcesArray = $this->resources->getResources();

        $id = 0;

        for ($i = 0; $i < count($this->resourcesArray); $i++) {
            $resourceEmail = $this->resourcesArray[$i]->getResourceEmail();

            self::$eventsArray[$resourceEmail] = [];

            foreach (range(-2, 5) as $hour) {
                $startTime = Carbon::now()->addHours($hour)->setMinute(0)->setSeconds(0)->format('c');
                $endTime = Carbon::now()->addHours($hour)->setMinute(15)->setSeconds(0)->format('c');

                $event = new Google_Service_Calendar_Event([
                    'id' => 'e' . $id,
                    'summary' => 'Event' . $id,
                    'start' => [
                        'dateTime' => $startTime,
                        'timeZone' => 'Europe/Budapest',
                    ],
                    'end' => [
                        'dateTime' => $endTime,
                        'timeZone' => 'Europe/Budapest',
                    ],
                    'recurrence' => [
                        'RRULE:FREQ=DAILY;COUNT=1'
                    ],
                    'reminders' => [
                        'useDefault' => false,
                    ],
                    'attendees' => [
                        ['email' => $resourceEmail, 'responseStatus' => GoogleCalendarEvents::EVENT_STATUS_ACCEPTED,],
                        ['email' => 'user1@example.com',],
                        ['email' => 'outsider@example.com',],
                    ],
                ]);
                $event->setOrganizer(new Google_Service_Calendar_EventOrganizer([
                    'email' => 'organizer@example.com',
                ]));

                if ($startTime < Carbon::parse('tomorrow midnight')->format('c')
                    && Carbon::now()->format('c') < $endTime) {
                    if (!isset(self::$eventsCount[$resourceEmail])) {
                        self::$eventsCount[$resourceEmail] = 0;
                    }

                    self::$eventsCount[$resourceEmail]++;
                }

                $id++;

                self::$eventsArray[$resourceEmail][$event->getId()] = $event;
            }

            $event = new Google_Service_Calendar_Event([
                'id' => 'e' . $id,
                'summary' => 'Event' . $id,
                'start' => [
                    'date' => Carbon::parse('tomorrow midnight'),
                    'timeZone' => 'Europe/Budapest',
                ],
                'end' => [
                    'date' => Carbon::parse('tomorrow midnight')->addDays(1),
                    'timeZone' => 'Europe/Budapest',
                ],
                'recurrence' => [
                    'RRULE:FREQ=DAILY;COUNT=1'
                ],
                'reminders' => [
                    'useDefault' => false,
                ],
                'attendees' => [
                    ['email' => $resourceEmail, 'responseStatus' => GoogleCalendarEvents::EVENT_STATUS_ACCEPTED,],
                    ['email' => 'user1@example.com',],
                    ['email' => 'outsider@example.com',],
                ],
            ]);
            $event->setOrganizer(new Google_Service_Calendar_EventOrganizer([
                'email' => 'organizer@example.com',
            ]));

            self::$eventsArray[$resourceEmail][$event->getId()] = $event;
        }
    }

    public function reInitEvents(): void
    {
        $this->clearEvents();
        $this->initEvents();
    }

    public function clearEvents(): void
    {
        self::$eventsArray = self::$eventsCount = [];
    }

    public function getEventsCount($resourceEmail):? int
    {
        return self::$eventsCount[$resourceEmail] ?? null;
    }

    public function listEvents(string $resourceEmail, array $optParams): Google_Service_Calendar_Events
    {
        $this->initExceptions();

        $from = Carbon::now()->format('c');
        if (isset($optParams['timeMin'])) {
            $from = Carbon::parse($optParams['timeMin'])->format('c');
        }

        $to = Carbon::parse('tomorrow midnight')->format('c');
        if (isset($optParams['timeMax'])) {
            $to = Carbon::parse($optParams['timeMax'])->format('c');
        }

        $allEvents = new Google_Service_Calendar_Events;

        if (!isset(self::$eventsArray[$resourceEmail])) {
            return $allEvents;
        }

        $filteredEvents = [];
        foreach (self::$eventsArray[$resourceEmail] as $event) {
            $start = $event->start->dateTime;
            $end = $event->end->dateTime;

            if (empty($start)) {
                $start = $event->start->date;
            }

            if (empty($end)) {
                $end = $event->end->date;
            }

            $start = Carbon::parse($start)->format('c');
            $end = Carbon::parse($end)->format('c');

            if ($start < $to
                && $from < $end
            ) {
                $filteredEvents[] = $event;
            }
        }

        $allEvents->setItems($filteredEvents);

        return $allEvents;
    }

    public function getEvents(): array
    {
        return self::$eventsArray;
    }

    public function get(string $resourceEmail, string $eventId): Google_Service_Calendar_Event
    {
        return self::$eventsArray[$resourceEmail][$eventId];
    }

    public function insert($calendarUser, $event): Google_Service_Calendar_Event
    {
        $calendarUser;
        $this->initExceptions();

        if ($event) {
            $event->setId('i'.$this->eventKey);
        }

        $this->eventKey++;

        $resourceEmail = $event->attendees[0]->email;
        $event->attendees[0]->responseStatus = GoogleCalendarEvents::EVENT_STATUS_ACCEPTED;
        self::$eventsArray[$resourceEmail][$event->getId()] = $event;

        return $event;
    }

    public function delete($calendarUser, $eventId)
    {
        $calendarUser;
        $this->initExceptions();

        foreach (self::$eventsArray as $resourceEmail => $events) {
            foreach ($events as $event) {
                if ($event->getId() === $eventId) {
                    unset(self::$eventsArray[$resourceEmail][$event->getId()]);
                }
            }
        }
    }

    public function update($resourceEmail, $eventId, $event)
    {
        $this->initExceptions();

        if (isset($event->status)) {
            self::$eventsArray[$resourceEmail][$eventId]->status = $event->status;
        }

        if (isset($event->end->dateTime)) {
            self::$eventsArray[$resourceEmail][$eventId]->end->dateTime = $event->end->dateTime;
        }
    }

    public function watch(): Google_Service_Calendar_Channel
    {
        $this->initExceptions();

        $this->channel = new Google_Service_Calendar_Channel();
        $this->channel->setResourceId('test01');

        return $this->channel;
    }

    private function initExceptions()
    {
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
    }
}
