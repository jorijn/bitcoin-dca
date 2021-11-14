<p align="center">
  <img src="/resources/images/dca-illustration.png?raw=true" alt="Bitcoin DCA">
</p>

# Bitcoin-DCA: Automated self-hosted Bitcoin DCA tool for multiple Exchanges

![Docker Pulls](https://img.shields.io/docker/pulls/jorijn/bitcoin-dca)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=alert_status)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=coverage)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=ncloc)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=security_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)

## Requirements
* You need to have an account on a supported exchange;
* You need to have Docker installed: https://docs.docker.com/get-docker/;
* You need to have an API key active on a supported exchange. It needs **read**, **trade** and **withdraw** permission.

## Supported Exchanges
| Exchange | URL | Currencies | XPUB withdraw supported |
|------|------|------|------|
| BL3P | https://bl3p.eu/ | EUR | Yes |
| Bitvavo | https://bitvavo.com/ | EUR | No * |
| Kraken | https://kraken.com/ | USD EUR CAD JPY GBP CHF AUD | No |
| Binance | https://binance.com/ | USDT BUSD EUR USDC USDT GBP AUD TRY BRL DAI TUSD RUB UAH PAX BIDR NGN IDRT VAI | Yes |

\* Due to regulatory changes in The Netherlands, Bitvavo currently requires you to provide proof of address ownership, thus temporarily disabling Bitcoin-DCA's XPUB feature.

## About this software
The DCA tool is built with flexibility in mind, allowing you to specify your schedule of buying and withdrawing. A few examples that are possible:

* Buy each week, never withdraw.
* Buy monthly and withdraw at the same time to reduce exchange risk.
* Buy each week but withdraw only at the end of the month to save on withdrawal fees.

## Documentation
| Format | Location | 
|------|------|
| Online |  https://bitcoin-dca.readthedocs.io/en/latest/ |
| PDF | https://bitcoin-dca.readthedocs.io/_/downloads/en/latest/pdf/ |
| ZIP | https://bitcoin-dca.readthedocs.io/_/downloads/en/latest/htmlzip/ |
| ePub | https://bitcoin-dca.readthedocs.io/_/downloads/en/latest/epub/ |

## Support
You can visit the Bitcoin DCA Support channel on Telegram: https://t.me/bitcoindca

## Contributing
Contributions are highly welcome! Feel free to submit issues and pull requests on https://github.com/jorijn/bitcoin-dca.

Like my work? Please buy me a 🍺 by sending some sats on https://jorijn.com/donate/
