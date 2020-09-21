<?php

namespace App\EventListener\Response;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DocumentIteratorResponseListener implements EventSubscriberInterface
{
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        if (!$controllerResult instanceof DocumentIterator) {
            return;
        }

        $result = [];
        foreach ($controllerResult as $item) {
            $result[] = $item;
        }

        $event->setControllerResult($result);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 40],
        ];
    }
}
