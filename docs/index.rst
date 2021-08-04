Welcome to Bitcoin DCA's documentation!
====================================

.. toctree::
   :maxdepth: 1

   installation
   getting-started
   configuration
   scheduling
   persistent-storage
   xpub-withdraw
   tagged-balance
   faq

About this software
-------------------
This self-hosted DCA (Dollar Cost Averaging) tool is built with flexibility in mind, allowing you to specify your schedule for buying and withdrawing.

A few examples of possible scenario's:

* Buy each week, never withdraw;
* Buy monthly and withdraw at the same time to reduce exchange risk;
* Buy each week but withdraw only at the end of the month to save on withdrawal fees.

Supported Exchanges
-------------------

.. list-table::
   :header-rows: 1

   * - Exchange
     - URL
     - XPUB withdraw supported
     - Currencies
   * - BL3P
     - https://bl3p.eu/
     - Yes
     - EUR
   * - Bitvavo
     - https://bitvavo.com/
     - No *
     - EUR
   * - Kraken
     - https://kraken.com/
     - No
     - USD EUR CAD JPY GBP CHF AUD
   * - Binance
     - https://binance.com/
     - Yes
     - USDT BUSD EUR USDC USDT GBP AUD TRY BRL DAI TUSD RUB UAH PAX BIDR NGN IDRT VAI

.. note::
   Due to regulatory changes in The Netherlands, Bitvavo currently requires you to provide proof of address ownership, thus (temporarily) disabling Bitcoin-DCA's XPUB feature.

Telegram / Support
------------------
You can visit the Bitcoin DCA Support channel on Telegram: https://t.me/bitcoindca

Contributing
------------
Contributions are highly welcome! Feel free to submit issues and pull requests on https://github.com/jorijn/bitcoin-dca.

Like my work? Please buy me a 🍺 by sending some sats to https://jorijn.com/donate/
