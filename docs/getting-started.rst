.. _getting-started:

.. note::
   This guide is meant for people on Linux. You can use it on your VPS or Raspberry Pi.

Getting started
===============
.. note::
   See :ref:`Installation <installation>` on how to download the tool to your server.

Configuration
-------------
Create a new file somewhere that will contain the configuration needed for the tool to operate. If your account is called ``bob`` and your home directory is `/home/bob` lets create a new file in ``/home/bob/.bl3p-dca``:

.. code-block::

   BL3P_PRIVATE_KEY=bl3p private key here
   BL3P_PUBLIC_KEY=bl3p identifier key here
   BL3P_WITHDRAW_ADDRESS=hardware wallet address here

.. note::
   See :ref:`configuration` for all available options.

You can test that it work with:

.. code-block:: bash

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest balance

If successful, you should see a table containing your balances on the exchange:

.. code-block::

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

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 10

Withdrawing to your hardware wallet
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest withdraw --all

**It will ask you:** Ready to withdraw 0.00412087 BTC to Bitcoin Address bc1abcdefghijklmopqrstuvwxuz123456? A fee of 0.0003 will be taken as withdraw fee [y/N]:

.. warning::
   **When testing, make sure to verify the displayed Bitcoin address matches the one configured in your `.bl3p-dca` configuration file. When confirming this question, withdrawal executes immediately.**

Automating buying and withdrawing
---------------------------------

The `buy` and `withdraw` command both allow skipping the confirmation questions with the `--yes` option. By leveraging the system's cron daemon on Linux, you can create flexible setups. Use the command `crontab -e` to edit periodic tasks for your user:

Example: Buying €50.00 of Bitcoin and withdrawing every monday. Buy at 3am and withdraw at 3:30am.
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   0 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 50 --yes --no-ansi
   30 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest withdraw --all --yes --no-ansi

Example: Buying €50.00 of Bitcoin every week on monday, withdrawing everything on the 1st of every month.
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   0 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 50 --yes --no-ansi
   0 0 1 * * $(command -v docker) run --rm --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest withdraw --all --yes --no-ansi

Example: Send out an email when Bitcoin was bought
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   0 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 50 --yes --no-ansi 2>&1 |mail -s "You just bought more Bitcoin!" youremail@here.com

You can use the great tool at https://crontab.guru/ to try more combinations.

Tips
----
* You can create and run this tool with different configuration files, e.g. different withdrawal addresses for your spouse, children or other saving purposes.
* On Linux, you can redirect the output to other tools, e.g. email yourself when Bitcoin is bought. Use ``--no-ansi`` to disable colored output.
* Go nuts on security, use a different API keys for buying and withdrawal. You can even lock your BL3P account to only allow a single Bitcoin address for withdrawal through the API.
* Although a bit technical, in a cron job, use ``2>&1`` to redirect any error output to the standard output stream. Basically this means that any error messages will show up in your email message too.
