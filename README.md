# Treblle - Runtime Intelligence Platform

[Website](http://treblle.com/) • [Documentation](https://docs.treblle.com/) • [Pricing](https://treblle.com/pricing)

Discover, Govern, and Secure APIs, Agents, and AI Across Any Cloud, Gateway or Technology.

## Treblle Symfony SDK

[![Latest Version](https://img.shields.io/packagist/v/treblle/treblle-symfony
)](https://packagist.org/packages/treblle/treblle-symfony)
[![Total Downloads](https://img.shields.io/packagist/dt/treblle/treblle-symfony)](https://packagist.org/packages/treblle/treblle-symfony)


## Requirements

| Dependency  | Version       |
|-------------|---------------|
| PHP         | 8.2, 8.3, 8.4 |
| Symfony     | 6.4, 7.x, 8.x |
| ext-curl    | any           |
| ext-json    | any           |
| ext-zlib    | any           |

> **Async mode** (optional): `symfony/messenger` + a Redis or AMQP transport.

## Installation

```bash
composer require treblle/treblle-symfony
```

> **Symfony Flex users:** Steps 1–3 below happen automatically on install

## Setup

### 1. Register the bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Treblle\Symfony\TreblleBundle::class => ['all' => true],
];
```

### 2. Configure the SDK

Create `config/packages/treblle.yaml`:

```yaml
treblle:
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
  api_key: "%env(TREBLLE_API_KEY)%"
```

### 3. Set your credentials

Add to your `.env`:

```dotenv
TREBLLE_SDK_TOKEN=your-sdk-token-from-treblle-dashboard
TREBLLE_API_KEY=your-api-key-from-treblle-dashboard
```

Both values are available in your [Treblle Dashboard](https://treblle.com).

---

## Configuration Reference

All options with their defaults:

```yaml
treblle:
  sdk_token: ""               # Required - SDK Token from Treblle Dashboard
  api_key: ""                 # Required - API Key from Treblle Dashboard
  enabled: true               # Set to false to disable Treblle (e.g. in dev/test)
  async: false                # Dispatch via Symfony Messenger (requires Redis or AMQP transport)
  masked_keywords:            # Fields to mask - set to [] to disable masking entirely
    - password
    - pwd
    - secret
    - password_confirmation
    - passwordConfirmation
    - cc
    - card_number
    - cardNumber
    - ccv
    - ssn
    - credit_score
    - creditScore
  excluded_paths: []          # Paths to skip (exact or wildcard)
  ingress_url: "https://ingress.treblle.com"  # Ingress endpoint
```

### `sdk_token`

Your SDK Token from the Treblle Dashboard. This is sent as the `x-api-key` header on every request to the ingress.

### `api_key`

Your project's API Key from the Treblle Dashboard. Identifies which project this data belongs to.

### `enabled`

Controls whether Treblle is active. Defaults to `true`. Set to `false` to disable the SDK in specific environments without removing your credentials.

The recommended approach is an environment-specific config file:

```yaml
# config/packages/dev/treblle.yaml
treblle:
  enabled: false
```

```yaml
# config/packages/test/treblle.yaml
treblle:
  enabled: false
```

You can also drive it from an environment variable:

```yaml
# config/packages/treblle.yaml
treblle:
  enabled: "%env(bool:TREBLLE_ENABLED)%"
```

### `masked_keywords`

The list of field names to mask in request bodies, response bodies, request headers, and response headers. The SDK ships with a default list of common sensitive fields (see the configuration reference above). You control the list entirely - add, remove, or replace entries as needed.

Masking replaces each character of the value with `*`, preserving the original length, and is applied recursively to nested objects and arrays.

**Adding fields to the default list:**

```yaml
treblle:
  masked_keywords:
    - password
    - pwd
    - secret
    - password_confirmation
    - passwordConfirmation
    - cc
    - card_number
    - cardNumber
    - ccv
    - ssn
    - credit_score
    - creditScore
    - authorization
    - x-api-key
    - access_token
```

**Disabling masking entirely:**

```yaml
treblle:
  masked_keywords: []
```

### `excluded_paths`

Paths that should not be tracked by Treblle. Supports exact paths and wildcard patterns.

```yaml
treblle:
  excluded_paths:
    - health           # exact: /health
    - health/check     # exact: /health/check
    - admin/*          # wildcard: /admin/anything
    - internal/*       # wildcard: /internal/anything
```

Paths are matched against the request's path without the leading `/`.

### `ingress_url`

Override the default ingress endpoint. Useful for EU-hosted or self-hosted Treblle deployments:

```yaml
treblle:
  ingress_url: "https://ingress-eu.treblle.com"
```

### `async`

When `true`, the SDK dispatches payloads as Symfony Messenger messages instead of sending them inline. This moves the HTTP call to a background worker process, freeing your application immediately.

**When to use async mode:**

By default, the SDK sends data in the `kernel.terminate` event - after the HTTP response has already been delivered to your client (Symfony calls `fastcgi_finish_request()` first). For most applications this is sufficient.

Enable `async: true` if:
- You run at high request volume and need to free PHP-FPM workers immediately rather than holding them during the HTTP call to Treblle
- Your hosting environment does not use PHP-FPM (e.g. Apache mod_php, Swoole) and you want guaranteed zero impact on response time

**Setup:**

1. Install Symfony Messenger:

```bash
composer require symfony/messenger
```

2. Configure a transport in `config/packages/messenger.yaml`. We recommend Redis or AMQP - the Doctrine (database) transport is **not recommended** at scale as it adds a DB write per request.

**Redis:**
```yaml
framework:
    messenger:
        transports:
            treblle:
                dsn: "%env(REDIS_URL)%"
                options:
                    stream: treblle_payloads

        routing:
            'Treblle\Symfony\Messenger\SendTrebllePayload': treblle
```

**AMQP (RabbitMQ):**
```yaml
framework:
    messenger:
        transports:
            treblle:
                dsn: "%env(RABBITMQ_URL)%"
                options:
                    exchange:
                        name: treblle

        routing:
            'Treblle\Symfony\Messenger\SendTrebllePayload': treblle
```

3. Enable async in your Treblle config:

```yaml
treblle:
  async: true
```

4. Start the Messenger worker:

```bash
php bin/console messenger:consume treblle --time-limit=3600
```

Run this as a supervised process (Supervisor, systemd, etc.) so it restarts automatically.

**Fallback behaviour:**

If `async: true` is set but `symfony/messenger` is not installed, the SDK silently falls back to the default synchronous send. No errors, no data loss - it just skips the queue.

---

## Migrating from v3 to v4

### 1. Update the package

```bash
composer require treblle/treblle-symfony:^4.0
```

### 2. Update your config file

The following keys changed in `config/packages/treblle.yaml`:

| v3 key | v4 key | Notes |
|---|---|---|
| `masked_fields` | `masked_keywords` | Renamed |
| `url` | `ingress_url` | Renamed |
| `ignored_environments` | _(removed)_ | Use `enabled` instead (see below) |
| `debug` | _(removed)_ | Use Monolog `treblle` channel instead |
| `excluded_headers` | `excluded_paths` | Different concept - now excludes by path, not header name |

**Before (v3):**

```yaml
treblle:
  api_key: "%env(TREBLLE_API_KEY)%"
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
  debug: false
  ignored_environments: dev,test,testing
  masked_fields:
    - password
    - secret
  excluded_headers:
    - Authorization
  url: "https://custom.treblle.com"
```

**After (v4):**

```yaml
treblle:
  api_key: "%env(TREBLLE_API_KEY)%"
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
  masked_keywords:
    - password
    - secret
  excluded_paths:
    - admin/*
  ingress_url: "https://custom.treblle.com"
```

### 3. Replace `ignored_environments` with `enabled`

v4 has no `ignored_environments` option. Instead, disable Treblle per environment using Symfony's standard config override mechanism:

```yaml
# config/packages/dev/treblle.yaml
treblle:
  enabled: false
```

```yaml
# config/packages/test/treblle.yaml
treblle:
  enabled: false
```

### 4. Remove the `debug` key

The `debug` flag no longer exists. Log output is controlled entirely through your `monolog.yaml` configuration via the `treblle` channel. See the [SDK Log Events](#sdk-log-events) section for details.

### 5. Review `excluded_headers` vs `excluded_paths`

`excluded_headers` (v3) excluded specific header names from being tracked. `excluded_paths` (v4) excludes entire request paths from being tracked. These are different concepts - if you were using `excluded_headers`, review whether `excluded_paths` covers your use case, and use `masked_keywords` if you need to hide sensitive header values.

### 6. Clear your cache

```bash
php bin/console cache:clear
```

---

## SDK Log Events

The SDK logs through Symfony's standard logging system using a dedicated `treblle` Monolog channel. There is no separate debug flag - log visibility is controlled entirely by your existing `monolog.yaml` configuration, exactly as you would for any other Symfony component.

### What gets logged

| Level     | Message                                              |
|-----------|------------------------------------------------------|
| `debug`   | Payload sent (with HTTP status code)                 |
| `debug`   | Skipped paths, disabled state, async dispatch        |
| `warning` | Missing `sdk_token` or `api_key` configuration       |
| `warning` | cURL errors or non-2xx responses from the ingress    |

Warnings from the SDK indicate transient network issues on Treblle's side or misconfiguration. They are intentionally `warning` rather than `error` so that a Treblle outage never pollutes your application's error logs.

### Viewing logs in development

The `treblle` channel appears automatically in the **Symfony Web Profiler** under the Logs tab. No configuration is needed - install the bundle and open any request in the profiler to see exactly what the SDK did.

### Routing logs to a dedicated file

To send Treblle logs to their own file, add a handler for the `treblle` channel in `config/packages/monolog.yaml`:

```yaml
monolog:
    handlers:
        treblle:
            type: stream
            path: "%kernel.logs_dir%/treblle.log"
            level: debug
            channels: [treblle]
```

### Silencing Treblle logs in production

If you want to suppress all Treblle log output in production, exclude the channel from your existing handlers:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            channels: ['!treblle']  # exclude treblle from your main handler
```

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
