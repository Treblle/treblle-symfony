<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('treblle');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('url')
            ->defaultValue(null)
            ->end()
            ->scalarNode('api_key')
            ->end()
            ->scalarNode('project_id')
            ->end()
            ->scalarNode('ignored_environments')
            ->end()
            ->arrayNode('masked_fields')
            ->scalarPrototype()
            ->end()
            ->end()
            ->booleanNode('debug')
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
