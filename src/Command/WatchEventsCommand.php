<?php

namespace App\Command;

use App\Utils\GoogleAPI\GoogleCalendarResources;
use App\Utils\GoogleAPI\GoogleCalendarWatchEvents;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WatchEventsCommand extends Command
{
    protected static $defaultName = 'watch:events';

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
        parent::__construct(null);
        $this->calendarResources = $calendarResources;
        $this->watchEvents = $watchEvents;
    }

    protected function configure()
    {
        $this
            ->setDescription('Init of Google Client API Event Watcher')
            ->addOption('channels', null, InputOption::VALUE_NONE)
            ->addOption('stop', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $resources = $this->calendarResources->getAll();

        $headers = ['building_id', 'resource_id', 'resource_name', 'resource_email', 'resource_type',];
        if ($input->getOption('channels')) {
            $headers[] = 'channel_id';
            $headers[] = 'channel_resource_id';
        }

        foreach ($resources as &$resource) {
            if ($input->getOption('channels')) {
                $channelData = $this->watchEvents->list($resource['resource_id']);
                if (is_array($channelData)) {
                    $resource['channel_id'] = $channelData['channel_id'] ?? null;
                    $resource['channel_resource_id'] = $channelData['channel_resource_id'] ?? null;
                }
                continue;
            }

            if ($input->getOption('stop')) {
                $this->watchEvents->stop($resource['resource_id']);
                continue;
            }

            $status = $this->watchEvents->watch($resource['resource_email'], $resource['resource_id']);
            $io->write(json_encode($status['errors']) . "\n");
        }

        $io->table($headers, $resources);
        $io->success('Success');
    }
}
