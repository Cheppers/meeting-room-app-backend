<?php

namespace App\Tests\Unit;

use App\Tests\TestBase;

class GoogleCalendarUsersTest extends TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->initUsers();
    }

    public function testGetAll()
    {
        $users = $this->googleCalendarUsers->getAll(self::DEFAULT_CUSTOMER);
        $this->assertCount(10, $users);
        $this->assertCount(10, $this->cache->getItem('users')->get());

        $i = 0;
        foreach ($users as $user) {
            $this->assertNotEmpty($user);
            $this->assertCount(3, $user);

            $this->assertArrayHasKey('full_name', $user);
            $this->assertEquals($this->usersArray[$i]->getName()->getFullName(), $user['full_name']);

            $this->assertArrayHasKey('email', $user);
            $this->assertEquals($this->usersArray[$i]->getPrimaryEmail(), $user['email']);

            $this->assertArrayHasKey('photo', $user);
            $this->assertEquals($this->usersArray[$i]->getThumbnailPhotoUrl(), $user['photo']);

            $i++;
        }
    }
}
