# Changelog

All notable changes to `treblle/treblle-symfony` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-10

### Added

- Complete rewrite as a fully self-contained Symfony Bundle — no external `treblle-php` dependency
- `DataMasker`: recursive masking engine with built-in defaults (`password`, `cc`, `ssn`, etc.) and user-configurable additional keywords
- `TreblleClient`: GZIP-compressed, fire-and-forget cURL HTTP client for the Treblle ingress
- `PayloadBuilder`: assembles the full Treblle payload matching the canonical JSON schema
- `PathMatcher`: excluded path matching supporting exact paths and wildcard patterns (`admin/*`)
- `TreblleEventSubscriber`: Symfony kernel event listener (REQUEST, RESPONSE, EXCEPTION, TERMINATE)
- Configuration options: `sdk_token`, `api_key`, `debug`, `masked_keywords`, `excluded_paths`, `ingress_url`
- Debug mode with actionable `error_log()` messages for initialization, missing config, cURL errors, and HTTP responses
- Response size guard: bodies over 2MB are omitted and an error entry is added to the payload
- Route path resolution via `RouterInterface` for accurate API documentation
- Support for PHP 8.2, 8.3, 8.4
- Support for Symfony 6.4, 7.x, 8.x

### Changed

- Payload fields renamed to align with updated Treblle ingress schema:
  - `api_key` in payload now maps to the user's configured `api_key` (project identifier)
  - `sdk_token` in payload now maps to the user's configured `sdk_token` (ingress authentication)
  - `x-api-key` header still uses the `sdk_token` value
- Configuration key `masked_fields` → `masked_keywords`
- Configuration key `excluded_headers` removed (headers are now masked via `masked_keywords`)
- Configuration key `ignored_environments` removed (use `excluded_paths` instead for selective tracking)
- `ingress_url` replaces the implicit `url` option with a clearer name and explicit default

### Removed

- Dependency on `treblle/treblle-php`
- `SensitiveDataMasker` from the PHP SDK — replaced by built-in `DataMasker`
- `SymfonyRequestDataProvider` and `SymfonyResponseDataProvider` — logic folded into `PayloadBuilder`
- `TreblleFactory` usage — payload construction and delivery are now handled directly
