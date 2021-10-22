# Treblle-symfony

Symfony integration for [Treble](https://treblle.com/).

@todo badges

## Installation

To install the SDK you will need to be using [Composer]([https://getcomposer.org/)
in your project. To install it please see the [docs](https://getcomposer.org/download/).

```bash
composer require treblle/treblle-symfony
```

### Step 2: Enable the Bundle

Enable it by adding it to the list of registered bundles depending on the Symfony version you're using.

```php
// config/bundles.php

    return [
        // ...
        new Treblle\Symfony\TreblleBundle(),
    ];
}
```

## Configuration of the SDK

Configure the SDK by adding the following snippet to your project configuration. If you have Symfony 3.4 add it
to ``app/config/config_prod.yml``. For Symfony 4 or newer add the value to `config/packages/treblle.yaml`.

```yaml
treblle:
  project_id: "%env(TREBLLE_PROJECT_ID)%"
  api_key: "%env(TREBLLE_API_KEY)%"
  debug: false
  masked:
    - password
    - api_key
    - secret
  endpoint_url: "https://rocknrolla.treblle.com" // optional
```

# Overriding data providers

Treblle SDK allows you to take over control over how the data is collected, processed, and sent to the Trebble service.

By default, the SDK will take all values from the `masked` configuration key and combine it with well-known keys such as
`password` or `ssn` to compile a list of sensitive information. These values will never be sent in a plain text, but
rather will be masked.

Default service configuration for Treblle looks like this:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  treblle-client:
    class: GuzzleHttp\Client

  Treblle\Treblle:
    factory: [ Treblle\Symfony\DependencyInjection\TreblleFactory, 'createTreblle' ]
    arguments:
      $client: '@treblle-client'

  Treblle\PayloadAnonymizer:
    factory: [ Treblle\Symfony\DependencyInjection\TreblleFactory, 'createAnonymizer' ]

  Treblle\Symfony\DataProvider: ~
  Treblle\Symfony\EventSubscriber\TreblleEventSubscriber: ~
  Treblle\InMemoryErrorDataProvider: ~
  Treblle\PhpHelper: ~
  Treblle\PhpLanguageDataProvider: ~
  Treblle\SuperglobalsServerDataProvider: ~

  Treblle\Contract\ErrorDataProvider: '@Treblle\InMemoryErrorDataProvider'
  Treblle\Contract\LanguageDataProvider: '@Treblle\PhpLanguageDataProvider'
  Treblle\Contract\RequestDataProvider: '@Treblle\Symfony\DataProvider'
  Treblle\Contract\ResponseDataProvider: '@Treblle\Symfony\DataProvider'
  Treblle\Contract\ServerDataProvider: '@Treblle\SuperglobalsServerDataProvider'
```

If you want to replace any component, feel free to provide your own implementation and register it in the DI container.
When you alias the interface to point to your implementation instead of the default one, Treblle will use it.

Example: We want to override how Request data is collected and passed to Treblle

1. We implement our own service satisfying the `Treblle\Contract\RequestDataProvider` interface in `App\Utils\AcmeRequestDataProvider`
2. We register the service in our own `services.yaml`:
```yaml
  App\Utils\AcmeRequestDataProvider: ~
```

3. We define servic ealias: `Treblle\Contract\RequestDataProvider: '@AcmeRequestDataProvider'`
```yaml
  Treblle\Contract\RequestDataProvider: '@App\Utils\AcmeRequestDataProvider'
```

Now all instances depending on the `RequestDataProvider` interface will use your service.
