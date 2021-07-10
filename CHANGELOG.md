# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
## [v1.5.0] - 2021-07-10
### Added
* When you buy Bitcoin and supply `EXPORT_CSV=/location/to/file.csv`, Bitcoin-DCA will export the order information to that file in CSV format.

Please note that when you do, you need to tell Docker to mount a local file as a volume inside Bitcoin-DCA's container:

```shell
$ touch /location/to/bitcoin-dca/orders.csv # create the file if it does not exists yet
$ docker run <--usual-argments> -v /location/to/bitcoin-dca/orders.csv:/location/to/bitcoin-dca/orders.csv -e EXPORT_CSV=/location/to/bitcoin-dca/orders.csv buy 25 ... # example 
```

More information: https://bitcoin-dca.readthedocs.io/en/latest/persistent-storage.html

## [v1.4.1] - 2021-07-04
### Added
* Bitcoin-DCA will now check for updates after executing the command and let you know if a newer version is available. This can be disabled by setting `DISABLE_VERSION_CHECK=true`.

## [v1.4.0] - 2021-07-04
### Added
* Added an `--output` option to the buying command. This enables the user to print information about the purchase in the following formats:
  * YAML
  * JSON
  * XML
  * CSV

## [v1.3.2] - 2021-06-27
### Fixed
* Missed a default setting for diagnostics

## [v1.3.1] - 2021-06-27
### Added
* The HTTP client will now log relevant information about API calls when `DEBUG=1` is supplied in the configuration.

### Changes
* Migrated away from Travis to GitHub Actions

## [v1.3.0] - 2021-05-24
XPUB withdrawal is enabled again for BL3P since [the regulator acknowledged](https://bitonic.nl/en/news/231/dnb-formally-acknowledges-complaints-bitonic-and-revokes-wallet-verification-requirement) the view of Bitonic / BL3P. 

### Added
* Add Binance to the list of supported exchanges. (see #53)

### Changed
* Changed the documentation to include Umbrel in the F.A.Q.

## [v1.2.3] - 2021-03-15
### Fixed
* Reverted to Alpine Linux v3.12 because of an issue with seccomp. (see #47)

## [v1.2.2] - 2021-03-10
### Fixed
* Allow configuring of the "trading_agreement" setting for German citizens using Kraken.

## [v1.2.1] - 2020-12-21
### Added
* Command `version` to show the build information of the current running version, this should help with support.

### Fixed
* Time calculation failed on 32-bit systems such as ARMv7 (Raspberry Pi's). This fix replaces integer handling with string handling.

## [v1.2.0] - 2020-12-08
### Warning
Due to regulatory changes in The Netherlands, BL3P and Bitvavo currently require you to provide proof of address ownership, thus temporarily breaking Bitcoin-DCA's XPUB feature.

### Added
* Add Kraken to the list of supported exchanges
* Allow other base currencies than EUR, with Kraken the list is expanded to USD, EUR, CAD, JPY, GBP, CHF & AUD
* Added the logo to the README.md file
* Added CODE_OF_CONDUCT.md for people looking to contribute to Bitcoin DCA
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

[Unreleased]: https://github.com/Jorijn/bitcoin-dca/compare/v1.5.0...HEAD
[v1.5.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.4.1...v1.5.0
[v1.4.1]: https://github.com/Jorijn/bitcoin-dca/compare/v1.4.0...v1.4.1
[v1.4.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.3.2...v1.4.0
[v1.3.2]: https://github.com/Jorijn/bitcoin-dca/compare/v1.3.1...v1.3.2
[v1.3.1]: https://github.com/Jorijn/bitcoin-dca/compare/v1.3.0...v1.3.1
[v1.3.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.2.3...v1.3.0
[v1.2.3]: https://github.com/Jorijn/bitcoin-dca/compare/v1.2.2...v1.2.3
[v1.2.2]: https://github.com/Jorijn/bitcoin-dca/compare/v1.2.1...v1.2.2
[v1.2.1]: https://github.com/Jorijn/bitcoin-dca/compare/v1.2.0...v1.2.1
[v1.2.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.1.0...v1.2.0
[v1.1.0]: https://github.com/Jorijn/bitcoin-dca/compare/v1.0.0...v1.1.0
