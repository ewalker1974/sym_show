<?php

namespace App\PssWorkerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SyncLog
 *
 * @ORM\Table(name="sync_log")
 * @ORM\Entity(repositoryClass="App\PssWorkerBundle\Repository\SyncLogRepository")
 */
class SyncLog
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="string")
     */
    private $syncTarget;

    /**
     * @var int
     *
     * @ORM\Column(type="datetime")
     */
    private $updateDate;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set srcId.
     *
     * @param string $syncTarget
     *
     * @return SyncLog
     */
    public function setSyncTarget($syncTarget)
    {
        $this->syncTarget = $syncTarget;

        return $this;
    }

    /**
     * Get syncTarget.
     *
     * @return string
     */
    public function getSyncTarget()
    {
        return $this->syncTarget;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return SyncLog
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
