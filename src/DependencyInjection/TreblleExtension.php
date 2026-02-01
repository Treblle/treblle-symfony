<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * TreblleExtension loads and processes the Treblle bundle configuration.
 *
 * This extension:
 * - Loads the service definitions from services.yaml
 * - Processes the configuration tree defined in Configuration class
 * - Creates and registers the TreblleConfiguration service with resolved values
 *
 * @see Configuration For the configuration tree definition
 * @see TreblleConfiguration For the resulting configuration value object
 */
final class TreblleExtension extends Extension
{
    /**
     * Loads the Treblle bundle configuration and services.
     *
     * Processes the configuration from config/packages/treblle.yaml,
     * validates it against the configuration tree, and registers
     * the TreblleConfiguration service with the resolved values.
     *
     * @param array<int, array<string, mixed>> $configs Raw configuration arrays from YAML
     * @param ContainerBuilder $container The service container builder
     *
     * @return void
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(TreblleConfiguration::class);
        $definition->setArguments([
            '$apiKey' => $config['api_key'] ?? '',
            '$sdkToken' => $config['sdk_token'] ?? '',
            '$url' => $config['url'],
            '$ignoredEnvironments' => $config['ignored_environments'],
            '$maskedFields' => (array)$config['masked_fields'],
            '$excludedHeaders' => (array)$config['excluded_headers'],
            '$debug' => (bool)$config['debug'],
            '$queueEnabled' => (bool) $config['queue_enabled']
        ]);

        $container->setDefinition(TreblleConfiguration::class, $definition);
    }
}
