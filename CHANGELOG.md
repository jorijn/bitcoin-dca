# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v1.2.0] - 2020-12-08
### Warning
Due to regulatory changes in The Netherlands, BL3P and Bitvavo currently require you to provide proof of address ownership, thus temporarily breaking Bitcoin-DCA's XPUB feature.

### Added
* Added the logo to the README.md file #39
* Added CODE_OF_CONDUCT.md for people looking to contribute to Bitcoin DCA
* Add Kraken to the list of supported exchanges #40
* Allow other base currencies than EUR, with Kraken the list is expanded to USD, EUR, CAD, JPY, GBP, CHF & AUD #40
* Added lines to the project README.md to allow project funding through on-chain and lightning transactions

### Fixed
* Fixed a path in persistent storage documentation
* Fixed the DI container name, it was still called after the first launching exchange BL3P

### Changed
* Refactored the code to have one single point of truth for Bitcoin related facts like amount of satoshis and amount of decimals

### Removed
* Removed the beta warnings, the software has been in use for at least half a year now

## [v1.1.0] - 2020-07-05
### Added
* A Python based fallback mechanism for 32bits systems. Unfortunately a bug prevented Raspberry Pi users from using Xpubs properly. The system will automatically use the native, more advanced method or derivation for systems that are capable and degrade to the Python tool without people noticing.

[Unreleased]: https://github.com/Jorijn/bitcoin-dca/compare/v1.2.0...HEAD
[v1.2.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.1.0...v1.2.0
[v1.1.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.0.0...v1.1.0
