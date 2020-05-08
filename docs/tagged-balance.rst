.. _tagged-balance:

Using tags to keep track of balance
===================================

.. note::
   You need persistent storage to keep track of tagged balances. See :ref:`persistent-storage`

What is tagging and what can I use it for?
------------------------------------------

Tagging is a multi tenant solution for DCA. It enables you to categorize each buy and maintain a balance for each category created. For example, you could use it to save some Bitcoin for your children. It's as easy as supplying.

Example: Bobby
-------

Lets say I have a kid called Bobby, I'm a true believer Bitcoin will someday rule the world and I would like to save some Bitcoin for him separately from my own account. I would then buy Bitcoin the regular way, except now I would provide a new argument: ``-t bobby``.

.. code-block:: bash
   :caption: Buying € 10,- of Bitcoin for Bobby

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 10 -t bobby

Then, when time comes to withdraw, you can withdraw his funds to a wallet of his own:

.. code-block:: bash
   :caption: Withdrawing all Bitcoin specifically for Bobby

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca-bobby jorijn/bl3p-dca:latest withdraw --all -t bobby

.. note::
   Docker can't handle both ``-e`` and ``--file-file``, it's best to create a seperate configuration file containing another xpub or withdrawal address.

Of course, other examples are possible, e.g. tagging balance for buying a house or a car.

Technical note, tagging works like this:
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* Buying 10.000 with tag ``mike``, Mike's balance is now 10.000, total balance 10.000;
* Buying 10.000 with tag ``bobby``, Bobby's balance is now 10.000, total balance 20.000;
* Buying 15.000 with tag ``mike``, Mike's balance is now 25.000, total balance 35.000;
* Withdrawing all for tag ``mike``, initiating a withdrawal for 25.000 leaving the balance for Mike at 0 and Bobby 10.000.
