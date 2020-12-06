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
This DCA (Dollar Cost Averaging) tool is built with flexibility in mind, allowing you to specify your own schedule for buying and withdrawing.

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
     - Currencies
     - XPUB withdraw supported
   * - BL3P
     - https://bl3p.eu/
     - EUR
     - No *
   * - Bitvavo
     - https://bitvavo.com/
     - EUR
     - No *
   * - Kraken
     - https://kraken.com/
     - USD EUR CAD JPY GBP CHF AUD
     - No

.. note::
   Due to regulatory changes in The Netherlands, BL3P and Bitvavo currently require you to provide proof of address ownership, thus temporarily disabling Bitcoin-DCA's XPUB feature.

Telegram / Support
------------------
You can visit the Bitcoin DCA Support channel on Telegram: https://t.me/bitcoindca

Contributing
------------
Contributions are highly welcome! Feel free to submit issues and pull requests on https://github.com/jorijn/bitcoin-dca.

Like my work? Buy me a 🍺 by sending some sats to:

* on-chain: `bc1quqjfmnldh9nfnxpucyvxh9pc63jyp0qdkpmf32 <https://blockstream.info/address/bc1quqjfmnldh9nfnxpucyvxh9pc63jyp0qdkpmf32>`_
* lightning: `03e85b676b0e8c84088525a1377b075dc4e12197bf2974529a3a5fdbfb47e957a2 <https://1ml.com/node/03e85b676b0e8c84088525a1377b075dc4e12197bf2974529a3a5fdbfb47e957a2>`_

.. image:: ../resources/images/contribute_qr.png
