<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

use Treblle\Treblle;
use Treblle\PayloadAnonymizer;
use GuzzleHttp\ClientInterface;
use Treblle\Contract\ErrorDataProvider;
use Treblle\Contract\ServerDataProvider;
use Treblle\Contract\RequestDataProvider;
use Treblle\Contract\LanguageDataProvider;
use Treblle\Contract\ResponseDataProvider;

final class TreblleFactory
{
    public static function createTreblle(
        TreblleConfiguration $configuration,
        ClientInterface $client,
        ServerDataProvider $serverDataProvider,
        LanguageDataProvider $languageDataProvider,
        RequestDataProvider $requestDataProvider,
        ResponseDataProvider $responseDataProvider,
        ErrorDataProvider $errorDataProvider
    ): Treblle {
        return new Treblle(
            $configuration->getApiKey(),
            $configuration->getProjectId(),
            $client,
            $serverDataProvider,
            $languageDataProvider,
            $requestDataProvider,
            $responseDataProvider,
            $errorDataProvider,
            $configuration->isDebug(),
            $configuration->getIgnored(),
        );
    }

    public static function createAnonymizer(TreblleConfiguration $configuration): PayloadAnonymizer
    {
        $defaultMaskedFields = [
            'password',
            'pwd',
            'secret',
            'password_confirmation',
            'cc',
            'card_number',
            'ccv',
            'ssn',
            'credit_score',
        ];
        $maskedFields = array_unique(array_merge($defaultMaskedFields, $configuration->getMasked()));

        return new PayloadAnonymizer($maskedFields);
    }
}
