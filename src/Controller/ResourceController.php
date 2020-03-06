<?php

namespace App\Controller;

use App\Utils\GoogleAPI\GoogleCalendarResources;
use Google_Service_Exception;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class ResourceController extends BaseController
{
    /**
     * @var GoogleCalendarResources
     */
    private $googleCalendarResources;

    public function __construct(GoogleCalendarResources $googleResources)
    {
        $this->googleCalendarResources = $googleResources;
    }

    /**
     * List resources
     *
     * @Route("/api/resource", methods={"GET"})
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the list of resources"
     * )
     */
    public function index()
    {
        $allResources = [];

        try {
            $allResources = $this->googleCalendarResources->getAll();
        } catch (ConnectException $connectException) {
            return $this->errorResponse(Response::HTTP_GATEWAY_TIMEOUT, 'Google API unavailable');
        } catch (Google_Service_Exception $googleServiceException) {
            $errors = $googleServiceException->getErrors();

            return $this->errorResponse($googleServiceException->getCode(), $errors[0]['message'] ?? null);
        }

        return $this->json([
            'data' => $allResources,
        ]);
    }
}
