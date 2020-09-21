<?php

namespace App\PssWorkerBundle\DataObject;

use App\PssWorkerBundle\ILogging;

abstract class UpdateData implements ILogging
{
    protected $fields = [];
    public function setField(string $field, $value)
    {
        $this->fields[$field] = $value;
    }
    public function getField(string $field)
    {
        return array_key_exists($field, $this->fields) ? $this->fields[$field] : null;
    }
}
