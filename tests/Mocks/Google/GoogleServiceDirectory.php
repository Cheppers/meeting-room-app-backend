<?php

namespace App\Tests\Mocks\Google;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class GoogleServiceDirectory
{
    public $resources_calendars;
    public $users;

    public function __construct()
    {
        $this->resources_calendars = new GoogleServiceDirectoryResourceResourcesCalendars;
        $this->resources_calendars->initResources();

        $this->users = new GoogleServiceDirectoryResourceUsers;
        $this->users->initUsers();
    }
}
