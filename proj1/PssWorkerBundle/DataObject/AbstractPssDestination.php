<?php

namespace App\PssWorkerBundle\DataObject;

use App\PssWorkerBundle\IUpdateEnity;
use App\PssWorkerBundle\DataObject\UpdateData;
use App\PssWorkerBundle\DataObject\IDestination;
use Psr\Log\LoggerInterface;

abstract class AbstractPssDestination implements IDestination
{
    protected $sync;
    protected $lastDate;
    protected $serviceName;
    protected $params = [];
    protected $skipNewer = false;
    protected $logger;
    public function __construct(ISyncService $sync, LoggerInterface $logger)
    {
        $this->sync = $sync;
        $this->lastDate = 0;
        $this->logger = $logger;
    }
    abstract protected function onPutEntry(UpdateData $entity);

    public function put(UpdateData $entity):bool
    {
        $this->onPutEntry($entity);
        $updateDate = $entity->getField('updatedAt');
        $curTimestamp = $updateDate->getTimestamp();
        if ($curTimestamp > $this->lastDate) {
            $this->lastDate = $curTimestamp;
        }
        return true;
    }

    public function getConstraints(): array
    {
        $date  = $this->sync->getLastSyncTimestamp($this->serviceName);
        return array_merge(['since' => $date], $this->params);
    }

    public function onEnd()
    {
        $date = new \DateTime();
        $date->setTimestamp($this->lastDate);
        $this->sync->setSyncTimestamp($this->serviceName, $date);
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
