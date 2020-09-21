<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 8/27/18
 * Time: 9:22 PM
 */

namespace App\PssWorkerBundle\Service;

use Psr\Log\LoggerInterface;
use \Dtc\QueueBundle\Model\Worker;
use App\PssWorkerBundle\Service\IWorker;
use App\PssWorkerBundle\DataObject\ISource;
use App\PssWorkerBundle\DataObject\IDestination;

class PssWorker extends Worker implements IWorker
{
    use PssWorkerTrait;
    public $workerName = 'pss.default';

    public function getName()
    {
        return $this->workerName;
    }
}
