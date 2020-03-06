<?php

namespace App\Controller;

use App\Utils\GoogleAPI\GoogleCalendarResources;
use App\Utils\GoogleAPI\GoogleCalendarWatchEvents;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class WatchEventsController extends BaseController
{
    /**
     * @var GoogleCalendarResources
     */
    private $calendarResources;

    /**
     * @var GoogleCalendarWatchEvents
     */
    private $watchEvents;

    public function __construct(
        GoogleCalendarResources $calendarResources,
        GoogleCalendarWatchEvents $watchEvents
    ) {
        $this->calendarResources = $calendarResources;
        $this->watchEvents = $watchEvents;
    }

    /**
     * Subscribe webhooks to Google
     *
     * @Route("/api/watch", methods={"GET"})
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the list of resources & channels"
     * )
     */
    public function watch()
    {
        $response = [];
        $resources = $this->calendarResources->getAll();

        foreach ($resources as $resource) {
            $status = $this->watchEvents->watch($resource['resource_email'], $resource['resource_id']);

            $response[] = [
                'resource_id' => $resource['resource_id'],
                'resource_name' => $resource['resource_id'],
                'channel_id' => $status['channel_id'],
                'channel_resource_id' => $status['channel_resource_id'],
                'expiration' => $status['expiration'],
                'errors' => $status['errors'],
            ];
        }

        return $this->json($response);
    }
}
