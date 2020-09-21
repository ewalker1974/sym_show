<?php

namespace App\EventListener;

use App\Elasticsearch\ManagerCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StoreHeaderListener implements EventSubscriberInterface
{
    const STORE_HEADER = 'store';

    protected $storeRequest;

    public function __construct(StoreRequest $storeRequest)
    {
        $this->storeRequest = $storeRequest;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $store = $event->getRequest()->headers->get(self::STORE_HEADER);
        $this->storeRequest->setCode($store);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }
}
