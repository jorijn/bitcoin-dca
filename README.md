_May 27th: This project was originally called "bl3p-dca" since I launched with BL3P at first. I would like to expand the list of supported exchanges and because of that, I chose to continue development under a more generic name. Be sure to update your Docker container from `jorijn/bl3p-dca` to `jorijn/bitcoin-dca`._

# Bitcoin DCA using the BL3P Exchange

![Docker Pulls](https://img.shields.io/docker/pulls/jorijn/bitcoin-dca)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=alert_status)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=coverage)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=ncloc)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bitcoin-dca&metric=security_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bitcoin-dca)

_Please be aware this is beta software. Test thoroughly with small amounts of money at first. This software is provided "as is" and comes without warranty. See [LICENSE](LICENSE)._

## Requirements
* You need to have an account on BL3P: https://bl3p.eu/.
* You need to have Docker installed: https://docs.docker.com/get-docker/
* You need to have an API key active on BL3P. Create one here: https://bl3p.eu/security. It needs **read**, **trade** and **withdraw** permission. For safety, I would recommend locking the API key to the IP address you are planning on running this tool from.

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
