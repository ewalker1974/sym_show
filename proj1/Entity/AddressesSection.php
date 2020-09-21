<?php

namespace App\Entity;

use App\Entity\Address;
use JMS\Serializer\Annotation as Serializer;

class AddressesSection
{
    /**
     * @var Address
     * @Serializer\Groups({"base_list"})
     */
    private $pickupAddress;
    /**
     * @var Address
     * @Serializer\Groups({"base_list"})
     */
    private $deliveryAddress;

    public function __construct(Address $pickupAddress, Address $deliveryAddress)
    {
        $this->pickupAddress = $pickupAddress;
        $this->deliveryAddress = $deliveryAddress;
    }

    public function getPickupAddress(): Address
    {
        return $this->pickupAddress;
    }

    public function setPickupAddress(Address $pickupAddress): AddressesSection
    {
        $this->pickupAddress = $pickupAddress;
        return $this;
    }

    public function getDeliveryAddress(): Address
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(Address $deliveryAddress): AddressesSection
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }



}