<?php

namespace App\EventListener\JMS;

use App\Document\Product;
use App\EventListener\StoreRequest;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class ProductWebsiteUrlSubscriber implements EventSubscriberInterface
{
    private $storeRequest;

    public function __construct(StoreRequest $storeRequest)
    {
        $this->storeRequest = $storeRequest;
    }
    
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
                'class' => Product::class,
            ],
        ];
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        /** @var Product $product */
        $product = $event->getObject();
        $productSlug = $product->urlKey;
        if (!$productSlug) {
            return;
        }

        $product->url = sprintf(
            '%s/%s',
            $this->storeRequest->getLocation()->getShopBaseUrl(),
            $productSlug
        );
    }
}
