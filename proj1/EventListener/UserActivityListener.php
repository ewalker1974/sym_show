<?php
/**
 * @author Yurii lunhol lunhol.yurii@gmail.com
 */
namespace App\EventListener;

use App\Entity\Activity;
use App\Event\UserActivityEvent;
use Doctrine\ORM\EntityManager;

class UserActivityListener
{
    public function onUserActivity(UserActivityEvent $event)
    {
        $user = $event->getUser();
        $action = $event->getAction();
        $shipment = $event->getShipment();
        $shipmentPartner = $shipment->getShipmentPartner();
        $entityManager = $event->getManager();

        $activity = new Activity();
        $activity->setUser($user);
        if ($shipment) {
            $activity->setShipment($shipment);
        }
        if ($shipmentPartner) {
            $activity->setForPartner($shipmentPartner);
        }
        $activity->generateText($action);

        $entityManager->persist($activity);
        $entityManager->flush();
    }
}