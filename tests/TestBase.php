<?php

namespace App\Tests;

use App\Utils\Cache\AppCache;
use App\Utils\GoogleAPI\GoogleCalendarEvents;
use App\Utils\GoogleAPI\GoogleCalendarResources;
use App\Utils\GoogleAPI\GoogleCalendarUsers;
use App\Utils\GoogleAPI\GoogleCalendarWatchEvents;
use Carbon\Carbon;
use Google_Service_Directory_CalendarResource;
use Google_Service_Directory_CalendarResources;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Google_Service_Directory_Users;
use Google_Service_Directory_User;
use Google_Service_Directory_UserName;
use Google_Service_Calendar_Channel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Google_Service_Calendar_Events;
use Google_Service_Calendar_Event;

use App\Tests\Mocks\Google\GoogleServiceDirectoryResourceUsers;
use App\Tests\Mocks\Google\GoogleServiceDirectoryResourceResourcesCalendars;
use App\Tests\Mocks\Google\GoogleServiceCalendarResourceEvents;
use App\Tests\Mocks\Google\GoogleServiceCalendarResourceChannels;

class TestBase extends WebTestCase
{
    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var AppCache
     */
    protected $cache;

    /**
     * @var Google_Service_Directory
     */
    protected $serviceDirectory;

    /**
     * @var Google_Service_Calendar
     */
    protected $serviceCalendar;

    /**
     * @var GoogleCalendarResources
     */
    protected $googleCalendarResources;

    /**
     * @var GoogleCalendarUsers
     */
    protected $googleCalendarUsers;

    /**
     * @var GoogleCalendarEvents
     */
    protected $googleCalendarEvents;

    /**
     * @var GoogleCalendarWatchEvents
     */
    protected $googleCalendarWatchEvents;

    /**
     * @var array
     */
    protected $resourcesArray = [];

    /**
     * @var array
     */
    protected $usersArray = [];

    /**
     * @var array
     */
    protected $eventsArray = [];

    /**
     * @var array
     */
    protected $channel = [];

    /**
     * @var GoogleServiceCalendarResourceEvents
     */
    protected $resourceEvents;

    /**
     * @var GoogleServiceDirectoryResourceResourcesCalendars
     */
    protected $resourcesCalendars;

    /**
     * @var KernelBrowser
     */
    protected $client;

    const DEFAULT_CUSTOMER = 'my_customer';

    private function boot()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $container = self::$container;
    }

    public function getService(string $service)
    {
        return self::$container->get($service);
    }

    public function setUp()
    {
        $this->boot();

        $this->parameterBag = $this->getService('parameter_bag');
        $this->client = static::createClient(['debug' => false]);

        $this->initRedis();
        $this->initCache();
        $this->initServiceDirectory();
        $this->initServiceCalendar();

        parent::setUp();
    }

    private function initCache()
    {
        $this->cache = $this->getService('app.cache');
    }

    private function initRedis()
    {
        $this->redis = $this->getMockBuilder("Redis")
            ->disableOriginalConstructor()
            ->getMock();

        $this->redis->expects($this->any())
            ->method("publish")
            ->will($this->returnCallback(function () {
            }));
    }

    private function initServiceDirectory()
    {
        $this->resourcesCalendars = new GoogleServiceDirectoryResourceResourcesCalendars;
        $this->resourcesCalendars->initResources();

        $this->serviceDirectory = $this->getMockBuilder("Google_Service_Directory")
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function initServiceCalendar()
    {
        $this->resourceEvents = new GoogleServiceCalendarResourceEvents;
        $this->resourceEvents->reInitEvents();

        $this->serviceCalendar = $this->getMockBuilder("Google_Service_Calendar")
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceCalendar->events = $this->getMockBuilder('Google_Service_Calendar_Resource_Events')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function initResources()
    {
        $this->resourcesArray = $this->resourcesCalendars->getResources();

        $resources = new Google_Service_Directory_CalendarResources();
        $resources->setItems($this->resourcesArray);

        $this->serviceDirectory->resources_calendars =
            $this->getMockBuilder('Google_Service_Directory_Resource_ResourcesCalendars')
                ->disableOriginalConstructor()
                ->getMock();
        $this->serviceDirectory
            ->resources_calendars
            ->expects($this->any())
            ->method("listResourcesCalendars")
            ->will($this->returnValue($resources));

        $this->googleCalendarResources = new GoogleCalendarResources(
            $this->parameterBag,
            $this->cache,
            $this->serviceDirectory
        );
    }

    protected function initUsers()
    {
        $resourceUsers = new GoogleServiceDirectoryResourceUsers;
        $resourceUsers->initUsers();
        $this->usersArray = $resourceUsers->getUsers();

        $users = new Google_Service_Directory_Users();
        $users->setUsers($this->usersArray);

        $this->serviceDirectory->users = $this->getMockBuilder('Google_Service_Directory_Resource_Users')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceDirectory->users->expects($this->any())
            ->method("listUsers")
            ->will($this->returnValue($users));

        $this->googleCalendarUsers = new GoogleCalendarUsers(
            $this->parameterBag,
            $this->cache,
            $this->serviceDirectory
        );
    }

    protected function initEventList()
    {
        $this->eventsArray = $this->resourceEvents->getEvents();

        $this->serviceCalendar->events->expects($this->any())
            ->method("listEvents")
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $resourceEmail = $args[0];
                $optParams = $args[1];

                return $this->resourceEvents->listEvents($resourceEmail, $optParams);
            }));

        $this->serviceCalendar->events->expects($this->any())
            ->method("get")
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $resourceEmail = $args[0];
                $eventId = $args[1];

                return $this->resourceEvents->get($resourceEmail, $eventId);
            }));

        $this->googleCalendarEvents = new GoogleCalendarEvents(
            $this->parameterBag,
            $this->cache,
            $this->googleCalendarResources,
            $this->googleCalendarUsers,
            $this->serviceCalendar,
            $this->redis
        );
    }

    protected function initEventInsert()
    {
        $this->serviceCalendar->events->expects($this->any())
            ->method("insert")
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $event = $args[1] ?? null;

                return $this->resourceEvents->insert(null, $event);
            }));
    }

    protected function initEventDelete()
    {
        $this->serviceCalendar->events->expects($this->any())
            ->method("delete")
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $eventId = $args[1] ?? null;

                $this->resourceEvents->delete(null, $eventId);
            }));
    }

    protected function initEventUpdate()
    {
        $this->serviceCalendar->events->expects($this->any())
            ->method("update")
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $resourceEmail = $args[0] ?? null;
                $eventId = $args[1] ?? null;
                $event = $args[2] ?? null;

                $this->resourceEvents->update($resourceEmail, $eventId, $event);
            }));
    }

    protected function initEventWatch()
    {
        $this->channel = new Google_Service_Calendar_Channel();
        $this->channel->setResourceId('test01');

        $this->serviceCalendar->events->expects($this->any())
            ->method("watch")
            ->will($this->returnValue($this->channel));

        $this->serviceCalendar->channels = $this->getMockBuilder('Google_Service_Calendar_Resource_Channels')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceCalendar->channels->expects($this->any())
            ->method("stop")
            ->will($this->returnValue(false));

        $this->googleCalendarWatchEvents = new GoogleCalendarWatchEvents(
            $this->parameterBag,
            $this->cache,
            $this->serviceCalendar
        );
    }
}
