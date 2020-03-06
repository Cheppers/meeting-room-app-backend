<?php

namespace App\Tests\Mocks\Google;

class GoogleServiceCalendar
{
    public $events;
    public $channels;

    public function __construct()
    {
        $this->events = new GoogleServiceCalendarResourceEvents;

        $this->channels = new GoogleServiceCalendarResourceChannels;
        $this->channels->initChannels();
    }
}
