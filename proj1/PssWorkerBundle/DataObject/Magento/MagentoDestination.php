<?php

namespace App\PssWorkerBundle\DataObject\Magento;

use App\PssWorkerBundle\DataObject\AbstractPssDestination;
use App\PssWorkerBundle\DataObject\IDestination;
use App\PssWorkerBundle\IWorkerModel;
use App\PssWorkerBundle\DataObject\UpdateData;
use App\PssWorkerBundle\DataObject\ISyncService;
use Psr\Log\LoggerInterface;

final class MagentoDestination extends AbstractPssDestination
{
    protected $serviceName = 'magento';

    public function __construct(ISyncService $sync, LoggerInterface $logger)
    {
        parent::__construct($sync, $logger);
    }


    protected function onPutEntry(UpdateData $entity)
    {
        // TODO: Implement onPutEntry() method.
    }
}
