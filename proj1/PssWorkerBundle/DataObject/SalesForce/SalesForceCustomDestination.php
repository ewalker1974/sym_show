<?php

namespace App\PssWorkerBundle\DataObject\SalesForce;

use App\PssWorkerBundle\DataObject\AbstractPssDestination;
use App\PssWorkerBundle\DataObject\ISyncService;
use GenesisGlobal\Salesforce\SalesforceBundle\Service\SalesforceServiceInterface;
use Psr\Log\LoggerInterface;

abstract class SalesForceCustomDestination extends AbstractPssDestination
{

    protected $salesforceService;
    protected $mapping;

    public function __construct(ISyncService $sync, SalesforceServiceInterface $salesforceService, LoggerInterface $logger, $mapping)
    {
        parent::__construct($sync, $logger);
        $this->salesforceService = $salesforceService;
        $this->mapping = $mapping;
        $this->logger  = $logger;
    }

    protected function getShipmentData($orderNumber, $sku)
    {
        $data  =  $this->salesforceService->query
        (
            [
                'Id',
                'PMO_Order_No__c',
                'LastModifiedDate',
                'Transport_Booking_Date__c',
                'Pick_up_Date__c',
                'Delivery_Date__c',
                'Cancel_Date__c',
                'Status__c',
                'Estimated_Pick_Up_Date_Min__c',
                'Estimated_Pick_Up_Date__c',
                'Estimated_Delivery_Date__c',
            ],
            'Shipment__c',
            ["PMO_Order_No__c='$orderNumber'", "SKU__c='$sku'"]
        );



        $records = $data->getContent();
        return $records;
    }
    private function getSFPartner($partnerName)
    {
        $parts = explode(';', $this->mapping);
        foreach ($parts as $part) {
            [$pssPartner, $sfPartner] =  explode(':', $part);
            if ($pssPartner === $partnerName) {
                return $sfPartner;
            }
        }
        return null;
    }
    protected function getShippingPartner($partnerName)
    {
        $partner = $this->getSFPartner($partnerName);
        if ($partner) {
            $data = $this->salesforceService->query(['Id', 'Name'],'Account', ["Name='$partner'"] );
            $records = $data->getContent();
            if ($records->totalSize > 0) {
                return $records->records[0];
            }
        }
        return null;
    }
    protected function isOldRecord($record, $updatedAt)
    {
        if ($this->skipNewer) {
            $lastModified = new \DateTime($record->LastModifiedDate);

            return $updatedAt < $lastModified;
        }
        return false;

    }
}