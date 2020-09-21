<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 9/2/18
 * Time: 11:46 AM
 */

namespace App\PssWorkerBundle\DataObject\Local;

use App\PssWorkerBundle\DataObject\ISyncService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use App\PssWorkerBundle\DataObject\ISource;
use App\PssWorkerBundle\DataObject\ShipmentData;
use App\Entity\Shipment;

class ShipmentSource implements ISource
{
    private $entityManager;
    private $log;
    private $sync;
    private $since;
    private $shipmentId = null;
    public function __construct(EntityManager $em, LoggerInterface $log, ISyncService $sync)
    {
        //set initially zero data to get all shipments
        $this->since = new \DateTime();
        $this->since->setTimestamp(0);
        $this->entityManager = $em;
        $this->log = $log;
        $this->sync = $sync;
    }
    private function getShipmentList()
    {
        if ($this->shipmentId) {
            return [$this->entityManager->getRepository(Shipment::class)->find($this->shipmentId)];
        } else {
            return $this->entityManager->getRepository(Shipment::class)->getUpdatedSince($this->since);
        }
    }
    public function get(): \Traversable
    {
        $data  = $this->getShipmentList();
        if (is_array($data)) {
            foreach ($data as $record) {
                /**
                 * @var Shipment $record
                 */
                $pssStatus = $record->getShipmentStatus();
                $shipmentData = new ShipmentData();
                $shipmentData->setField('shipmentNumber', $record->getShipmentNumber());
                $shipmentData->setField('orderNumber', $record->getOrderNumber());
                $shipmentData->setField('sku', $record->getSku());
                $shipmentData->setField('deliveryStatus',$record->getDeliveryStatus());
                $shipmentData->setField('pssStatus',$pssStatus);
                $shipmentData->setField('pickupETADateFrom',$record->getPickupETADateFrom());
                $shipmentData->setField('pickupETADateTo',$record->getPickupETADateTo());
                $shipmentData->setField('deliveryETADateTo',$record->getDeliveryETADateTo());
                $shipmentData->setField('deliveryETADate', $record->getDeliveryETADate());
                $shipmentData->setField('shipmentCost', $record->getShippingPrice());

                $shipmentData->setField('bookedDate', $record->getShipmentBookingDate());
                $shipmentData->setField('pickupDate', $record->getPickupDate());
                $shipmentData->setField('deliveryDate', $record->getDeliveryDate());
                $shipmentData->setField('statusDate', $record->getDeliveryStatusDate());


                $shipmentData->setField('updatedAt',$record->getUpdatedAt());
                $partner = $record->getShipmentPartner();
                if ($partner) {
                    $shipmentData->setField('partnerName', $partner->getPartnerName());
                } else {
                    $shipmentData->setField('partnerName', null);
                }

                yield $shipmentData;
            }
        }

    }
    public function setConstraint(string $name, $value):ISource
    {
        if (property_exists(static::class, $name)) {
            $this->$name = $value;
        } else {
            throw new \InvalidArgumentException('The constraint '.$name. ' is not accepted');
        }
        return $this;
    }

    public function setSyncTime(): void
    {
        // TODO: Implement setSyncTime() method.
    }
}
