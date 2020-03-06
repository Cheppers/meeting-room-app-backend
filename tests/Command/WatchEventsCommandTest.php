<?php

namespace App\Tests\Command;

use App\Tests\TestBase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class WatchEventsCommandTest extends TestBase
{
    public function testCommand()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('watch:events');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertContains('[OK] Success', $output);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--channels' => true,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('[OK] Success', $output);
    }
}
