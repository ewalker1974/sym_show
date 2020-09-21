<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 9/2/18
 * Time: 1:40 AM
 */

namespace App\PssWorkerBundle\DataObject;

use App\PssWorkerBundle\Entity\SyncLog;
use Doctrine\ORM\EntityManager;

abstract class ExternalSource implements ISource
{
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }
    protected function getLastCallDate(int $sourceId): int
    {
    }

    abstract protected function items(int $lastCallDate):\Traversable;
    /**
     * gets data from data source (as array or generator)
     * @return \Traversable
     */
    public function get(): \Traversable
    {
        $lastCallDate = $this->getLastCallDate($this->sourceId);
        foreach ($this->items($lastCallDate) as $item) {
            yield $item;
        }
        $this->logCallDate($this->sourceId);
    }
}
