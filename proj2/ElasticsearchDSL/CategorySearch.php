<?php

namespace App\ElasticsearchDSL;

use ONGR\ElasticsearchDSL\Serializer\OrderedSerializer;

class CategorySearch extends AbstractSearch
{
    /**
     * @var OrderedSerializer
     */
    protected static $serializer;

    public function __construct()
    {
        parent::__construct();
    }

    public function setIds($ids): self
    {
        return $this->setFieldIds('_id', $ids);
    }
}
