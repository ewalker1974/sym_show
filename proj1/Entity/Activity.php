<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 * @ORM\Table(name="activity")
 * @ORM\HasLifecycleCallbacks()
 */
class Activity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * One Product has Many Features.
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @Serializer\Exclude
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Shipment")
     * @ORM\JoinColumn(name="shipment_id", referencedColumnName="id", nullable=true)
     * @Serializer\Exclude
     */
    protected $shipment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Partner")
     * @ORM\JoinColumn(name="for_partner_id", referencedColumnName="id", nullable=true)
     * @Serializer\Exclude
     */
    protected $forPartner;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string")
     */
    protected $text;

    /**
     * Activity constructor.
     */
    public function __construct()
    {
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
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
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return Shipment
     */
    public function getShipment()
    {
        return $this->shipment;
    }

    /**
     * @param Shipment $shipment
     */
    public function setShipment(Shipment $shipment)
    {
        $this->shipment = $shipment;
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
    public function getText()
    {
        $createdAt = $this->getCreatedAt();
        return sprintf("%s %s", $this->text, $createdAt);
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    public function generateText($text)
    {
        $this->setText($text);
    }

    /**
     * @return Partner
     */
    public function getForPartner()
    {
        return $this->forPartner;
    }

    /**
     * @param Partner $forPartner
     */
    public function setForPartner(Partner $forPartner)
    {
        $this->forPartner = $forPartner;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("formatted_date")
     */
    public function getFormattedDateAgo()
    {
        $postDate = $this->getCreatedAt();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $interval = $postDate->diff($now);

        $days = (int) $interval->format('%a');
        if ($days > 1) {
            return sprintf("%s days ago", $days);
        } else if ($days == 1) {
            return sprintf("%s day ago", $days);
        }

        $hours = (int) $interval->format('%h');
        if ($hours > 1) {
            return sprintf("%s hours ago", $hours);
        } else if ($hours == 1) {
            return sprintf("%s hour ago", $hours);
        }

        $minutes = (int) $interval->format('%i');
        if ($minutes > 1) {
            return sprintf("%s minutes ago", $minutes);
        } else if ($minutes == 1) {
            return sprintf("%s minute ago", $minutes);
        }

        return "Less than a minute ago";
    }
}
