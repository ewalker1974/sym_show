<?php


namespace App\PssWorkerBundle;

interface IUpdateSource
{
    public function setParam($param, $value):IUpdateSource;
    public function getItem():?IWorkerModel;
}
