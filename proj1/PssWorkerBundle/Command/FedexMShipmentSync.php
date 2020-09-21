<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */

namespace App\PssWorkerBundle\Command;

namespace App\PssWorkerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Shipment;
use Psr\Container\ContainerInterface;

class FedexMShipmentSync extends Command
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
            ->setName('pss-worker:load-fedexm-tracking')

            // the short description shown while running "php bin/console list"
            ->setDescription('Fetch Fedex M tracking Ids for Shipments')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Fetch Fedex M tracking Ids from Salesforce for Shipments');
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = $this->container->get('pss_workers.fedex_m_updater');
        $updater->run();
    }

}