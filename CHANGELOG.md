# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-04-19

### Added
- Initial release
- Model auto-discovery from app/Models
- Config-based whitelist for models and fields
- Search with LIKE queries (case-insensitive by default)
- Relationship support via dot notation (e.g., user.email)
- REST API with 3 endpoints
- Pagination with meta data
- Sorting support
- Caching for performance
- Facade for programmatic usage
- Form request validation
- SQL injection protection