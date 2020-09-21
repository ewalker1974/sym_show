<?php


namespace App\PssWorkerBundle\DataObject\Local;

use App\PssWorkerBundle\DataObject\IDestination;
use App\PssWorkerBundle\DestinationException;
use App\PssWorkerBundle\IWorkerModel;
use App\PssWorkerBundle\DataObject\UpdateData;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use App\Entity\Shipment;
use App\Entity\Partner;
use App\PssWorkerBundle\DataObject\ISyncService;

class ShipmentDestination implements IDestination
{
    private $entityManager;
    private $isUpdated;
    protected $params = [];
    protected $skipNewer = false;
    protected $logger;

    public function __construct(EntityManager $em, LoggerInterface $logger)
    {
        $this->entityManager = $em;
        $this->logger = $logger;
    }

    private function getShipments(UpdateData $entity)
    {
        $shipments = null;
        $shipmentNumber = $entity->getField('shipmentNumber');
        if ($shipmentNumber) {
            $shipments  = $this->entityManager
                ->getRepository(Shipment::class)
                ->getShipmentByShipmentNumber($shipmentNumber);

        }
        return $shipments;
    }

    /**
     * put data to external source or create new job in queue
     * @param IWorkerModel $entity
     * @return mixed
     */
    public function put(UpdateData $entity)
    {
        $shipments = $this->getShipments($entity);

        if ($shipments && is_array($shipments) && count($shipments) > 0) {
            $status = $entity->getField('deliveryStatus');
            if ($status) {
                $date = $entity->getField('statusDate');

                $eta = $entity->getField('eta');

                $pssStatus = $entity->getField('pssStatus');
                $partners = $this->entityManager->getRepository(Partner::class)->getPartnerByTag($entity->getField('partnerTag'));
                if (is_array($partners) && count($partners) > 0) {
                    $partner = $partners[0];
                    foreach ($shipments as $shipment) {
                        if ($shipment->getShipmentPartner() == null) {
                            $shipment->setShipmentPartner($partner);
                        }
                        /**
                         * @var Shipment $shipment
                         */

                        if ($eta) {
                            $shipment->setDeliveryETADate($eta);
                        }

                        $shipment->processShipmentStatusByPartner($pssStatus, $date);
                        $shipment->setDeliveryStatusDateByPartner($status, $date);
                    }
                } else {
                    $this->logger->error('Cannot get partner data for Order '. $entity->getField('orderNumber'));
                }

            } else {
                $this->logger->error('Invalid update status of order '. $entity->getField('orderNumber'));
            }
        } else {
            $this->logger->error('The related recorder of order '.$entity->getField('orderNumber').' is not found in database');
        }
        $this->logger->info('Local Shipment data is updated:', $entity->getLogInfo());
        $this->entityManager->flush();
    }

    public function getConstraints(): array
    {
        $date = new \DateTime();
        $date->setTimestamp(0);
        return array_merge(['since' => $date], $this->params);
    }

    public function onEnd()
    {

    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }
    public function setSkipNewer($skipNewer)
    {
        $this->skipNewer = $skipNewer;
    }
}
