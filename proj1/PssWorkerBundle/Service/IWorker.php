<?php

namespace App\PssWorkerBundle\Service;

interface IWorker
{
    public function run();
    public function runParams($params);
    public function restartOnErrors(bool $restart);
}
