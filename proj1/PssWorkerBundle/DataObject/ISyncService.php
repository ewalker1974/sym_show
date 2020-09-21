<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 9/2/18
 * Time: 12:07 PM
 */

namespace App\PssWorkerBundle\DataObject;

interface ISyncService
{
    public function getLastSyncTimestamp(string $syncTarget) : \DateTime;
    public function setSyncTimestamp(string $syncTarget, \DateTime $timestamp): void;
}
