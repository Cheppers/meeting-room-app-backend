<?php

namespace App\Utils\GoogleAPI;

use App\Utils\Cache\AbstractCache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Google_Service_Directory;

class GoogleCalendarUsers extends GoogleApiCalls
{
    /**
     * @var Google_Service_Directory
     */
    private $serviceDirectory;

    public function __construct(
        ParameterBagInterface $parameterBag,
        AbstractCache $appCache,
        $serviceDirectory
    ) {
        parent::__construct($parameterBag, $appCache);
        $this->serviceDirectory = $serviceDirectory;
    }

    public function getAll()
    {
        $cacheKey = 'users';

        return $this->appCache->saveIfNotExists($cacheKey, function () {
            return $this->listUsers();
        }, 'PT12H')->get();
    }

    private function listUsers()
    {
        $userList = $this->serviceDirectory->users->listUsers(['customer' => self::DEFAULT_CUSTOMER]);

        $users = [];
        foreach ($userList->getUsers() as $user) {
            $users[$user->primaryEmail] = [
                'full_name' => $user->name->fullName,
                'email' => $user->primaryEmail,
                'photo' => $user->thumbnailPhotoUrl,
            ];
        }

        return $users;
    }
}
