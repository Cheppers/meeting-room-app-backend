<?php

namespace App\Command;

use App\Utils\GoogleAPI\GoogleCalendarEvents;
use App\Utils\GoogleAPI\GoogleCalendarResources;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CheckEventsCommand extends Command
{
    protected static $defaultName = 'check:events';

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GoogleCalendarResources
     */
    private $calendarResources;

    /**
     * @var GoogleCalendarEvents
     */
    private $calendarEvents;

    private $cancelUnconfirmed = false;

    public function __construct(
        ParameterBagInterface $parameterBag,
        LoggerInterface $logger,
        GoogleCalendarResources $calendarResources,
        GoogleCalendarEvents $calendarEvents
    ) {
        parent::__construct(null);
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->calendarResources = $calendarResources;
        $this->calendarEvents = $calendarEvents;
        $this->cancelUnconfirmed = (int)$this->parameterBag->get('cancel.unconfirmed') > 0;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check events & delete non-confirmed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $rooms = $this->calendarResources->getAll();

        $this->logger->info('check:events', ['cancel.unconfirmed' => (int)$this->cancelUnconfirmed]);
        $io->write('Cancel Unconfirmed: ' . ($this->cancelUnconfirmed ? 'On' : 'Off'), true);

        $this->cancelUnconfirmed($rooms, $io);

        $io->success('Success');
    }

    private function cancelUnconfirmed($rooms, &$io)
    {
        if (!is_array($rooms)) {
            return;
        }

        foreach ($rooms as $room) {
            $resourceId = $room['resource_id'];
            $resourceEmail = $room['resource_email'];
            $resourceName = $room['resource_name'];

            $events = $this->calendarEvents->getResourceEventsById($resourceId);

            if (!is_array($events)) {
                continue;
            }

            $io->write('', true);
            $io->write($resourceName, true);
            $eventTable = [];
            foreach ($events as $event) {
                $this->checkIfEventNeedsToCancel($resourceEmail, $resourceId, $event, $eventTable);
            }

            $io->table([], $eventTable);
        }
    }

    private function checkIfEventNeedsToCancel(
        string $resourceEmail,
        string $resourceId,
        array $event,
        array &$eventTable
    ) {
        if (Carbon::parse($event['start'])
                ->addMinutes($this->parameterBag->get('cancel.unconfirmed.after')) < Carbon::now()
            && Carbon::parse($event['end']) > Carbon::now()
            && $event['resource_status'] === 'accepted'
            && !$this->calendarEvents->isEventConfirmed($resourceId, $event['id'])) {
            $eventTable[] = [
                'id' => $event['id'],
                'summary' => $event['summary'],
                'start' => $event['start'],
                'end' => $event['end'],
                'status' => $event['status'],
                'resource_status' => $event['resource_status'],
            ];

            if ($this->cancelUnconfirmed) {
                $this->calendarEvents->cancel($resourceEmail, $event['id']);
            }
        }
    }
}
