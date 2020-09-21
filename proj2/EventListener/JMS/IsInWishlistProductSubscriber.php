<?php

namespace App\EventListener\JMS;

use App\Customer\Wishlist\WishlistInterface;
use App\Document\Product;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class IsInWishlistProductSubscriber implements EventSubscriberInterface
{
    private $wishlist;
    public function __construct(WishlistInterface $wishlist)
    {
        $this->wishlist = $wishlist;
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
        $product->isInWishlist = $this->wishlist->isInWishList($product);
    }
}
