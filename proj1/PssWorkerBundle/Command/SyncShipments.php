<?php

namespace App\PssWorkerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Entity\Shipment;
use Psr\Container\ContainerInterface;

class SyncShipments extends Command
{
    private $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('pss-worker:sync-shipments')

            // the short description shown while running "php bin/console list"
            ->setDescription('Sync all shipments')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Force sync all assignedshipments with salesforce')
            ->addArgument('shipment', InputArgument::OPTIONAL, 'shipment number to sync');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->container->get('doctrine');
        $shipment = $input->getArgument('shipment');
        if ($shipment) {
            $shipments = $doctrine->getManager()->getRepository(Shipment::class)->getShipmentByShipmentNumber($shipment);
        } else {
            $shipments = $doctrine->getManager()->getRepository(Shipment::class)->getAssignedShipments();
        }
        $salesforce = $this->container->get('pss_workers.salesforce_upload_worker_sync');
        if (is_array($shipments)) {
            foreach($shipments as $shipment) {
                /**
                 * @var Shipment $shipment
                 */
                $output->writeln('Updating shipment: '.$shipment->getShipmentNumber());
                $salesforce->runParams(['shipmentId' => $shipment->getId()]);
            }
        }

    }

}