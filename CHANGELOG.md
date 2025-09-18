# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Initial release of the XML to JSON converter package.
- Support for XML to JSON conversion with options for namespace tagging and CDATA handling.
- Schema validation support (XSD and DTD).

## [1.0.0] - 2025-04-26
### Added
- `xmlToJson()` method for converting XML to JSON with options for validation and namespace handling.
- `xmlToArray()` method for converting XML to a PHP array with full namespace support.
- DTD validation if the `schema_path` is an empty string.
- XSD validation if a valid file path is provided for `schema_path`.
- Option to preserve CDATA content during conversion.

### Fixed
- Fixed XML validation handling for XSD and DTD.
- Improved error handling for invalid XML and failed schema validation.

### Changed
- Refactored XML to Array conversion logic to handle namespaces properly.
- Optimized error logging for validation failures.


## [1.0.2] - 2025-09-17

### Fixed
- Fixed error with displaying only one item if there are several items inside some xml tag.


## [1.0.3] - 2025-09-18

### Fixed
- Fixed error with parsing nested objects.
