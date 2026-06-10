<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Treblle\Symfony\Doctrine\QueryCollector;
use Treblle\Symfony\Doctrine\TreblleMiddleware;
use Treblle\Symfony\Http\TreblleClient;
use Treblle\Symfony\Messenger\SendTrebllePayloadHandler;
use Treblle\Symfony\TreblleEventSubscriber;

final class TreblleExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', ['channels' => ['treblle']]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition(TreblleConfiguration::class);
        $definition->setArguments([
            '$sdkToken' => $config['sdk_token'] ?? '',
            '$apiKey' => $config['api_key'] ?? '',
            '$enabled' => (bool) ($config['enabled'] ?? true),
            '$maskedKeywords' => (array) ($config['masked_keywords'] ?? []),
            '$excludedPaths' => (array) ($config['excluded_paths'] ?? []),
            '$ingressUrl' => $config['ingress_url'] ?? 'https://ingress.treblle.com',
            '$async' => (bool) ($config['async'] ?? false),
        ]);

        $container->setDefinition(TreblleConfiguration::class, $definition);

        $dataMasker = new Definition(\Treblle\Symfony\Masking\DataMasker::class);
        $dataMasker->setArguments([
            '$maskedKeys' => (array) ($config['masked_keywords'] ?? []),
        ]);
        $container->setDefinition(\Treblle\Symfony\Masking\DataMasker::class, $dataMasker);

        if (class_exists(\Doctrine\DBAL\Driver\Middleware::class)) {
            $middleware = new Definition(TreblleMiddleware::class);
            $middleware->setArguments([new Reference(QueryCollector::class)]);
            $middleware->addTag('doctrine.middleware');
            $container->setDefinition(TreblleMiddleware::class, $middleware);
        }

        if (($config['async'] ?? false) && interface_exists('Symfony\Component\Messenger\MessageBusInterface')) {
            $container->getDefinition(TreblleEventSubscriber::class)
                ->addMethodCall('setMessageBus', [new Reference('Symfony\Component\Messenger\MessageBusInterface')]);

            $handler = new Definition(SendTrebllePayloadHandler::class);
            $handler->setArguments([new Reference(TreblleClient::class)]);
            $handler->addTag('messenger.message_handler');
            $container->setDefinition(SendTrebllePayloadHandler::class, $handler);
        }

        $loggerServiceId = $container->hasExtension('monolog') ? 'monolog.logger.treblle' : 'logger';
        $loggerRef = new Reference($loggerServiceId, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        $container->getDefinition(TreblleEventSubscriber::class)
            ->setArgument('$logger', $loggerRef);
        $container->getDefinition(TreblleClient::class)
            ->setArgument('$logger', $loggerRef);
    }
}
