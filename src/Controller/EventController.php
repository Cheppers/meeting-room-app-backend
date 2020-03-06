<?php

namespace App\Controller;

use App\Utils\GoogleAPI\GoogleApiCalendarInsertException;
use Carbon\Carbon;
use App\Utils\GoogleAPI\GoogleCalendarEvents;
use App\Utils\GoogleAPI\GoogleCalendarResources;
use Google_Service_Calendar_Event;
use Google_Service_Exception;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class EventController extends BaseController
{
    /**
     * @var GoogleCalendarResources
     */
    private $googleCalendarResources;

    /**
     * @var GoogleCalendarEvents
     */
    private $googleCalendarEvents;

    public function __construct(
        GoogleCalendarResources $googleResources,
        GoogleCalendarEvents $googleCalendarEvents
    ) {
        $this->googleCalendarResources = $googleResources;
        $this->googleCalendarEvents = $googleCalendarEvents;
    }

    /**
     * List events for given resource
     *
     * @Route("/api/event/{resourceId}", methods={"GET"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the resource events"
     * )
     */
    public function index(string $resourceId)
    {
        $resourceEvents = [];

        try {
            $resourceEvents = $this->googleCalendarEvents->refreshResourceEventsById($resourceId);
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([
            'data' => $resourceEvents,
        ]);
    }

    /**
     * Refresh event information to websocket
     *
     * @Route("/api/event/refresh/{resourceId}", methods={"GET", "POST"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns empty json"
     * )
     */
    public function refresh(string $resourceId)
    {
        try {
            $this->googleCalendarEvents->refreshToWebsocket($resourceId);
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([]);
    }

    /**
     * Inserts an event to the given resource calendar.
     *
     * @Route("/api/event/{resourceId}", methods={"POST"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Parameter(
     *      name="summary",
     *      type="string",
     *      description="Summary of the inserted event",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(
     *          type="string"
     *      )
     * )
     *
     * @SWG\Parameter(
     *      name="start_time",
     *      type="string",
     *      description="Start time of the inserted event",
     *      in="body",
     *      required=false,
     *      @SWG\Schema(
     *          type="string"
     *      )
     * )
     *
     * @SWG\Parameter(
     *      name="end_time",
     *      type="string",
     *      description="End time of the inserted event",
     *      in="body",
     *      required=false,
     *      @SWG\Schema(
     *          type="string"
     *      )
     * )
     *
     * @SWG\Parameter(
     *      name="event_length",
     *      type="integer",
     *      description="Event length (in minutes)",
     *      in="body",
     *      required=false,
     *      @SWG\Schema(
     *          type="integer",
     *          minimum="0"
     *      )
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the inserted event"
     * )
     */
    public function insert(string $resourceId, Request $request)
    {
        $summary = $request->request->get('summary', null);
        $startTime = $request->request->get('start_time', Carbon::now()->format('c'));
        $eventLength = $request->request->get('event_length', 0);
        $endTime = $request->request->get(
            'end_time',
            $eventLength ? null : Carbon::now()->addMinutes(15)->format('c')
        );

        $startTime = Carbon::parse($startTime)->format('c');
        $endTime = $endTime ? Carbon::parse($endTime)->format('c') : null;

        if (empty($endTime)) {
            $endTime = Carbon::parse($startTime)->addMinutes($eventLength)->format('c');
        }

        $resourceEmail = null;
        $response = null;
        $errorResponse = null;

        try {
            $resourceEmail = $this->googleCalendarResources->getEmailByResourceId($resourceId);

            if ($resourceEmail) {
                $response = $this->googleCalendarEvents->insert($resourceEmail, $summary, $startTime, $endTime);
            }

            return $this->json([
                'data' => $response,
            ]);
        } catch (GoogleApiCalendarInsertException $calendarInsertException) {
            $errorResponse = $this->errorResponse(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $calendarInsertException->getMessage()
            );
        } catch (ConnectException $connectException) {
            $errorResponse = $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            $errorResponse = $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $errorResponse;
    }

    /**
     * Delete event
     *
     * @Route("/api/event/{eventId}", methods={"DELETE"})
     *
     * @SWG\Parameter(
     *      name="eventId",
     *      type="string",
     *      description="The id of the event",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns empty json"
     * )
     */
    public function delete(string $eventId)
    {
        try {
            $this->googleCalendarEvents->delete($eventId);
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([]);
    }

    /**
     * Cancel event
     *
     * @Route("/api/event/cancel/{resourceId}/{eventId}", methods={"GET"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Parameter(
     *      name="eventId",
     *      type="string",
     *      description="The id of the event",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns empty json"
     * )
     */
    public function cancel(string $resourceId, string $eventId)
    {
        try {
            $resourceEmail = $this->googleCalendarResources->getEmailByResourceId($resourceId);

            if ($resourceEmail) {
                $this->googleCalendarEvents->cancel($resourceEmail, $eventId);
            }
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([]);
    }

    /**
     * Close event (update event end-time)
     *
     * @Route("/api/event/close/{resourceId}/{eventId}", methods={"GET"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Parameter(
     *      name="eventId",
     *      type="string",
     *      description="The id of the event",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns modified event"
     * )
     */
    public function close($resourceId, $eventId)
    {
        $response = null;

        try {
            $response = $this->googleCalendarEvents->closeEvent($resourceId, $eventId);
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([
            'data' => $response,
        ]);
    }

    /**
     * Confirm event (to avoid automatic delete)
     *
     * @Route("/api/event/confirm/{resourceId}/{eventId}", methods={"GET"})
     *
     * @SWG\Parameter(
     *      name="resourceId",
     *      type="string",
     *      description="The resource id of room's calendar",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Parameter(
     *      name="eventId",
     *      type="string",
     *      description="The id of the event",
     *      in="path",
     *      required=true
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the confirm value from Redis"
     * )
     */
    public function confirm($resourceId, $eventId)
    {
        $value = $this->googleCalendarEvents->confirmEvent($resourceId, $eventId);

        return $this->json(['value' => $value]);
    }
}
