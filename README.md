<p align="center">
  <img src="/resources/images/logo.png?raw=true" alt="Bitcoin DCA Logo">
</p>

# Automated Bitcoin DCA tool for multiple Exchanges

![Docker Pulls](https://img.shields.io/docker/pulls/jorijn/bitcoin-dca)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=alert_status)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=coverage)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=ncloc)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=security_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)

_Please be aware this is beta software. Test thoroughly with small amounts of money at first. This software is provided "as is" and comes without warranty. See [LICENSE](LICENSE)._

## Requirements
* You need to have an account on a supported exchange;
* You need to have Docker installed: https://docs.docker.com/get-docker/;
* You need to have an API key active on a supported exchange. It needs **read**, **trade** and **withdraw** permission.

## Supported Exchanges
| Exchange | URL | Currencies |
|------|------|------|
| BL3P | https://bl3p.eu/ | EUR |
| Bitvavo | https://bitvavo.com/ | EUR |

## About this software
The DCA tool is built with flexibility in mind, allowing you to specify your own schedule of buying and withdrawing. A few examples that are possible:

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

Like my work? Buy me a 🍺 by sending some sats to `bc1quqjfmnldh9nfnxpucyvxh9pc63jyp0qdkpmf32` on-chain or on lightning: `03e85b676b0e8c84088525a1377b075dc4e12197bf2974529a3a5fdbfb47e957a2@37.97.173.245:9735`.

<p align="center">
    <img src="/resources/images/contribute_qr.png?raw=true" alt="QR code for a contribution">
</p>
