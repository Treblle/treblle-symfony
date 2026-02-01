<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class defines the configuration tree structure for the Treblle bundle.
 *
 * This class defines all available configuration options including:
 * - API credentials (api_key, sdk_token)
 * - Environment settings (ignored_environments, debug)
 * - Security settings (masked_fields, excluded_headers)
 * - Custom endpoint URL
 *
 * @see TreblleConfiguration For the value object that holds these configuration values
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * Builds and returns the configuration tree for the Treblle bundle.
     *
     * Defines the structure and default values for all configuration options
     * that can be set in config/packages/treblle.yaml.
     *
     * @return TreeBuilder The configuration tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('treblle');

        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('url')->defaultNull()->end()
            ->scalarNode('api_key')->defaultValue('')->end()
            ->scalarNode('sdk_token')->defaultValue('')->end()
            ->scalarNode('ignored_environments')->defaultValue('dev,test,testing')->end()
            ->arrayNode('masked_fields')->scalarPrototype()->end()
            ->defaultValue([
                'password', 'pwd', 'secret', 'password_confirmation',
                'cc', 'card_number', 'ccv', 'ssn', 'credit_score',
            ])->end()
            ->arrayNode('excluded_headers')->scalarPrototype()->end()
            ->defaultValue([])->end()
            ->booleanNode('debug')->defaultFalse()->end()
            ->booleanNode('queue_enabled')->defaultFalse()->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
