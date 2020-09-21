<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */

namespace App\PssWorkerBundle\Util\Rules;

use App\Entity\Shipment;
use Doctrine\ORM\EntityManager;
use App\PssWorkerBundle\Util\RuleInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Psr\Log\LoggerInterface;
use GenesisGlobal\Salesforce\SalesforceBundle\Service\SalesforceServiceInterface;

class FedexMRule implements RuleInterface
{
    private $em;
    private $logger;
    private $sf;
    /**
     * @var Shipment
     */
    private $currentRecord;
    /**
     * @var IterableResult
     */
    private $dataSet;

    public function __construct(EntityManager $em, LoggerInterface $logger, SalesforceServiceInterface $sf)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sf = $sf;
    }

    public function importItem()
    {
        if ($this->currentRecord) {

            $record  = $this->getSalesForceShipment($this->currentRecord->getOrderNumber(), $this->currentRecord->getSku());
            if (!$record) {
                $this->logger->error(
                    'Cannot update Fedex_m Shipment: '
                    .$this->currentRecord->getShipmentNumber()
                    . '. Corresponding SalesForce record is not found'
                );
            } else {
                if (!empty($record->tracking_number_2__c)) {
                    $this->currentRecord->setTrackingId($record->tracking_number_2__c);
                    $this->afterUpdate($this->currentRecord);
                } elseif (!empty($record->Tracking_Number__c)) {
                    $this->currentRecord->setTrackingId($record->Tracking_Number__c);
                    $this->afterUpdate($this->currentRecord);
                } else {
                    $this->logger->error(
                        'Cannot update Fedex_m Shipment: '
                        . $this->currentRecord->getShipmentNumber()
                        . '. Corresponding SalesForce record has no Tracking Id'
                    );
                }
            }
            $this->setNextRecord();
        }
    }

    private function afterUpdate(Shipment $shipment)
    {
        $this->em->flush($shipment);
        $this->logger->info(
            'Shipment is successfully updated: '
            .$shipment->getShipmentNumber()
        );

    }

    public function hasNext()
    {
        return !empty($this->currentRecord);
    }

    private function setNextRecord()
    {
        $record = $this->dataSet->next();
        if ($this->dataSet->valid()) {
            $this->currentRecord = $record[0];
        } else {
            $this->currentRecord = null;
        }
    }

    public function start()
    {
        $this->dataSet = $this->em->getRepository(Shipment::class)->getUntackedFedexMShipments();
        $this->setNextRecord();

    }
    private function getSalesForceShipment($orderNumber, $sku)
    {
        $data  =  $this->sf->query
        (
            ['Id', 'PMO_Order_No__c', 'SKU__c', 'Tracking_Number__c', 'tracking_number_2__c'],
            'Shipment__c',
            ["PMO_Order_No__c='$orderNumber'", "SKU__c='$sku'"]
        );
        $records = $data->getContent();
        if ($records->totalSize > 0) {
            return $records->records[0];
        } else {
            return null;
        }


    }
}