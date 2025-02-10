<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class TreblleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(TreblleConfiguration::class);
        $definition->setArguments([
            '$url' => $config['url'],
            '$apiKey' => $config['api_key'],
            '$projectId' => $config['project_id'],
            '$ignoredEnvironments' => $config['ignored_environments'],
            '$maskedFields' => (array)$config['masked_fields'],
            '$debug' => (bool)$config['debug'],
        ]);

        $container->setDefinition(TreblleConfiguration::class, $definition);
    }
}
