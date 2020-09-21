<?php

namespace App\EventListener\JMS;

use App\Model\Location;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

class LocationSubscriber implements EventSubscriberInterface
{

    private $shopURLTemplate;

    public function __construct(string $shopURLTemplate)
    {
        $this->shopURLTemplate = $shopURLTemplate;
    }
    /**
     * Returns the events to which this class has subscribed.
     *
     * Return format:
     *     array(
     *         array('event' => 'the-event-name', 'method' => 'onEventName', 'class' => 'some-class', 'format' => 'json'),
     *         array(...),
     *     )
     *
     * The class may be omitted if the class wants to subscribe to events of all classes.
     * Same goes for the format key.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.post_deserialize',
                'method' => 'onPostDeSerialize',
                'class' => Location::class,
            ],
        ];
    }

    public function onPostDeSerialize(ObjectEvent $event)
    {
        /** @var Location $location */
        $location = $event->getObject();
        $location->setShopBaseURL($this->shopURLTemplate);
    }
}