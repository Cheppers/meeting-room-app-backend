<?php

namespace App\Command;

use App\Utils\GoogleAPI\GoogleCalendarEvents;
use App\Utils\GoogleAPI\GoogleCalendarResources;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshEventsCommand extends Command
{
    protected static $defaultName = 'refresh:events';

    /**
     * @var GoogleCalendarResources
     */
    private $calendarResources;

    /**
     * @var GoogleCalendarEvents
     */
    private $calendarEvents;

    public function __construct(
        GoogleCalendarResources $calendarResources,
        GoogleCalendarEvents $calendarEvents
    ) {
        parent::__construct(null);
        $this->calendarResources = $calendarResources;
        $this->calendarEvents = $calendarEvents;
    }

    protected function configure()
    {
        $this
            ->setDescription('Refresh events, when called')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resources = $this->calendarResources->getAll();

        foreach ($resources as $resource) {
            $resourceId = $resource['resource_id'];

            $this->calendarEvents->refreshToWebsocket($resourceId);
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('Success');
    }
}
