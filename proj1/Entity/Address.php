<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 * @ORM\Table(name="address")
 * @ORM\HasLifecycleCallbacks()
 */
class Address
{
    /** @deprecated   */
    const CUSTOMER_ADDRESS = 1;

    const DELIVERY_ADDRESS = 2;
    const PICKUP_ADDRESS = 3;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $phone;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $region;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $zipCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $addressLine;

    /**
     * @ORM\Column(type="integer")
     *
     */
    protected $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $comment;

    /**
     * vendor or customer comment
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     *
     */
    protected $company;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"base_list"})
     */
    protected $contactName;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Address constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return mixed
     */
    public function getAddressLine()
    {
        return $this->addressLine;
    }

    /**
     * @param $nr line nr
     * @return mixed
     */
    public function getAddressLineNr($nr)
    {
        $addressLine = explode("\n", $this->addressLine);
        return array_key_exists($nr, $addressLine) ? $addressLine[$nr] : null;
    }



    /**
     * @param mixed $addressLine
     */
    public function setAddressLine($addressLine)
    {
        $this->addressLine = $addressLine;
    }

    /**
     * @param mixed $addressLine
     */
    public function setAddressLine2($addressLine)
    {
        $this->addressLine .= " $addressLine";
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $phone
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }


    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * @param mixed $contactName
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }
}
