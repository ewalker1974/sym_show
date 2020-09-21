<?php

namespace App\ElasticsearchDSL;

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;

abstract class AbstractSearch extends Search
{
    const CHUNK_SIZE  = 1000;
    public function __construct()
    {
        parent::__construct();
    }

    protected function setFieldIds($field, $ids)
    {
        if (!$ids) {
            return $this;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        if (count($ids) < self::CHUNK_SIZE) {
            return $this->addQuery(new TermsQuery(
                $field,
                $ids
            ));
        } else {
            $chunkIds = array_chunk($ids, self::CHUNK_SIZE);
            $combineQuery = new BoolQuery();
            $combineQuery->addParameter("minimum_should_match", 1);
            foreach ($chunkIds  as $chunk) {
                $query = new TermsQuery($field, $chunk);
                $combineQuery->add($query, BoolQuery::SHOULD);
            }
            return $this->addQuery($combineQuery);
        }
    }
}
