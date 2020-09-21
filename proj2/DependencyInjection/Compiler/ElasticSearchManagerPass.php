<?php

namespace App\DependencyInjection\Compiler;

use App\Elasticsearch\ManagerCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ElasticSearchManagerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $languages = array_keys($container->getParameter('es.managers'));
        $managerCollection = $container->getDefinition(ManagerCollection::class);

        foreach ($languages as $language) {
            $managerCollection->addMethodCall(
                'addManager',
                [$language, new Reference('es.manager.'.$language)]
            );
        }
    }
}
