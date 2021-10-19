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

Enable it by adding it to the list of registered bundles in the `Kernel.php` file of your project:

```php
class AppKernel extends \Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles(): array
    {
        return [
            // ...
            new \Treblle\Symfony\TreblleBundle(),
        ];
    }

    // ...
}
```

## Configuration of the SDK

Configure the SDK by adding the following snippet to your project configuration. If you have Symfony 3.4 add it
to ``app/config/config_prod.yml``. For Symfony 4 or newer add the value to `config/packages/trblle.yaml`.

```yaml
treblle:
  api_endpoint: "https://xxx"
  project_id: "12345"
  api_key: "myawesomekey"
  debug: false
  masked:
    - password
    - api_key
    - secret
```

# Overriding data providers

@todo
