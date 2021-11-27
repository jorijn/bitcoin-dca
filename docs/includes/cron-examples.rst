The ``buy`` and ``withdraw`` command both allow skipping the confirmation questions with the ``--yes`` option. By leveraging the system's cron daemon on Linux, you can create flexible setups. Use the command ``crontab -e`` to edit periodic tasks for your user:

Since it's best to use absolute paths in crontabs, we'll be using ``$(command -v docker)`` to have it automatically determined for you.

.. code-block:: bash
   :caption: Finding out where Docker is located

   $ command -v docker
     -> /usr/bin/docker

Example: Buying €50.00 of Bitcoin and withdrawing every monday. Buy at 3am and withdraw at 3:30am.
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   0 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest buy 50 --yes --no-ansi
   30 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest withdraw --all --yes --no-ansi

Example: Buying €50.00 of Bitcoin every week on monday, withdrawing everything on the 1st of every month.
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

   0 3 * * mon $(command -v docker) run --rm --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest buy 50 --yes --no-ansi
   0 0 1 * * $(command -v docker) run --rm --env-file=/home/bob/.bitcoin-dca ghcr.io/jorijn/bitcoin-dca:latest withdraw --all --yes --no-ansi

.. note::
   You can use the great tool at https://crontab.guru/ to try more combinations.

Tips
----
* You can create and run this tool with different configuration files, e.g. different withdrawal addresses for your spouse, children or other saving purposes.
* Go nuts on security, use a different API keys for buying and withdrawal. You can even lock your BL3P account to only allow a single Bitcoin address for withdrawal through the API.
