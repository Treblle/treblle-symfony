# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2025-10-10

### Added
- Support for `excluded_headers` configuration option to exclude specific headers from tracking
- Full compatibility with treblle-php v5.0.0
- Support for Guzzle v9.0
- Enhanced configuration documentation in README
- Detailed upgrade guide (UPGRADE.md)

### Changed
- **BREAKING**: Updated to treblle-php v5.0.0 which changes the parameter naming in the underlying SDK
  - The SDK now passes `projectId` as `apiKey` and `apiKey` as `sdkToken` to TreblleFactory::create()
  - This is handled internally - no changes needed to your configuration
- Updated default masked fields to include `ccv` (was `ccb` in previous version)
- Improved configuration options documentation

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
