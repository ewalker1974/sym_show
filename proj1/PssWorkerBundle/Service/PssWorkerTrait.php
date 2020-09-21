<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */

namespace App\PssWorkerBundle\Service;

use Psr\Log\LoggerInterface;
use App\PssWorkerBundle\DataObject\ISource;
use App\PssWorkerBundle\DataObject\IDestination;
use App\PssWorkerBundle\ILogging;
use App\PssWorkerBundle\IWorkerModel;
use App\PssWorkerBundle\DestinationException;
use App\PssWorkerBundle\SourceException;
use App\PssWorkerBundle\Service\WorkerProcessException;

trait PssWorkerTrait
{
    public $fetchNote = null;
    public $writeNote = null;
    public $writeExceptionNote = null;
    public $exceptionNote = 'An Exception  occurs during the data processing';

    protected $source;
    protected $destination;
    protected $logger;
    protected $restartOnError = true;

    public function __construct(ISource $source, IDestination $destination, LoggerInterface $logger)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->logger = $logger;
    }

    protected function onGetItem(ILogging $entity)
    {
        if ($this->fetchNote) {
            $this->logger->info($this->fetchNote, $entity->getLogInfo());
        }
    }
    protected function onPutItem(ILogging $entity)
    {
        if ($this->writeNote) {
            $this->logger->info($this->writeNote, $entity->getLogInfo());
        }
    }

    protected function onPutItemException(DestinationException $e)
    {
        if ($this->writeExceptionNote) {
            $this->logger->error($this->writeExceptionNote. ' '. $e->getMessage());
        }
    }

    protected function onGetItemException(SourceException $e)
    {
        if ($this->writeExceptionNote) {
            $this->logger->error($this->writeExceptionNote. ' '. $e->getMessage());
        }
    }


    protected function onException(\Throwable $exception)
    {
        if ($this->exceptionNote) {
            $this->logger->error($this->exceptionNote . ' ' . $exception->getMessage());
        }
    }

    protected function setConstraints($constraints)
    {
        foreach ($constraints as $constraintId => $constraintValue) {
            $this->source->setConstraint($constraintId, $constraintValue);
        }
    }
    protected function execute()
    {
        $hasErrors = false;

        $sources = $this->source->get();
        try {
            foreach ($sources as $item) {
                $this->onGetItem($item);
                try {
                    $this->destination->put($item);
                } catch (DestinationException $e) {
                    $this->onPutItemException($e);
                    $hasErrors = true;
                    continue;
                }
                $this->onPutItem($item);
            }
        } catch (SourceException $e){
            $hasErrors = true;
            $this->onGetItemException($e);
            $sources->throw(new WorkerProcessException($e->getMessage()));
        }





        $this->destination->onEnd();

        return $hasErrors;
    }
    public function run()
    {
        try {
            $constraints = $this->destination->getConstraints();
            $this->setConstraints($constraints);
            $this->execute();
            $this->source->setSyncTime();

        } catch (\Throwable $e) {
            $this->onException($e);
            if ($this->restartOnError) {
                $this->later()->run();
            }

        }
    }

    public function runParams($params)
    {
        try {
            $this->destination->setParams($params);
            $constraints = $this->destination->getConstraints();
            $this->setConstraints($constraints);
            $this->execute();

        } catch (\Throwable $e) {
            $this->onException($e);
            if ($this->restartOnError) {
                $this->later()->runParams($params);
            }
        }
    }

    public function restartOnErrors(bool $restart)
    {
        $this->restartOnError = $restart;
    }

}
