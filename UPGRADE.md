# Upgrade Guide

## Upgrading from v2.x to v3.0

### Overview

Version 3.0 updates the underlying treblle-php package from v4.x to v5.0, which includes some improvements and new features. The good news is that **your configuration does not need to change** - the SDK handles the underlying changes internally.

### What's New in v3.0

1. **New Configuration Option: `excluded_headers`**
   - You can now exclude specific headers from being tracked
   - Add to your `config/packages/treblle.yaml`:

   ```yaml
   treblle:
     excluded_headers:
       - Authorization
       - X-Api-Key
   ```

2. **Support for Guzzle v9.0**
   - The SDK now supports Guzzle HTTP client versions 7.x, 8.x, and 9.x

3. **Updated Default Masked Fields**
   - The masked field `ccb` has been changed to `ccv` to align with treblle-php v5.0
   - All default masked fields now match treblle-php exactly

### Breaking Changes

#### Internal API Changes (No Action Required)

The underlying treblle-php v5.0 changed its parameter naming:
- Before: `TreblleFactory::create(apiKey: $apiKey, projectId: $projectId)`
- After: `TreblleFactory::create(apiKey: $projectId, sdkToken: $apiKey)`

**However**, this is handled internally by the Symfony SDK. Your configuration remains the same:

```yaml
treblle:
  project_id: "%env(TREBLLE_PROJECT_ID)%"
  api_key: "%env(TREBLLE_API_KEY)%"
```

The SDK automatically maps these values correctly to the new parameter names.

### Step-by-Step Upgrade Instructions

1. **Update your composer.json**

   ```bash
   composer require treblle/treblle-symfony:^3.0
   ```

2. **Clear your cache**

   ```bash
   php bin/console cache:clear
   ```

3. **(Optional) Add excluded_headers configuration**

   If you want to exclude certain headers from tracking, add the new `excluded_headers` option to your configuration:

   ```yaml
   treblle:
     project_id: "%env(TREBLLE_PROJECT_ID)%"
     api_key: "%env(TREBLLE_API_KEY)%"
     excluded_headers:
       - Authorization
       - X-Custom-Secret-Header
   ```

4. **Test your application**

   Verify that Treblle is still tracking your API requests correctly by checking your Treblle dashboard.

### Configuration Changes

#### Before (v2.x)

```yaml
treblle:
  project_id: "%env(TREBLLE_PROJECT_ID)%"
  api_key: "%env(TREBLLE_API_KEY)%"
  debug: false
  ignored_environments: dev
  masked_fields:
    - password
    - api_key
    - secret
```

#### After (v3.0)

```yaml
treblle:
  project_id: "%env(TREBLLE_PROJECT_ID)%"
  api_key: "%env(TREBLLE_API_KEY)%"
  debug: false
  ignored_environments: dev,test,testing
  masked_fields:
    - password
    - pwd
    - secret
    - password_confirmation
    - cc
    - card_number
    - ccv        # Changed from 'ccb' in v2.x
    - ssn
    - credit_score
    - api_key
  excluded_headers:  # NEW in v3.0
    - Authorization
    - X-Api-Key
```

### Troubleshooting

#### Issue: SDK not tracking requests after upgrade

**Solution:** Clear your application cache and restart your web server:

```bash
php bin/console cache:clear
# If using PHP-FPM
sudo service php-fpm restart
# If using Symfony server
symfony server:restart
```

#### Issue: Headers still being tracked despite excluded_headers

**Solution:** Make sure the header names match exactly (case-sensitive) and clear your cache.

### Need Help?

If you encounter any issues during the upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the [README.md](README.md) for configuration examples
3. Open an issue on [GitHub](https://github.com/Treblle/treblle-symfony/issues)
4. Join our [Discord community](https://treblle.com/chat)

### Rollback Instructions

If you need to rollback to v2.x:

```bash
composer require treblle/treblle-symfony:^2.0
php bin/console cache:clear
```

Note that the v2.x configuration will continue to work in v3.0, so you can upgrade without changing your config files.
