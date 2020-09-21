<?php

namespace App\EventListener\Response;

use App\Model\Response\Exception\ConstraintViolationListExceptionResponse;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ConstraintViolationListExceptionResponseListener implements EventSubscriberInterface
{
    protected $serializer;

    protected $annotationDriver;

    /**
     * @var int
     */
    public $code = 400;

    /**
     * @var array
     * @JMS\Type("array<string, array<string>>")
     * @JMS\Expose()
     * @SWG\Property(description="Error messages", example={"security_id": {"Missed security_id OAuth token for authentication"}})
     */
    public $messages = [];

    public function __construct(SerializerInterface $serializer, AnnotationDriver $annotationDriver)
    {
        $this->serializer = $serializer;
        $this->annotationDriver = $annotationDriver;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        if (!$controllerResult instanceof ConstraintViolationListExceptionResponse) {
            return;
        }

        $classMeta = $this->annotationDriver->loadMetadataForClass(
            new \ReflectionClass(
                $controllerResult->getViolationList()->get(0)->getRoot()
            )
        );
        $controllerResult->setMessages($classMeta);
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
