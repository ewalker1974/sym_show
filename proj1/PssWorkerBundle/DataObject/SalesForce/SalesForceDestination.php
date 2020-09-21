<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 8/28/18
 * Time: 11:27 AM
 */

namespace App\PssWorkerBundle\DataObject\SalesForce;

use App\Entity\Shipment;
use App\PssWorkerBundle\IWorkerModel;
use App\PssWorkerBundle\DataObject\ISyncService;
use App\PssWorkerBundle\DataObject\SalesForce\SalesForceCustomDestination;
use App\PssWorkerBundle\DataObject\UpdateData;
use GenesisGlobal\Salesforce\SalesforceBundle\Service\SalesforceServiceInterface;
use App\PssWorkerBundle\DestinationException;


class SalesForceDestination extends SalesForceCustomDestination
{
    protected $serviceName = 'salesforce';
    protected $overwriteSalesforceDates = false;
    protected function getUpdatableDateFields(UpdateData $entity, \stdClass $record, array $map)
    {
        $result = [];
        foreach ($map as $sfField => $entField) {
            $date = $entity->getField($entField);
            if ($date) {
                if ($this->overwriteSalesforceDates || !$record->$sfField) {
                    $result[$sfField] = $date->format('Y-m-d');
                } else {
                    $dateSf = new \DateTime($record->$sfField);
                    if ($dateSf > $date) {
                        $result[$sfField] = $date->format('Y-m-d');
                    }
                }

            }

        }
        return $result;
    }

    protected function createETADates($sfRecord, $entity)
    {
        $updateDateFields = [];
        $estimatingDates = [
            'pickupETADateFrom' => 'Estimated_Pick_Up_Date_Min__c',
            'pickupETADateTo' => 'Estimated_Pick_Up_Date__c',
            'deliveryETADateTo' => 'Estimated_Delivery_Date__c',
            'deliveryETADate' => 'Estimated_Delivery_Date__c',
        ];
        foreach ($estimatingDates as $pDate => $sfDate) {
            $date = $entity->getField($pDate);
            if ($date) {
                $refDate = $sfRecord->$sfDate;
                if ($refDate) {
                    $refDate = new \DateTime($refDate);
                    if ($date > $refDate) {
                        $updateDateFields[$sfDate] = $date->format('Y-m-d');
                    }
                } else {
                    $updateDateFields[$sfDate] = $date->format('Y-m-d');
                }
            }
        }
        return $updateDateFields;

    }

    protected function onPutEntry(UpdateData $entity)
    {
        $statusMap = [
            Shipment::SHIPMENT_NEW_STATUS=> 'OPN',
            Shipment::SHIPMENT_ASSIGNED_STATUS => 'OPN',
            Shipment::SHIPMENT_BOOKED_STATUS => 'BKD',
            Shipment::SHIPMENT_IN_TRANSIT_STATUS => 'SHPT',
            Shipment::SHIPMENT_PLANNED_STATUS => 'BKD',
            Shipment::SHIPMENT_DELIVERED_STATUS => 'DLV',
            Shipment::SHIPMENT_CANCELLED_STATUS => 'CAN',
         ];
        $deliveredStatus = $entity->getField('pssStatus');
        if (array_key_exists($deliveredStatus, $statusMap)) {
            $status = $statusMap[$deliveredStatus];
            $updateDateFields = [];

            $orderNr = $entity->getField('orderNumber');
            $sku = $entity->getField('sku');
            $records = $this->getShipmentData($orderNr, $sku);
            $record = null;
            if ($records && $records->totalSize > 0) {
                $record = $records->records[0];
            } else {
                $this->logger->error('SalesForce Shipment Entry is not found: ', $entity->getLogInfo());
                return;

            }

            switch ($status) {
                case 'OPN':
                    $updateDateFields = [
                        'Pick_up_Date__c' => null,
                        'Delivery_Date__c' => null,
                        'Cancel_Date__c' => null,
                        'Transport_Booking_Date__c' => null
                    ];
                    break;
                case 'BKD':
                    $dateFields = $this->getUpdatableDateFields($entity, $record, ['Transport_Booking_Date__c' => 'bookedDate']);
                    $updateDateFields = array_merge(
                        [
                            'Pick_up_Date__c' => null,
                            'Delivery_Date__c' => null,
                            'Cancel_Date__c' => null,
                            'PSS_Job_costing__c' => $entity->getField('shipmentCost'),
                        ],
                        $dateFields
                    );
                    break;
                case 'SHPT':
                    $dateFields = $this->getUpdatableDateFields(
                        $entity,
                        $record,
                        [
                            'Pick_up_Date__c' => 'pickupDate',
                        ]
                    );
                    $updateDateFields = array_merge(
                        [
                            'Delivery_Date__c' => null,
                            'PSS_Job_costing__c' => $entity->getField('shipmentCost'),
                            'Cancel_Date__c' => null,
                        ],
                        $dateFields
                    );
                    break;
                case 'DLV':
                    $dateFields = $this->getUpdatableDateFields(
                        $entity,
                        $record,
                        [
                            'Delivery_Date__c' => 'deliveryDate'
                        ]
                    );
                    $updateDateFields = array_merge(
                        [
                            'Cancel_Date__c' => null,
                            'PSS_Job_costing__c' => $entity->getField('shipmentCost'),
                        ],
                        $dateFields
                    );
                    break;
                case 'CAN':
                    $dateFields = $this->getUpdatableDateFields(
                        $entity,
                        $record,
                        [
                            'Cancel_Date__c' => 'statusDate'
                        ]
                    );
                    $updateDateFields = array_merge(
                        [
                            'Delivery_Date__c' => null
                        ],
                        $dateFields
                    );
            }


            $updateDateFields = array_merge($updateDateFields, $this->getShippingPartnerUpdateData($entity));

            if (count($updateDateFields) > 0) {
                if($this->isOldRecord($record, $entity->getField('updatedAt'))) {
                    $this->logger->error('SalesForce Shipment Entry is not updated SalesForce has newer one: ', $entity->getLogInfo());
                } elseif (!$this->isAllowedTransition($record, $status)) {
                    $this->logger->error('SalesForce Shipment Entry is not updated due to status conflict: ', $entity->getLogInfo());
                } else {
                    $id = $record->Id;
                    $etaFields = $this->createETADates($record, $entity);
                    $updateDateFields = array_merge($updateDateFields, $etaFields);
                    $this->salesforceService->update('Shipment__c', $id, $updateDateFields);

                        //have to set status in separate query because new date triggered status update
                    $this->salesforceService->update('Shipment__c', $id, ['Status__c' => $status]);
                    $this->logger->info('SalesForce Shipment Entry is updated: ', $entity->getLogInfo());
                }
            }

        }

    }
    protected function getShippingPartnerUpdateData(UpdateData $entity)
    {
        $partner = $entity->getField('partnerName');
        if (!$partner) {
            return ['Shipping_Partner__c' => null];
        } else {
            $sfPartner = $this->getShippingPartner($partner);
            if (!$sfPartner) {
                return [];
            } else {
                return ['Shipping_Partner__c' => $sfPartner->Id];
            }

        }

    }
    protected function isAllowedTransition($record, $nextStatus)
    {
        $allowedStatusMap = [
            'OPN' => ['OPN','CAN'],
            'BKD' => ['OPN', 'BKD', 'CAN'],
            'SHPT' => ['OPN','BKD', 'SHPT', 'CAN'],
            'DLV' => ['OPN', 'BKD', 'SHPT', 'DLV', 'CAN'],
            'CAN' => ['OPN', 'BKD', 'SHPT', 'DLV', 'CAN'],
        ];
        return $this->overwriteSalesforceDates || array_key_exists($nextStatus, $allowedStatusMap) && (in_array($record->Status__c, $allowedStatusMap[$nextStatus]));
    }

}
