<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('treblle');

        // @phpstan-ignore-next-line
        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('endpoint_url')->defaultValue('https://rocknrolla.treblle.com')->end()
            ->scalarNode('project_id')->end()
            ->scalarNode('api_key')->end()
            ->booleanNode('debug')->end()
            ->arrayNode('masked')->scalarPrototype()->end()
            ->arrayNode('ignore')->scalarPrototype()->end()
            ->end();

        return $treeBuilder;
    }
}
