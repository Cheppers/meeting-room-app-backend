<?php

namespace App\Utils\GoogleAPI;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Directory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleClient
{
    /**
     * @var Google_Client
     */
    private $googleClient = null;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function initClient(): void
    {
        $this->googleClient = new Google_Client();

        $this->googleClient->setApplicationName('Google Calendar API Tests');

        $this->googleClient->setScopes([
            Google_Service_Directory::ADMIN_DIRECTORY_RESOURCE_CALENDAR,
            Google_Service_Directory::ADMIN_DIRECTORY_USER_READONLY,
            Google_Service_Calendar::CALENDAR,
        ]);

        $this->googleClient->setAuthConfig([
            "type"                          => $this->parameterBag->get('google.auth.type'),
            "project_id"                    => $this->parameterBag->get('google.auth.project_id'),
            "private_key_id"                => $this->parameterBag->get('google.auth.private_key_id'),
            "private_key"                   => $this->parameterBag->get('google.auth.private_key'),
            "client_email"                  => $this->parameterBag->get('google.auth.client_email'),
            "client_id"                     => $this->parameterBag->get('google.auth.client_id'),
            "auth_uri"                      => $this->parameterBag->get('google.auth.auth_uri'),
            "token_uri"                     => $this->parameterBag->get('google.auth.token_uri'),
            "auth_provider_x509_cert_url"   => $this->parameterBag->get('google.auth.auth_provider_x509_cert_url'),
            "client_x509_cert_url"          => $this->parameterBag->get('google.auth.client_x509_cert_url'),
        ]);

        $this->googleClient->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $this->googleClient->setSubject($this->parameterBag->get('google.subject'));
    }

    public function getClient(): Google_Client
    {
        return $this->googleClient;
    }

    public function setClient(Google_Client $googleClient): void
    {
        $this->googleClient = $googleClient;
    }
}
