<?php

namespace App\PssWorkerBundle\DataObject\SalesForce;

use App\PssWorkerBundle\DataObject\SalesForce\SalesForceCustomDestination;
use App\PssWorkerBundle\DataObject\ISyncService;
use App\PssWorkerBundle\DataObject\UpdateData;
use GenesisGlobal\Salesforce\SalesforceBundle\Sobject\Sobject;

final class SalesForceNoteDestination extends SalesForceCustomDestination
{

    protected $serviceName = 'salesforce_note';
    protected $title = "Delivery is set to %s";
    protected $body = "The %s is confirmed as shipment partner of shipment %s delivery on %s";

    protected function onPutEntry( UpdateData $entity)
    {
        $shipmentNumber = $entity->getField('shipmentNumber');
        $orderNr = $entity->getField('orderNumber');
        $sku = $entity->getField('sku');
        $records = $this->getShipmentData($orderNr, $sku);
        if ($records->totalSize > 0) {
            $title = sprintf(
                $this->title,
                $entity->getField('partnerName')
            );
            $body = sprintf(
                $this->body,
                $entity->getField('partnerName'),
                $shipmentNumber,
                $entity->getField('updatedAt')->format('Y-m-d')
            );
            $this->createNote($records->records[0], $title, $body);
            $this->logger->info('SalesForce Shipment Entry is updated: Note Added: ', $entity->getLogInfo());
        }
    }

    protected function createNote($salesforceData, $title, $body)
    {
        $content = new \stdClass();
        $content->ParentId = $salesforceData->Id;
        $content->Body = $body;
        $content->Title = $title;

        $data = new Sobject();
        $data->setName('Note');
        $data->setContent($content);
        $this->salesforceService->create($data);
    }

}
