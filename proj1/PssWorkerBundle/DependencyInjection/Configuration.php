<?php

namespace App\PssWorkerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pss_worker');
        $rootNode
            ->children()
            ->scalarNode('mapping')->end()
        ;

        return $treeBuilder;
    }
}