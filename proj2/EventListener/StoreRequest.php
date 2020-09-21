<?php

namespace App\EventListener;

use App\LocalFile\Repository\LocationRepository;
use App\Model\Location;
use App\Soap\Entity\Store;
use App\Soap\Repository\StoreSoapRepository;

class StoreRequest
{
    private $storeRepository;
    private $locationRepository;
    private $code;

    public function __construct(StoreSoapRepository $storeRepository, LocationRepository $locationRepository)
    {
        $this->storeRepository = $storeRepository;
        $this->locationRepository = $locationRepository;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code)
    {
        $this->code = $code;
    }

    public function getStore(): ?Store
    {
        return $this->storeRepository->findOneByCode($this->getCode());
    }

    public function getLocation(): ?Location
    {
        return $this->locationRepository->find($this->getCode());
    }
}
