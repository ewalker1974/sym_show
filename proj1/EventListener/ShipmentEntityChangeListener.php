<?php

namespace App\EventListener;

use App\Entity\Shipment;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Psr\Container\ContainerInterface;

class ShipmentEntityChangeListener
{
    private $shipments = [];
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function preUpdate(PreUpdateEventArgs $args) // OR LifecycleEventArgs
    {
        $entity = $args->getEntity();

        if ($entity instanceof Shipment && $args->hasChangedField('shipmentStatus')) {

            $shipment = [
                'oldShipmentStatus' => $args->getOldValue('shipmentStatus'),
                'newShipmentStatus' => $args->getNewValue('shipmentStatus'),
                'source' => $entity->getUpdateSource(),
            ];
            $this->shipments[$entity->getId()] = $shipment;

        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->shipments as $id => $shipment) {
            if ($shipment['newShipmentStatus'] != $shipment['oldShipmentStatus']) {
                $this->handleAllStatusChange($id, $shipment[ 'source']);
                if ($shipment['newShipmentStatus'] == Shipment::SHIPMENT_BOOKED_STATUS) {
                    $this->handleBookedStatusChange($id);
                }
            }

        }
        //:TODO multiple flashes occurs need to have 1 place to flush
        $this->shipments = [];

    }
    private function handleAllStatusChange($id, $source)
    {
        if ($source === Shipment::PRICE_NEGOTIATION) {
            $worker = $this->container->get('pss_workers.salesforce_quotation_upload_worker');
        } else {
            $worker = $this->container->get('pss_workers.salesforce_upload_worker');
        }
        $worker->later()->runParams(['shipmentId' => $id]);
    }
    private function handleBookedStatusChange($id)
    {
        $worker = $this->container->get('pss_workers.salesforce_notification_worker');
        $worker->later()->runParams(['shipmentId' => $id]);
    }


}