<?php

namespace App\Tests\Mocks\Google;

use Google_Service_Directory_User;
use Google_Service_Directory_UserName;
use Google_Service_Directory_Users;

class GoogleServiceDirectoryResourceUsers
{
    private $usersArray = [];

    public function initUsers(): void
    {
        foreach (range(1, 10) as $id) {
            $user = new Google_Service_Directory_User();
            $user->setId('user' . $id);
            $user->setPrimaryEmail('user' . $id . '@example.com');
            $userName = new Google_Service_Directory_UserName();
            $userName->setFullName('User' . $id);
            $user->setName($userName);
            $user->setThumbnailPhotoUrl('test.url');
            $this->usersArray[] = $user;
        }
    }

    public function getUsers(): array
    {
        return $this->usersArray;
    }

    public function listUsers(): Google_Service_Directory_Users
    {
        $users = new Google_Service_Directory_Users();
        $users->setUsers($this->getUsers());

        return $users;
    }
}
