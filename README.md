# Bitcoin DCA using the BL3P Exchange

![Docker Pulls](https://img.shields.io/docker/pulls/jorijn/bl3p-dca)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bl3p-dca&metric=alert_status)](https://sonarcloud.io/dashboard?id=Jorijn_bl3p-dca)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bl3p-dca&metric=coverage)](https://sonarcloud.io/dashboard?id=Jorijn_bl3p-dca)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bl3p-dca&metric=ncloc)](https://sonarcloud.io/dashboard?id=Jorijn_bl3p-dca)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bl3p-dca&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bl3p-dca)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Jorijn_bl3p-dca&metric=security_rating)](https://sonarcloud.io/dashboard?id=Jorijn_bl3p-dca)

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
| Online |  https://bl3p-dca.readthedocs.io/en/latest/ |
| PDF | https://bl3p-dca.readthedocs.io/_/downloads/en/latest/pdf/ |
| ZIP | https://bl3p-dca.readthedocs.io/_/downloads/en/latest/htmlzip/ |
| ePub | https://bl3p-dca.readthedocs.io/_/downloads/en/latest/epub/ |
