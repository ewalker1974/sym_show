<?php

namespace App\ElasticsearchDSL\Utils;

use App\ElasticsearchDSL\Utils\SearchMetaData;
use App\ElasticsearchDSL\Utils\SearchQuery;

class SearchBuilder
{
    /**
     * @var \App\ElasticsearchDSL\Utils\SearchMetaData
     */
    private $metaData;

    public function __construct(SearchMetaData $metaData)
    {
        $this->metaData = $metaData;
    }

    public function getSearchQuery(string $class)
    {
        $map = $this->metaData->getMetaData($class);
        return new SearchQuery($class, $map);
    }
}
