<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 8/30/18
 * Time: 11:57 AM
 */

namespace App\PssWorkerBundle\Service;

interface IWorkerModel
{
    public function onException(\Exception $e);
}
