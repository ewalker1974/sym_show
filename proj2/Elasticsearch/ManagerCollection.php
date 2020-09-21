<?php

namespace App\Elasticsearch;

use App\EventListener\StoreRequest;
use App\Exception\NoManagerForLocaleException;
use ONGR\ElasticsearchBundle\Service\Manager as EsManager;

class ManagerCollection
{
    protected $esManagers;
    protected $store;

    public function __construct(StoreRequest $store)
    {
        $this->store = $store;
    }

    public function getManager(): EsManager
    {
        if (!isset($this->esManagers[$this->store->getCode()])) {
            throw new NoManagerForLocaleException($this->store->getCode(), array_keys($this->esManagers));
        }

        return $this->esManagers[$this->store->getCode()];
    }

    public function addManager(string $language, EsManager $manager)
    {
        $this->esManagers[$language] = $manager;
    }
}
