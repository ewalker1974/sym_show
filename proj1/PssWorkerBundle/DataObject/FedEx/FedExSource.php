<?php
/**
 * @author ewalker
 * @author Yurii lunhol lunhol.yurii@gmail.com
 */

namespace App\PssWorkerBundle\DataObject\FedEx;

use App\Entity\Shipment;
use App\Entity\Partner;
use App\PssWorkerBundle\DataObject\ISource;
use App\PssWorkerBundle\DataObject\ShipmentData;
use App\PssWorkerBundle\SourceException;
use App\PssWorkerBundle\Service\FedexService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use FedEx\TrackService;
use Symfony\Component\Validator\Constraints\DateTime;

/**
/**
 * Class FedExSource
 *
 * @package App\PssWorkerBundle\DataObject\FedEx
 */
class FedExSource implements ISource
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \App\PssWorkerBundle\Service\FedexService
     */
    protected $fedexService;

    /**
     * @var array
     */
    protected $fedexStatusMap = [
        'Shipment information sent to FedEx' => Shipment::SHIPMENT_BOOKED_STATUS,
        'Order Created' => Shipment::SHIPMENT_BOOKED_STATUS,
        'At Canada Post facility' => Shipment::SHIPMENT_PLANNED_STATUS,
        'Enroute to Pickup' => Shipment::SHIPMENT_PLANNED_STATUS,
        'At Pickup' => Shipment::SHIPMENT_PLANNED_STATUS,
        'Hold at Location' => Shipment::SHIPMENT_PLANNED_STATUS,
        'Shipment Information sent to USPS' => Shipment::SHIPMENT_PLANNED_STATUS,
        'Pickup Delay' => Shipment::SHIPMENT_PLANNED_STATUS,
        'At Airport' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At FedEx Facility' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Arrived at' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At USPS facility' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Location Changed' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Delivery DEly' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Departed' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Vehicle furnished but not used' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Vehicle Dispatched' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Delay' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Enroute to Airport' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Enroute to Delivery' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Enroute to Origin Airport' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At FedEx Destination' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'in Transit' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'In transit' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'in Transit(see details)' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'In transit(see details)' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Left Origin' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Out for Delivery' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At FedEx origin facility' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Plane in Flight' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Plane Landed' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'In Progress' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Picked Up' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Picked Up (see Details)' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Split Status' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Transfer' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Cleared Customs' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Clearance in Progress' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Export Approved' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At Delivery' => Shipment::SHIPMENT_DELIVERED_STATUS,
        'Delivered' => Shipment::SHIPMENT_DELIVERED_STATUS,
        'Shipment cancelled' => Shipment::SHIPMENT_CANCELLED_STATUS,
        'Return to Shipper' => Shipment::SHIPMENT_CANCELLED_STATUS,
        'At local FedEx facility' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'At FedEx destination facility' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Arrived at FedEx location' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Delivery exception' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Picked up' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'On FedEx vehicle for delivery' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
        'Departed FedEx location' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,

    ];

    protected $logger;

    /**
     * FedExSource constructor.
     *
     * @param EntityManager $em
     * @param FedexService $fedexService
     */
    public function __construct(EntityManager $em, FedexService $fedexService, LoggerInterface $logger)
    {
        $this->entityManager = $em;
        $this->fedexService = $fedexService;
        $this->logger = $logger;
    }

    /**
     * @return \Traversable
     * @throws SourceException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function get(): \Traversable
    {
        try {
            $em = $this->entityManager;
            $shipments = $em->getRepository(Shipment::class)->getFedexShipments();

            $trackRequest = $this->fedexService->getTrackRequest();

            $request = new TrackService\Request();
            if ($this->fedexService->isProductionMode()) {
                // @todo this checking need to hide in fedex service
                $request->getSoapClient()->__setLocation(TrackService\Request::PRODUCTION_URL);
            }

        } catch (\Throwable $e) {
            throw new SourceException($e->getMessage());
        }

        foreach ($shipments as $item) {
            try {
                $shipment = $item[0];
                $trackingId = $shipment->getTrackingId();

                $identifier = $this->fedexService->getTrackPackageIdentifier($trackingId);
                $trackRequest->setPackageIdentifier($identifier);


                $response = $request->getTrackReply($trackRequest);

                $highestSeverity = $response->HighestSeverity;
                if ($highestSeverity == 'SUCCESS') {
                    foreach ($response->TrackDetails as $trackDetail) {
                        $deliveryStatus = $this->handleStatus($trackDetail->StatusDescription);
                        if (!$deliveryStatus) {
                            $this->logger->error("Cannot get Shipment Status, Shipment Number: ".$shipment->getShipmentNumber());
                            continue;
                        }
                        $shipmentData = new ShipmentData();
                        $statusDate = $this->getStatusTimestamp($trackDetail, $deliveryStatus);
                        if (!$statusDate) {
                            $this->logger->error("Cannot get Shipment Timestamp, Shipment Number: ".$shipment->getShipmentNumber());
                            continue;
                        }
                        $shipmentData->setField('pssStatus', $deliveryStatus);
                        $shipmentData->setField('deliveryStatus', $trackDetail->StatusDescription);
                        $shipmentData->setField('orderNumber', $shipment->getOrderNumber());
                        $shipmentData->setField('shipmentNumber', $shipment->getShipmentNumber());
                        $shipmentData->setField('statusDate', $statusDate);
                        if ($trackDetail->EstimatedDeliveryTimestamp) {
                            $shipmentData->setField('eta', new \DateTime($trackDetail->EstimatedDeliveryTimestamp));
                        }
                        $shipmentData->setField('updatedAt', new \DateTime('now'));
                        $shipmentData->setField('partnerTag', Partner::FEDEX_PARTNER);
                        yield $shipmentData;
                    }
                } else {
                    $errors = [];
                    foreach ($response->Notifications as $notification) {
                        $errors[] = $notification->Message;
                    }
                    $this->logger->error(implode(' ', $errors). " Shipment Number: ".$shipment->getShipmentNumber());
                }

            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(). " Shipment Number: ".$shipment->getShipmentNumber());
            }


        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return ISource
     */
    public function setConstraint(string $name, $value): ISource
    {
        return $this;
    }

    /**
     * @return array
     */
    protected function getShipments()
    {
        $shipments = $this->entityManager
            ->getRepository(Shipment::class)
            ->loadProcessingShipments();

        return $shipments;
    }

    /**
     * @param string $status
     * @return string
     */
    protected function handleStatus($status)
    {
        if (array_key_exists($status, $this->fedexStatusMap)) {
            return $this->fedexStatusMap[$status];
        } else {
            return null;
        }

    }

    public function setSyncTime(): void
    {
        // TODO: Implement setSyncTime() method.
    }
    private function getTimestampFromEvent($record): ?\DateTime
    {
        if (is_object($record->Events[0]) && !empty($record->Events[0]->Timestamp)) {
            return $this->getUTCTimestamp($record->Events[0]->Timestamp);
        } else {
            return null;
        }
    }
    private function getTimestampFromField($record, $fieldName): ?\DateTime
    {
        if (!empty($record->$fieldName)) {
            return $this->getUTCTimestamp($record->$fieldName);
        } else {
            return $this->getTimestampFromEvent($record);
        }
    }
    public function getStatusTimestamp($record, $pssStatus): ?\DateTime
    {
        switch ($pssStatus) {
            case Shipment::SHIPMENT_BOOKED_STATUS:
                return $this->getTimestampFromEvent($record);
            case Shipment::SHIPMENT_PLANNED_STATUS:
                return $this->getTimestampFromEvent($record);
            case Shipment::SHIPMENT_IN_TRANSIT_STATUS:
                return $this->getTimestampFromEvent($record);
            case Shipment::SHIPMENT_DELIVERED_STATUS:
                return $this->getTimestampFromField($record, 'ActualDeliveryTimestamp');
            case Shipment::SHIPMENT_CANCELLED_STATUS:
                return $this->getTimestampFromEvent($record);
        }
        return null;

    }
    protected function getUTCTimestamp($timeStamp)
    {
        $localDate = new \DateTime($timeStamp);
        $returnDate = new \DateTime();
        $returnDate->setTimezone( new \DateTimeZone('UTC'));
        $returnDate->setTimestamp($localDate->getTimestamp());
        return $returnDate;
    }
}
