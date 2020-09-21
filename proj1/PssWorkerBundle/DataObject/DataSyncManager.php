<?php


namespace App\PssWorkerBundle\DataObject;

use Doctrine\ORM\EntityManager;
use App\PssWorkerBundle\DataObject\ISyncService;
use App\PssWorkerBundle\Entity\SyncLog;
use App\PssWorkerBundle\DataObject\ISynEntity;


class DataSyncManager implements ISyncService
{
    private $entityManager;

    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    public function getLastSyncTimestamp(string $syncTarget): \DateTime
    {
        $data  = $this->entityManager->getRepository(SyncLog::class)->findOneBy(['syncTarget' => $syncTarget]);

        if (!$data) {
            $date = new \DateTime();
            $date->setTimestamp(0);
        } else {
            $date = $data->getUpdateDate();
        }

        return $date;
    }

    public function setSyncTimestamp(string $syncTarget, \DateTime $timestamp): void
    {
        $data  = $this->entityManager->getRepository(SyncLog::class)->findOneBy(['syncTarget' => $syncTarget]);
        if (!$data) {
            $data = new SyncLog();
            $data->setSyncTarget($syncTarget);
        }
        $data->setUpdateDate($timestamp);
        $this->entityManager->persist($data);

        $this->entityManager->flush();
    }

}
