<div align="center">
  <img src="https://github.com/user-attachments/assets/6331a3c4-5165-4761-90a6-f462dca0edc5"/>
</div>
<div align="center">

# Treblle

<a href="https://docs.treblle.com/en/integrations" target="_blank">Integrations</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="http://treblle.com/" target="_blank">Website</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://docs.treblle.com" target="_blank">Docs</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://blog.treblle.com" target="_blank">Blog</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://twitter.com/treblleapi" target="_blank">Twitter</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://treblle.com/chat" target="_blank">Discord</a>
<br />

  <hr />
</div>

API Intelligence Platform. 🚀

Treblle is a lightweight SDK that helps Engineering and Product teams build, ship & maintain REST-based APIs faster.

## Features

<div align="center">
  <br />
  <img src="https://github.com/user-attachments/assets/0b2cc257-6a61-48f4-9ee3-c3693fc14b31"/>
  <br />
  <br />
</div>

- [API Monitoring & Observability](https://www.treblle.com/features/api-monitoring-observability)
- [Auto-generated API Docs](https://www.treblle.com/features/auto-generated-api-docs)
- [API analytics](https://www.treblle.com/features/api-analytics)
- [Treblle API Score](https://www.treblle.com/features/api-quality-score)
- [API Lifecycle Collaboration](https://www.treblle.com/features/api-lifecycle)
- [Native Treblle Apps](https://www.treblle.com/features/native-apps)


## How Treblle Works
Once you’ve integrated a Treblle SDK in your codebase, this SDK will send requests and response data to your Treblle Dashboard.

In your Treblle Dashboard you get to see real-time requests to your API, auto-generated API docs, API analytics like how fast the response was for an endpoint, the load size of the response, etc.

Treblle also uses the requests sent to your Dashboard to calculate your API score which is a quality score that’s calculated based on the performance, quality, and security best practices for your API.

> Visit [https://docs.treblle.com](http://docs.treblle.com) for the complete documentation.

## Security

### Masking fields
Masking fields ensure certain sensitive data are removed before being sent to Treblle.

To make sure masking is done before any data leaves your server [we built it into all our SDKs](https://docs.treblle.com/en/security/masked-fields#fields-masked-by-default).

This means data masking is super fast and happens on a programming level before the API request is sent to Treblle. You can [customize](https://docs.treblle.com/en/security/masked-fields#custom-masked-fields) exactly which fields are masked when you’re integrating the SDK.

> Visit the [Masked fields](https://docs.treblle.com/en/security/masked-fields) section of the [docs](https://docs.sailscasts.com) for the complete documentation.


## Get Started

1. Sign in to [Treblle](https://identity.treblle.com/login).
2. [Create a Treblle project](https://docs.treblle.com/en/dashboard/projects#creating-a-project).
3. [Setup the SDK](#install-the-SDK) for your platform.

### Install the SDK

```sh
composer require treblle/treblle-symfony
```

Enable it by adding it to the list of registered bundles in `config/bundles.php`:

```php
// config/bundles.php
return [
    // ...
    Treblle\Symfony\TreblleBundle::class => ['all' => true],
];
```

Configure the SDK by adding the following snippet to `config/packages/treblle.yaml`:

```yaml
treblle:
  api_key: "%env(TREBLLE_API_KEY)%"
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
  debug: false
  ignored_environments: dev,test,testing
  masked_fields:
    - password
    - pwd
    - secret
    - password_confirmation
    - cc
    - card_number
    - ccv
    - ssn
    - credit_score
  excluded_headers:
    - Authorization
    - X-Api-Key
```

### Configuration Options

- `api_key` (required): Your Treblle API key (project ID)
- `sdk_token` (required): Your Treblle SDK token
- `debug` (optional, default: `false`): Enable debug mode for development
- `ignored_environments` (optional, default: `dev,test,testing`): Comma-separated list of environments to ignore
- `masked_fields` (optional): Array of field names to mask in request/response data
- `excluded_headers` (optional): Array of header patterns to exclude from tracking
- `url` (optional): Custom Treblle endpoint URL for self-hosted instances

### Requirements

- PHP 8.2, 8.3, or 8.4
- Symfony 6.4, 7.x, or 8.x
- JSON extension

> See the [docs](https://docs.treblle.com/en/integrations/symfony) for this SDK to learn more.

### Queue Support
Symfony sdk now support Messanger to send queued messages
To enable queued transmissions add `queue_enabled: true` to treblle config file.
After adding, file should look like this

```yaml
treblle:
  api_key: "%env(TREBLLE_API_KEY)%"
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
  debug: false
  ignored_environments: dev,test,testing
  masked_fields:
    - password
    - pwd
    - secret
    - password_confirmation
    - cc
    - card_number
    - ccv
    - ssn
    - credit_score
  excluded_headers:
    - Authorization
    - X-Api-Key
  queue_enabled: true
```

Additionally, it is necessary to add treblle transport to messanger.yaml file

```yaml
framework:
    messenger:
       # Uncomment this (and the failed transport below) to send failed messages to this transport for later handling.
       # failure_transport: failed

       transports:
          # https://symfony.com/doc/current/messenger.html#transport-configuration
          # async: '%env(MESSENGER_TRANSPORT_DSN)%'
          # failed: 'doctrine://default?queue_name=failed'
          sync: 'sync://'
          treblle: "%env(MESSENGER_TRANSPORT_DSN)%"
```
Run `php bin/console debug:messenger` to confirm that new Message and Handler are added, you should see
```
Treblle\Symfony\Message\TransmitTreblleData                                                             
      handled by Treblle\Symfony\MessageHandler\TransmitTreblleDataHandler (when from_transport=treblle)
```

If new pair shows, then Treblle queued transmission is configured and ready to go.

This will enable Treblle transmissions to run asynchronously, but actual transmission flow will depend on type of Messenger
included in project (Redis is recommended).

## Available SDKs

Treblle provides [open-source SDKs](https://docs.treblle.com/en/integrations) that let you seamlessly integrate Treblle with your REST-based APIs.

- [`treblle-symfony`](https://github.com/Treblle/treblle-symfony): SDK for Symfony
- [`treblle-laravel`](https://github.com/Treblle/treblle-laravel): SDK for Laravel
- [`treblle-php`](https://github.com/Treblle/treblle-php): SDK for PHP
- [`treblle-lumen`](https://github.com/Treblle/treblle-lumen): SDK for Lumen
- [`treblle-sails`](https://github.com/Treblle/treblle-sails): SDK for Sails
- [`treblle-adonisjs`](https://github.com/Treblle/treblle-adonisjs): SDK for AdonisJS
- [`treblle-fastify`](https://github.com/Treblle/treblle-fastify): SDK for Fastify
- [`treblle-directus`](https://github.com/Treblle/treblle-directus): SDK for Directus
- [`treblle-strapi`](https://github.com/Treblle/treblle-strapi): SDK for Strapi
- [`treblle-express`](https://github.com/Treblle/treblle-express): SDK for Express
- [`treblle-koa`](https://github.com/Treblle/treblle-koa): SDK for Koa
- [`treblle-go`](https://github.com/Treblle/treblle-go): SDK for Go
- [`treblle-ruby`](https://github.com/Treblle/treblle-ruby): SDK for Ruby on Rails
- [`treblle-python`](https://github.com/Treblle/treblle-python): SDK for Python/Django

> See the [docs](https://docs.treblle.com/en/integrations) for more on SDKs and Integrations.

## Community 💙

First and foremost: **Star and watch this repository** to stay up-to-date.

Also, follow our [Blog](https://blog.treblle.com), and on [Twitter](https://twitter.com/treblleapi).

You can chat with the team and other members on [Discord](https://treblle.com/chat) and follow our tutorials and other video material at [YouTube](https://youtube.com/@treblle).

[![Treblle Discord](https://img.shields.io/badge/Treblle%20Discord-Join%20our%20Discord-F3F5FC?labelColor=7289DA&style=for-the-badge&logo=discord&logoColor=F3F5FC&link=https://treblle.com/chat)](https://treblle.com/chat)

[![Treblle YouTube](https://img.shields.io/badge/Treblle%20YouTube-Subscribe%20on%20YouTube-F3F5FC?labelColor=c4302b&style=for-the-badge&logo=YouTube&logoColor=F3F5FC&link=https://youtube.com/@treblle)](https://youtube.com/@treblle)

[![Treblle on Twitter](https://img.shields.io/badge/Treblle%20on%20Twitter-Follow%20Us-F3F5FC?labelColor=1DA1F2&style=for-the-badge&logo=Twitter&logoColor=F3F5FC&link=https://twitter.com/treblleapi)](https://twitter.com/treblleapi)

### How to contribute

Here are some ways of contributing to making Treblle better:

- **[Try out Treblle](https://docs.treblle.com/en/introduction#getting-started)**, and let us know ways to make Treblle better for you. Let us know here on [Discord](https://treblle.com/chat).
- Join our [Discord](https://treblle.com/chat) and connect with other members to share and learn from.
- Send a pull request to any of our [open source repositories](https://github.com/Treblle) on Github. Check the contribution guide on the repo you want to contribute to for more details about how to contribute. We're looking forward to your contribution!

## Upgrading

### Upgrading from v2.x to v3.0

Version 3.0 introduces **breaking changes** to align with treblle-php v5.0 naming conventions. This is a major release that requires configuration updates.

#### What's New in v3.0

1. **New Configuration Option: `excluded_headers`**
   - You can now exclude specific headers from being tracked
   - Supports exact matching, wildcards, and regex patterns

2. **Support for Guzzle v9.0**
   - The SDK now supports Guzzle HTTP client versions 7.x, 8.x, and 9.x

3. **Better Integration with treblle-php**
   - Now uses `SensitiveDataMasker` from treblle-php package
   - Removed custom helpers in favor of treblle-php utilities

4. **Updated Default Masked Fields**
   - Removed `api_key` from default masked fields
   - Updated to match treblle-php v5.0 defaults exactly

#### Breaking Changes

##### 1. Configuration Parameter Names (BREAKING)

The configuration parameter names have been updated to match treblle-php v5.0 conventions:

**Before (v2.x):**
```yaml
treblle:
  project_id: "%env(TREBLLE_PROJECT_ID)%"
  api_key: "%env(TREBLLE_API_KEY)%"
```

**After (v3.0):**
```yaml
treblle:
  api_key: "%env(TREBLLE_API_KEY)%"
  sdk_token: "%env(TREBLLE_SDK_TOKEN)%"
```

##### 2. Environment Variable Names (BREAKING)

You need to update your environment variables:

**Before (v2.x):**
- `TREBLLE_PROJECT_ID` - Your project ID
- `TREBLLE_API_KEY` - Your API key

**After (v3.0):**
- `TREBLLE_API_KEY` - Your project ID (this is what was TREBLLE_PROJECT_ID)
- `TREBLLE_SDK_TOKEN` - Your SDK token (this is what was TREBLLE_API_KEY)

**Important:** The *values* stay the same, only the variable names change:
```bash
# Before
TREBLLE_PROJECT_ID=your-project-id
TREBLLE_API_KEY=your-api-key

# After
TREBLLE_API_KEY=your-project-id      # Same value as old TREBLLE_PROJECT_ID
TREBLLE_SDK_TOKEN=your-api-key       # Same value as old TREBLLE_API_KEY
```

##### 3. Default Masked Fields (BREAKING)

The field `api_key` is no longer masked by default. If you need to mask it, add it explicitly:

```yaml
treblle:
  masked_fields:
    - api_key  # Add this if you need it masked
```

#### Step-by-Step Upgrade Instructions

1. **Update your composer.json**

   ```bash
   composer require treblle/treblle-symfony:^3.0
   ```

2. **Update your configuration file**

   Update `config/packages/treblle.yaml`:

   ```yaml
   treblle:
     api_key: "%env(TREBLLE_API_KEY)%"        # Was project_id
     sdk_token: "%env(TREBLLE_SDK_TOKEN)%"    # Was api_key
     debug: false
     ignored_environments: dev,test,testing
     masked_fields:
       - password
       - pwd
       - secret
       - password_confirmation
       - cc
       - card_number
       - ccv
       - ssn
       - credit_score
     excluded_headers:  # NEW in v3.0 (optional)
       - Authorization
       - X-Api-Key
   ```

3. **Update your .env file**

   ```bash
   # Update variable names (keep the same values!)
   TREBLLE_API_KEY=your-project-id        # This is your old TREBLLE_PROJECT_ID value
   TREBLLE_SDK_TOKEN=your-api-key-token   # This is your old TREBLLE_API_KEY value
   ```

4. **Clear your cache**

   ```bash
   php bin/console cache:clear
   ```

5. **Test your application**

   Verify that Treblle is tracking your API requests correctly by checking your Treblle dashboard.

#### Troubleshooting

**Issue: SDK not tracking requests after upgrade**

**Solution:** Clear your application cache and restart your web server:

```bash
php bin/console cache:clear
# If using PHP-FPM
sudo service php-fpm restart
# If using Symfony server
symfony server:restart
```

**Issue: Headers still being tracked despite excluded_headers**

**Solution:** Make sure the header names match exactly (case-sensitive) and clear your cache.

#### Need Help?

If you encounter any issues during the upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the configuration examples above
3. Open an issue on [GitHub](https://github.com/Treblle/treblle-symfony/issues)
4. Join our [Discord community](https://treblle.com/chat)

#### Rollback Instructions

If you need to rollback to v2.x:

```bash
composer require treblle/treblle-symfony:^2.0
php bin/console cache:clear
```

Note that v2.x configuration will continue to work in v3.0, so you can upgrade without immediately changing your config files.

### Contributors
<!-- Replace link with the link of the SDK contributors-->
<a href="https://github.com/Treblle/treblle-symfony/graphs/contributors">
  <p align="center">
    <img  src="https://contrib.rocks/image?repo=Treblle/treblle-symfony" alt="A table of avatars from the project's contributors" />
  </p>
</a>
