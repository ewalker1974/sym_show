<?php
/**
 * Created by PhpStorm.
 * User: ewalker
 * Date: 8/22/18
 * Time: 11:26 PM
 */

namespace App\PssWorkerBundle\DataObject;

interface ISource
{
    /**
     * gets data from data source (as array or generator)
     * @return \Traversable
     */
    public function get():?\Traversable;

    public function setConstraint(string $name, $value):ISource;
    public function setSyncTime(): void;

}
