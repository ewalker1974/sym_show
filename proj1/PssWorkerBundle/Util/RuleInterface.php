<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */

namespace App\PssWorkerBundle\Util;

interface RuleInterface
{
    public function importItem();
    public function hasNext();
    public function start();

}