<?php

namespace App\EventListener\Response;

use App\Model\Response\Exception\BadRequestHttpExceptionResponse;
use App\Model\Response\Exception\ConstraintViolationListExceptionResponse;
use JMS\Serializer\SerializerInterface;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BadRequestHttpExceptionResponseListener implements EventSubscriberInterface
{
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        if (!$controllerResult instanceof BadRequestHttpExceptionResponse) {
            return;
        }

        $event->setResponse(new Response(
            $this->serializer->serialize($controllerResult, $event->getRequest()->getRequestFormat('json')),
            $controllerResult->code
        ));
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 40],
        ];
    }
}
