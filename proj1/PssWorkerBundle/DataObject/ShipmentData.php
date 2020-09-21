<?php

namespace App\PssWorkerBundle\DataObject;

use App\PssWorkerBundle\DataObject\UpdateData;

class ShipmentData extends UpdateData
{
    public function getLogInfo() : array
    {
        return [
            'orderNumber' => $this->fields['orderNumber'],
            'deliveryStatus' => $this->fields['deliveryStatus'],
            'pssStatus'  => $this->fields['pssStatus'],
            'statusDate' => $this->fields['statusDate'],
            'updatedAt' => $this->fields['updatedAt'],
        ];
    }
}
