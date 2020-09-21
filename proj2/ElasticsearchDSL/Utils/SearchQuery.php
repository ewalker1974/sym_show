<?php


namespace App\ElasticsearchDSL\Utils;

use App\ElasticsearchDSL\AbstractSearch;

class SearchQuery
{
    private $searchQuery;
    private $map;

    public function __construct(string $searchClass, array $map)
    {
        $this->searchQuery = new $searchClass;
        $this->map = $map;
    }

    /**
     * @param string $name
     * @param string|array $value
     * @return SearchBuilder
     */
    public function addSearch(string $name, $value): self
    {
        if (isset($this->map[$name])) {
            $caller = $this->map[$name];
            $this->searchQuery->$caller($value);
        } else {
            throw new \LogicException('class: '.get_class($this->searchQuery). ' has no search key: '.$name);
        }
        return $this;
    }

    public function getSearch(): AbstractSearch
    {
        return $this->searchQuery;
    }
}
