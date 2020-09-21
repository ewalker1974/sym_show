<?php

namespace App\DependencyInjection\Compiler;

use App\EventListener\StoreRequest;
use App\OAuth\ResourceOwner\CachedShopResourceOwner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShopResourceOwnerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('hwi_oauth.resource_owner.shop');
        $definition->addMethodCall('setSerializer', [new Reference('jms_serializer.serializer')]);
        $definition->addMethodCall('setStoreRequest', [new Reference(StoreRequest::class)]);

        if ($definition->getClass() === CachedShopResourceOwner::class) {
            $definition->addMethodCall('setCache', [new Reference('cache.app')]);
        }
    }
}
