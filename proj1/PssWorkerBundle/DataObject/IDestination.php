<?php


namespace App\PssWorkerBundle\DataObject;

use App\PssWorkerBundle\IUpdateEnity;
use App\PssWorkerBundle\DataObject\UpdateData;

interface IDestination
{
    /**
     * put data to external source or create new job in queue
     * @param IUpdateEnity $entity
     * @return mixed
     */
    public function put(UpdateData $entity);
    public function onEnd();
    public function setParams(array $params): void;
    public function getConstraints() : array;
    public function setSkipNewer(bool $skipNewer);
}
