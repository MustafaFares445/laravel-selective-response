# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release
- `SelectiveResponse` trait for filtering resource data
- `BaseApiResource` class extending Laravel's `JsonResource`
- `SelectiveResponseExtension` for Scramble API documentation
- Service provider with conditional Scramble registration
- Configuration file with package settings
- Comprehensive documentation and examples
- Unit tests for core functionality

### Features
- Automatic filtering of API responses based on `select()` queries
- Support for always-include fields
- Ability to disable filtering per-resource or globally
- Optional Scramble extension for accurate API documentation
- Zero breaking changes - just change parent class

