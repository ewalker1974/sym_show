<?php

namespace App\ElasticsearchDSL\Utils;

use App\Document\Product;
use App\Elasticsearch\ManagerCollection;
use App\Model\Response\FilterSetResponse;
use FOS\RestBundle\Request\ParamFetcherInterface;
use App\ElasticsearchDSL\Utils\SearchBuilder;

class QuerySetBuilder
{
    /**
     * @var array [[filter name => value]]
     */
    private $filters;

    /**
     * @var SearchBuilder
     */
    private $searchBuilder;

    /**
     * @var ManagerCollection
     */
    private $managerCollection;

    public function __construct(SearchBuilder $builder, ManagerCollection $managerCollection)
    {
        $this->searchBuilder = $builder;
        $this->managerCollection = $managerCollection;
    }

    /**
     * @param string $name
     * @param string|array $value
     */
    public function setFilter(string $name, $value)
    {
        if ($value) {
            $this->filters[$name] = $value;
        }
    }

    /**
     * @param string $searchClass
     * @param string $documentClass
     * @param string $name
     * @param string|array $value
     * @return FilterSetResponse
     */
    public function getAllowedFilterSet(string $searchClass, string $documentClass, string $name, $value): FilterSetResponse
    {
        if ($this->filters) {
            $currentFilters = $this->filters;
            do {
                $filters = array_merge($currentFilters, [$name => $value]);
                $numProducts = $this->getNumProducts($searchClass, $documentClass, $filters);

                if ($numProducts > 0) {
                    return new FilterSetResponse(array_keys($filters), $numProducts);
                }
                array_pop($currentFilters);
            } while (count($currentFilters) > 0);
        }

        $filters = [$name => $value];
        $numProducts = $this->getNumProducts($searchClass, $documentClass, $filters);
        return new FilterSetResponse(array_keys($filters), $numProducts);
    }

    private function getNumProducts(string $searchClass, string $documentClass, array $filters): int
    {
        $search  = $this->searchBuilder->getSearchQuery($searchClass);
        foreach ($filters as $filter => $value) {
            $search->addSearch($filter, $value);
        }

        return $this->managerCollection->getManager()
            ->getRepository($documentClass)
            ->findDocuments($search->getSearch())
            ->count();
    }
}
