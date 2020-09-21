<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */


namespace App\PssWorkerBundle\Util;
use App\PssWorkerBundle\Util\RuleInterface;
use Psr\Log\LoggerInterface;

class TrackingImporter
{
    private $logger;
    private $rule;
    public function __construct(RuleInterface $rule, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->rule = $rule;
    }
    public function run()
    {
        try {
            $this->rule->start();
            while($this->rule->hasNext()) {
                $this->rule->importItem();
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

    }

}