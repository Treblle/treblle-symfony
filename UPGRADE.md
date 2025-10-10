# Upgrade Guide

## Upgrading from v2.x to v3.0

### Overview

Version 3.0 introduces **breaking changes** to align with treblle-php v5.0 naming conventions. This is a major release that requires configuration updates.

### What's New in v3.0

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

### Breaking Changes

#### 1. Configuration Parameter Names (BREAKING)

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

#### 2. Environment Variable Names (BREAKING)

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

#### 3. Default Masked Fields (BREAKING)

The field `api_key` is no longer masked by default. If you need to mask it, add it explicitly:

```yaml
treblle:
  masked_fields:
    - api_key  # Add this if you need it masked
```

### Step-by-Step Upgrade Instructions

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

### Configuration Changes Summary

#### Before (v2.x)

```yaml
# config/packages/treblle.yaml
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

```bash
# .env
TREBLLE_PROJECT_ID=proj_abc123
TREBLLE_API_KEY=key_xyz789
```

#### After (v3.0)

```yaml
# config/packages/treblle.yaml
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
  excluded_headers:  # NEW in v3.0
    - Authorization
    - X-Api-Key
```

```bash
# .env
TREBLLE_API_KEY=proj_abc123      # Same value as old TREBLLE_PROJECT_ID
TREBLLE_SDK_TOKEN=key_xyz789     # Same value as old TREBLLE_API_KEY
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
