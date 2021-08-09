.. note::
   This guide is meant for people on Linux. You can use it on your VPS or Raspberry Pi. The Getting Started guide assumes you will be setting up Bitcoin DCA using the BL3P exchange. If you need to configure another exchange substitute the exchange specific configuration with the correct ones from :ref:`configuration`.

.. _getting-started:

Getting started
===============
.. note::
   See :ref:`Installation <installation>` on how to download the tool to your server.

Configuration
-------------
Create a new file somewhere that will contain the configuration needed for the tool to operate. If your account is called ``bob`` and your home directory is `/home/bob` lets create a new file in ``/home/bob/.bitcoin-dca``:

.. code-block:: bash
   :caption: /home/bob/.bitcoin-dca

   BL3P_PRIVATE_KEY=bl3p private key here
   BL3P_PUBLIC_KEY=bl3p identifier key here
   WITHDRAW_ADDRESS=hardware wallet address here

.. note::
   See :ref:`configuration` for all available options.

You can test that it work with:

.. code-block:: bash
   :caption: Checking the Exchange balance

   $ docker run --rm -it --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest balance

If successful, you should see a table containing your balances on the exchange:

.. code-block:: bash

   +----------+----------------+----------------+
   | Currency | Balance        | Available      |
   +----------+----------------+----------------+
   | BTC      | 0.00000000 BTC | 0.00000000 BTC |
   | EUR      | 10.0000000 EUR | 10.0000000 EUR |
   | BCH      | 0.00000000 BCH | 0.00000000 BCH |
   | LTC      | 0.00000000 LTC | 0.00000000 LTC |
   +----------+----------------+----------------+

Testing
-------
For safety, I recommend buying and withdrawing at least once manually to verify everything works before proceeding with automation.

Buying €10,00 of Bitcoin
^^^^^^^^^^^^^^^^^^^^^^^^
.. code-block:: bash

   $ docker run --rm -it --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest buy 10

Withdrawing to your hardware wallet
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   $ docker run --rm -it --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest withdraw --all

**It will ask you:** Ready to withdraw 0.00412087 BTC to Bitcoin Address bc1abcdefghijklmopqrstuvwxuz123456? A fee of 0.0003 will be taken as withdraw fee [y/N]:

.. warning::
   **When testing, make sure to verify the displayed Bitcoin address matches the one configured in your `.bitcoin-dca` configuration file. When confirming this question, withdrawal executes immediately.**

Automating buying and withdrawing
---------------------------------

.. include:: ./includes/cron-examples.rst
