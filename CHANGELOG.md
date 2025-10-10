# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2025-10-10

### Added
- Support for `excluded_headers` configuration option to exclude specific headers from tracking
- Full compatibility with treblle-php v5.0.0
- Support for Guzzle v9.0
- Support for Symfony 8.x (forward compatibility)
- Support for PHP 8.3 and 8.4
- Enhanced configuration documentation in README
- Detailed upgrade guide (UPGRADE.md)
- Requirements section in README
- Now uses `SensitiveDataMasker` from treblle-php package for better consistency

### Changed
- **BREAKING**: Configuration parameter names updated to match treblle-php v5.0 conventions:
  - `project_id` renamed to `api_key` (this holds your project ID)
  - `api_key` renamed to `sdk_token` (this holds your API key/SDK token)
  - Environment variables: `TREBLLE_PROJECT_ID` → `TREBLLE_API_KEY`, `TREBLLE_API_KEY` → `TREBLLE_SDK_TOKEN`
- **BREAKING**: Updated to treblle-php v5.0.0
- **BREAKING**: Minimum Symfony version raised from 4.4 to 6.4 (LTS)
- **BREAKING**: Removed custom `Normalise` helper in favor of inline normalization
- **BREAKING**: Removed `api_key` from default masked fields list
- PHP requirement updated to ^8.2|^8.3|^8.4 (added 8.3 and 8.4 support)
- Symfony requirement updated to ^6.4|^7.0|^8.0 (added Symfony 8 support)
- Refactored DataProviders to use `SensitiveDataMasker` from treblle-php instead of `FieldMasker`
- Updated exception messages to reflect new naming (missingSdkToken instead of missingProjectId)
- Improved configuration options documentation
- Updated bundle registration example for modern Symfony

### Removed
- **BREAKING**: `src/Helpers/Normalise.php` - functionality moved inline to DataProviders
- **BREAKING**: Default masking of `api_key` field (removed from default list)

### Fixed
- Aligned masked fields with treblle-php v5.0.0 defaults

## [2.0.0] - Previous Release

### Added
- Initial stable release with treblle-php v4.x support
- Custom masked fields configuration
- Debug mode support
- Ignored environments configuration
- Custom URL endpoint support

[3.0.0]: https://github.com/Treblle/treblle-symfony/compare/v2.0.0...v3.0.0
[2.0.0]: https://github.com/Treblle/treblle-symfony/releases/tag/v2.0.0
