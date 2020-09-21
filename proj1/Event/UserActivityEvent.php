<?php
/**
 * @author Yurii lunhol lunhol.yurii@gmail.com
 */
namespace App\Event;

use App\Entity\Shipment;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\User\UserInterface;

class UserActivityEvent extends Event
{
    const NAME = 'user.activity';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var Shipment
     */
    protected $shipment;

    public function __construct(string $action, ObjectManager $em, UserInterface $user = null, Shipment $shipment = null)
    {
        $this->action = $action;
        $this->em = $em;
        $this->user = $user;
        $this->shipment = $shipment;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
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
     * @return ObjectManager
     */
    public function getManager(): ObjectManager
    {
        return $this->em;
    }
}