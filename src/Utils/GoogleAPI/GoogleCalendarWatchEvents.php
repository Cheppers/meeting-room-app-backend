<?php

namespace App\Utils\GoogleAPI;

use App\Utils\Cache\AbstractCache;
use Google_Service_Calendar;
use Google_Service_Calendar_Channel;
use Google_Service_Exception;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleCalendarWatchEvents extends GoogleApiCalls
{
    /**
     * @var Google_Service_Calendar
     */
    private $googleServiceCalendar;

    public function __construct(
        ParameterBagInterface $parameterBag,
        AbstractCache $appCache,
        $googleServiceCalendar
    ) {
        parent::__construct($parameterBag, $appCache);
        $this->googleServiceCalendar = $googleServiceCalendar;
    }

    /**
     * @param string $resourceEmail
     * @param string $resourceId
     * @param bool $autoStop
     */
    public function watch(
        string $resourceEmail,
        string $resourceId
    ): array {
        $this->stop($resourceId);

        $channelId = Uuid::uuid4();

        $serviceChannel = new Google_Service_Calendar_Channel([
            "id" => $channelId->toString(),
            "type" => "web_hook",
            "address" => $this->parameterBag->get('public.url') . '/api/event/refresh/' . $resourceId,
            //"expiration" => Carbon::now()->addMinutes(10)->timestamp * 1000,
            "expiration" => Carbon::parse('tomorrow midnight')->timestamp * 1000,
        ]);

        $currentChannel = null;
        $errors = [];
        try {
            $currentChannel = $this->googleServiceCalendar->events->watch($resourceEmail, $serviceChannel);
        } catch (Google_Service_Exception $googleServiceException) { // @codeCoverageIgnore
            $errors = $googleServiceException->getErrors(); // @codeCoverageIgnore
        }

        $this->appCache->save('channel.' . $resourceId, [
            'channel_id' => $channelId->toString(),
            'channel_resource_id' => $currentChannel->resourceId ?? null,
        ]);

        return [
            'channel_id' => $channelId->toString(),
            'channel_resource_id' => $currentChannel->resourceId ?? null,
            'expiration' => Carbon::parse('tomorrow midnight'),
            'errors' => $errors,
        ];
    }

    public function list($resourceId): ?array
    {
        return $this->appCache->getItem('channel.' . $resourceId)->get();
    }

    public function stop($resourceId): bool
    {
        $resourceData = $this->appCache->getItem('channel.' . $resourceId)->get();

        if (!is_array($resourceData)) {
            return false;
        }

        $channelId = $resourceData['channel_id'] ?? null;
        $resourceId = $resourceData['channel_resource_id'] ?? null;

        if (!$channelId || !$resourceId) {
            return false; // @codeCoverageIgnore
        }

        $serviceChannel = new Google_Service_Calendar_Channel([
            "id" => $channelId,
            "resourceId" => $resourceId,
        ]);

        try {
            $this->googleServiceCalendar->channels->stop($serviceChannel);
        } catch (Google_Service_Exception $googleServiceException) { // @codeCoverageIgnore
        }

        return true;
    }
}
