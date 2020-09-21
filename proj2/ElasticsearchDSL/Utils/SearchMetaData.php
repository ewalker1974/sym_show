<?php

namespace App\ElasticsearchDSL\Utils;

use App\ElasticsearchDSL\Utils\SearchMetaParser;

class SearchMetaData
{
    private $parser;
    private $metaDataCollection = [];
    public function __construct(SearchMetaParser$parser)
    {
        $this->parser = $parser;
    }

    public function getMetaData(string $className): array
    {
        if (!isset($this->metaDataCollection[$className])) {
            $metaData = new \ReflectionClass($className);
            $this->metaDataCollection[$className] = $this->parser->parse($metaData);
        }
        return $this->metaDataCollection[$className];
    }
}
