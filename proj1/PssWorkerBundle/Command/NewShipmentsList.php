<?php


namespace App\PssWorkerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Shipment;
use Psr\Container\ContainerInterface;

class NewShipmentsList extends Command
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
            ->setName('pss-worker:get-sync-shipments-salesforce')

            // the short description shown while running "php bin/console list"
            ->setDescription('Get all shipments that newer than in Salesforce')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Get all shipments that newer than in Salesforce');
    }

    protected function date2string($date)
    {
        if ($date === null) {
            return '';
        } else {
            return $date->format('Y-m-d');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->container->get('doctrine');
        $shipments = $doctrine->getManager()->getRepository(Shipment::class)->getAssignedShipments();
        $logger = $this->container->get("monolog.logger.sync_list");
        $salesforce = $this->container->get('salesforce.service');

        foreach($shipments as $shipment) {
            $data  =  $salesforce->query
            (
                [
                    'Id',
                    'PMO_Order_No__c',
                    'Status__c',
                    'LastModifiedDate',
                    'Transport_Booking_Date__c',
                    'Pick_up_Date__c',
                    'Delivery_Date__c'
                ],
                'Shipment__c',
                ["PMO_Order_No__c='{$shipment->getOrderNumber()}'", "SKU__c='{$shipment->getSku()}'"]
            );
            $records = $data->getContent();
            if($records->totalSize > 0) {
                $lastModified = new \DateTime($records->records[0]->LastModifiedDate);
                $updatedAt = $shipment->getUpdatedAt();
                if($updatedAt > $lastModified) {

                    $bookDate = new \DateTime($records->records[0]->Transport_Booking_Date__c);
                    $pickupDate = new \DateTime($records->records[0]->Pick_up_Date__c);
                    $deliveryDate = new \DateTime($records->records[0]->Delivery_Date__c);

                    if (
                        $bookDate != $shipment->getShipmentBookingDate() ||
                        $pickupDate != $shipment->getPickupDateDate() ||
                        $deliveryDate != $shipment->getDeliveryDate()
                    ) {
                        $output->writeln('Can update Salesforce entry: ShipmentNumber: ' .
                            $shipment->getShipmentNumber(). ' local update '.
                            $shipment->getUpdatedAt()->format('Y-m-d H:i:s'). ' Salesforce update '.
                            $lastModified->format('Y-m-d H:i:s')
                        );

                        $output->writeln('Matches: ');
                        $output->writeln('Book '.$this->date2string($bookDate) .'(sf)  '. $this->date2string($shipment->getShipmentBookingDate()). '(pss)');
                        $output->writeln('Pickup '.$this->date2string($pickupDate) .'(sf)  '. $this->date2string($shipment->getPickupDate()). '(pss)');
                        $output->writeln('Delivery '. $this->date2string($deliveryDate) .'(sf)  '. $this->date2string($shipment->getDeliveryDate()). '(pss)');
                        $output->writeln('-------------------------------------------------');
                        $logger->info(
                            'Can update Salesforce entry: ShipmentNumber: ' .
                            $shipment->getShipmentNumber(). ' local update '.
                            $shipment->getUpdatedAt()->format('Y-m-d H:i:s'). ' Salesforce update '.
                            $lastModified->format('Y-m-d H:i:s'),
                            [
                                'Book (sf)' => $this->date2string($bookDate),
                                'Book (pss)' =>$this->date2string($shipment->getShipmentBookingDate()),
                                'Pickup (sf)' => $this->date2string($pickupDate),
                                'Pickup (pss)' =>$this->date2string($shipment->getPickupDate()),
                                'Delivery (sf)' => $this->date2string($deliveryDate),
                                'Delivery (pss)' =>$this->date2string($shipment->getDeliveryDate()),
                            ]
                        );
                    }


                }
            }
        }
    }


}