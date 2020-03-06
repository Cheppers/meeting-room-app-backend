<?php

namespace App\Utils\GoogleAPI;

use App\Utils\Cache\AbstractCache;
use Carbon\Carbon;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Exception;
use GuzzleHttp\Exception\ConnectException;
use Redis;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class GoogleCalendarEvents extends GoogleApiCalls
{
    const EVENT_STATUS_ACCEPTED = 'accepted';
    const EVENT_STATUS_CANCELLED = 'cancelled';

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var GoogleCalendarResources
     */
    private $googleCalendarResources;

    /**
     * @var GoogleCalendarUsers
     */
    private $googleCalendarUsers;

    /**
     * @var Google_Service_Calendar
     */
    private $googleServiceCalendar;

    public function __construct(
        ParameterBagInterface $parameterBag,
        AbstractCache $appCache,
        GoogleCalendarResources $googleCalendarResources,
        GoogleCalendarUsers $googleCalendarUsers,
        $googleServiceCalendar,
        $redis
    ) {
        parent::__construct($parameterBag, $appCache);
        $this->redis = $redis;
        $this->googleCalendarResources = $googleCalendarResources;
        $this->googleCalendarUsers = $googleCalendarUsers;
        $this->googleServiceCalendar = $googleServiceCalendar;
    }

    public function getResourceEventsById(
        string $resourceId,
        string $from = null,
        string $to = null
    ): array {
        $resourceEmail = $this->googleCalendarResources->getEmailByResourceId($resourceId);
        $cacheKey = 'events.' . $resourceId;

        if (empty($resourceEmail)) {
            return [];
        }

        $events = $this->saveCacheIfNotExists($cacheKey, $resourceEmail, $from, $to);
        $this->initConfirmedCache($resourceId, $events);

        return $events;
    }

    public function refreshResourceEventsById(
        string $resourceId,
        string $from = null,
        string $to = null
    ): array {
        $resourceEmail = $this->googleCalendarResources->getEmailByResourceId($resourceId);
        $cacheKey = 'events.' . $resourceId;

        if (empty($resourceEmail)) {
            return [];
        }

        $events = $this->saveCache($cacheKey, $resourceEmail, $from, $to);
        $this->initConfirmedCache($resourceId, $events);

        return $events;
    }

    public function getResourceEventsByEmail(
        string $resourceEmail,
        string $from = null,
        string $to = null
    ): array {
        $resourceId = $this->googleCalendarResources->getResourceIdByEmail($resourceEmail);
        $cacheKey = 'events.' . $resourceId;

        if (empty($resourceId)) {
            return [];
        }

        return $this->saveCacheIfNotExists($cacheKey, $resourceEmail, $from, $to);
    }

    public function refreshResourceEventsByEmail(
        string $resourceEmail,
        string $from = null,
        string $to = null
    ): array {
        $resourceId = $this->googleCalendarResources->getResourceIdByEmail($resourceEmail);
        $cacheKey = 'events.' . $resourceId;

        if (empty($resourceId)) {
            return [];
        }

        return $this->saveCache($cacheKey, $resourceEmail, $from, $to);
    }

    private function initConfirmedCache($resourceId, $events)
    {
        foreach ($events as $event) {
            $this->initConfirmedKey($resourceId, $event['id']);
        }
    }

    private function saveCache(
        string $cacheKey,
        string $resourceEmail,
        string $from = null,
        string $to = null
    ): array {
        return $this->appCache->save($cacheKey, function () use ($resourceEmail, $from, $to) {
            return $this->listEvents($resourceEmail, $from, $to);
        }, 'PT12H')->get();
    }

    private function saveCacheIfNotExists(
        string $cacheKey,
        string $resourceEmail,
        string $from = null,
        string $to = null
    ): array {
        return $this->appCache->saveIfNotExists($cacheKey, function () use ($resourceEmail, $from, $to) {
            return $this->listEvents($resourceEmail, $from, $to);
        }, 'PT12H')->get();
    }

    public function listEvents(string $resourceEmail, string $from = null, string $to = null): array
    {
        $users = $this->googleCalendarUsers->getAll();

        $optParams = array(
            //'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $from ?? Carbon::now()->format('c'),
            'timeMax' => $to ?? Carbon::parse('tomorrow midnight')->format('c'),
            'timeZone' => 'Europe/Budapest',
        );

        $results = $this->googleServiceCalendar->events->listEvents($resourceEmail, $optParams);
        $eventList = $results->getItems();

        $resourceId = $this->googleCalendarResources->getResourceIdByEmail($resourceEmail);

        $events = [];
        foreach ($eventList as $event) {
            $attendees = [];
            $resourceStatus = null;

            $this->findEventAttendees($event, $users, $resourceEmail, $attendees, $resourceStatus);

            if ($resourceStatus !== self::EVENT_STATUS_ACCEPTED) {
                continue;
            }

            $start = $event->start->dateTime;
            if (empty($start)) {
                $start = $event->start->date;
            }

            $end = $event->end->dateTime;
            if (empty($end)) {
                $end = $event->end->date;
            }

            $events[] = [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'start' => $start,
                'end' => $end,
                'status' => $event->status,
                'resource_status' => $resourceStatus,
                'is_confirmed' => $this->isEventConfirmed($resourceId, $event->getId()),
                'location' => $event->location,
                'attendees' => $attendees,
                'time_min' => Carbon::now()->format('c'),
                'time_max' => Carbon::parse('tomorrow midnight')->format('c'),
            ];
        }

        return $events;
    }

    private function findEventAttendees($event, $users, $resourceEmail, &$attendees, &$resourceStatus)
    {
        if ($event->organizer) {
            $organizerEmail = $event->organizer->email;
            $attendees[$organizerEmail] =
                (isset($users[$organizerEmail]) ? $users[$organizerEmail] : ['email' => $organizerEmail])
                    + ['response_status' => self::EVENT_STATUS_ACCEPTED,];
        }

        foreach ($event->attendees as $attendee) {
            if ($attendee->email === $resourceEmail) {
                $resourceStatus = $attendee->responseStatus;
                continue;
            }

            if (isset($users[$attendee->email])) {
                $attendees[$attendee->email] =
                    $users[$attendee->email] + ['response_status' => $attendee->responseStatus,];
                continue;
            }

            $attendees[$attendee->email] =
                ['email' => $attendee->email, 'response_status' => $attendee->responseStatus,];
        }

        $attendees = array_values($attendees);
    }

    public function insert(
        string $resourceEmail,
        string $summary,
        string $startTime,
        string $endTime
    ) {
        $resourceId = $this->googleCalendarResources->getResourceIdByEmail($resourceEmail);
        $eventCollision = $this->checkEventConflict($resourceId, $resourceEmail, $startTime, $endTime);

        if (count($eventCollision)) {
            throw new GoogleApiCalendarInsertException('Event conflict');
        }

        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            //'location' => '800 Howard St., San Francisco, CA 94103',
            //'description' => 'A chance to hear more about Google\'s developer products.',
            'start' => [
                'dateTime' => $startTime, // '2019-09-08T09:00:00'
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
                ['email' => $resourceEmail]
            ],
        ]);

        $savedEvent = $this->googleServiceCalendar->events->insert($this->parameterBag->get('calendar.user'), $event);
        $eventId = $savedEvent->getId();

        $this->confirmEvent($resourceId, $eventId);

        return $savedEvent;
    }

    public function checkEventConflict(
        string $resourceId,
        string $resourceEmail,
        string $startTime,
        string $endTime
    ): array {
        $cacheKey = 'events.' . $resourceId;

        $events = $this->saveCacheIfNotExists($cacheKey, $resourceEmail);

        $conflicts = [];
        foreach ($events as $event) {
            if (Carbon::parse($event['start']) <= Carbon::parse($endTime)
                && Carbon::parse($startTime) <= Carbon::parse($event['end'])
            ) {
                $conflicts[] = $event;
            }
        }

        return $conflicts;
    }

    public function delete(string $eventId): void
    {
        $this->googleServiceCalendar->events->delete($this->parameterBag->get('calendar.user'), $eventId);
    }

    public function cancel(string $resourceEmail, string $eventId): void
    {
        $event = $this->googleServiceCalendar->events->get($resourceEmail, $eventId);

        $event = new Google_Service_Calendar_Event([
            'summary' => $event->getSummary(),
            'status' => self::EVENT_STATUS_CANCELLED,
            'start' => [
                'dateTime' => $event->getStart()->dateTime,
            ],
            'end' => [
                'dateTime' => $event->getEnd()->dateTime,
            ],
        ]);

        $this->googleServiceCalendar->events->update($resourceEmail, $eventId, $event);
    }

    public function refreshToWebsocket(string $resourceId)
    {
        $resourceEvents = $this->refreshResourceEventsById($resourceId);

        $this->redis->publish('ws-' . $resourceId, json_encode([
            'event' => 'calendarEvents',
            'data'  => $resourceEvents,
        ]));

        return $resourceEvents;
    }

    public function initConfirmedKey(string $resourceId, string $eventId): void
    {
        $eventId = $this->getEventID($eventId);

        $this->appCache->saveIfNotExists(
            'event.' . $resourceId . '.' . $eventId . '.confirmed',
            false,
            'PT24H'
        );
    }

    public function confirmEvent(string $resourceId, string $eventId)
    {
        $eventId = $this->getEventID($eventId);

        $value = $this->appCache->save(
            'event.' . $resourceId . '.' . $eventId . '.confirmed',
            true,
            'PT24H'
        );

        return $value->get();
    }

    public function isEventConfirmed(string $resourceId, string $eventId): bool
    {
        $eventId = $this->getEventID($eventId);

        return (bool)$this->appCache
            ->getItem('event.' . $resourceId . '.' . $eventId . '.confirmed')
            ->get();
    }

    public function closeEvent(string $resourceId, string $eventId)
    {
        $resourceEmail = $this->googleCalendarResources->getEmailByResourceId($resourceId);

        $event = $this->googleServiceCalendar->events->get($resourceEmail, $eventId);

        if (Carbon::parse($event->getEnd()->dateTime) < Carbon::now()) {
            return null;
        }

        $event = new Google_Service_Calendar_Event([
            'summary' => $event->getSummary(),
            'start' => [
                'dateTime' => $event->getStart()->dateTime,
            ],
            'end' => [
                'dateTime' => Carbon::now()->format('c'),
            ],
        ]);

        return $this->googleServiceCalendar->events->update($resourceEmail, $eventId, $event);
    }

    public function getEventID(string $id): ?string
    {
        $idParts = explode('_', $id);

        return $idParts[0] ?? null;
    }
}
