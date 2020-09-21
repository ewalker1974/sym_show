<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 8/31/18
 * Time: 4:07 AM
 */

namespace App\PssWorkerBundle;

interface ILogging
{
    public function getLogInfo() : array;
}
