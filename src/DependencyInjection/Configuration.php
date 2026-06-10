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

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('sdk_token')
                    ->defaultValue('')
                    ->info('SDK Token obtained from the Treblle Dashboard. Sent as x-api-key header.')
                ->end()
                ->scalarNode('api_key')
                    ->defaultValue('')
                    ->info('API Key obtained from the Treblle Dashboard. Identifies your project.')
                ->end()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('Enable or disable Treblle. Set to false to disable in specific environments.')
                ->end()
                ->arrayNode('masked_keywords')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'password',
                        'pwd',
                        'secret',
                        'password_confirmation',
                        'passwordConfirmation',
                        'cc',
                        'card_number',
                        'cardNumber',
                        'ccv',
                        'ssn',
                        'credit_score',
                        'creditScore',
                    ])
                    ->info('Field names to mask in request/response payloads and headers. Defaults to a built-in sensitive-field list. Set to [] to disable masking entirely.')
                ->end()
                ->arrayNode('excluded_paths')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                    ->info('Paths to exclude from tracking. Supports exact paths and wildcards (e.g. admin/*).')
                ->end()
                ->scalarNode('ingress_url')
                    ->defaultValue('https://ingress.treblle.com')
                    ->info('Treblle ingress endpoint. Override for EU or self-hosted deployments.')
                ->end()
                ->booleanNode('async')
                    ->defaultFalse()
                    ->info('Dispatch payloads via Symfony Messenger instead of sending inline. Requires symfony/messenger with a Redis or AMQP transport.')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
