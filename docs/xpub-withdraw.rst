.. _xpub:

Deriving new Bitcoin addresses from your XPUB
=============================================

.. note::
   You need persistent storage to keep track of which address index the tool should use. See :ref:`persistent-storage`

Instead of withdrawing to the same static Bitcoin address every time you make a withdrawal, it's also possible to supply a Master Public Key to BL3P-DCA.

After configuring, BL3P-DCA will start at the first address (index #0) it can derive from your XPUB.

Configuring a XPUB
------------------
For the sake of demonstration, we'll be using the following XPUB here:

.. code-block::
   :caption: /home/bob/.bl3p-dca

   BL3P_WITHDRAW_XPUB=zpub6rLtzSoXnXKPXHroRKGCwuRVHjgA5YL6oUkdZnCfbDLdtAKNXb1FX1EmPUYR1uYMRBpngvkdJwxqhLvM46trRy5MRb7oYdSLbb4w5VC4i3z

.. warning::
   It's **very important** that you verify the configured XPUB to make sure your Bitcoin will be sent to addresses in your possession.

Verifying the configured XPUB
-----------------------------

You can verify that BL3P-DCA will derive the correct addresses using the following command:

.. code-block::
   :caption: Verifying the configured XPUB

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca-bobby jorijn/bl3p-dca:latest verify-xpub
   ┌───┬────────────────────────────────────────────┬───────────────┐
   │ # │ Address                                    │ Next Withdraw │
   ├───┼────────────────────────────────────────────┼───────────────┤
   │ 0 │ bc1qvqatyv2xynyanrej2fcutj6w5yugy0gc9jx2nn │ <             │
   │ 1 │ bc1q360p67y3jvards9f2eud5rlu07q8ampfp35vp7 │               │
   │ 2 │ bc1qs4k3p9w4ke5np3lr3lgnma9jcaxedau8mpwawu │               │
   │ 3 │ bc1qpk48z0s7gvyrupm2wmd7nr0fdzkxa42372ver2 │               │
   │ 4 │ bc1q0uam3l30y43q0wjhq0kwf050uyg23mz7p3frr4 │               │
   │ 5 │ bc1qef62h9xt937lu9x5ydv204r7lpk3sjdc575kax │               │
   │ 6 │ bc1q2rl0he7zca8a88ax7hf9259c33kd2ux5ffhkqw │               │
   │ 7 │ bc1qr9ffza3w6tae4g5m4ydnjvphg8tpgarf5yjgqz │               │
   │ 8 │ bc1qr65srxamrmx8zumgv5puljnd93u3sj7lw6cnrg │               │
   │ 9 │ bc1q2ufc8j9uw6x7hwqfsdakungk63etanxtkplel0 │               │
   └───┴────────────────────────────────────────────┴───────────────┘

   [WARNING] Make sure these addresses match those in your client, do not use the withdraw function is they do not.

You can check that the correct address is being used when attempting to withdraw your Bitcoin:

.. code-block::
   :caption: Here, it takes address #0 (bc1qvqatyv2xynyanrej2fcutj6w5yugy0gc9jx2nn) for withdrawal

   $ docker run --rm -it --env-file=/home/bob/.bl3p-dca-bobby jorijn/bl3p-dca:latestwithdraw --all
   Ready to withdraw 0.0013 BTC to Bitcoin Address bc1qvqatyv2xynyanrej2fcutj6w5yugy0gc9jx2nn? A fee of 0.0003 will be taken as withdraw fee. (yes/no) [no]:

After successful withdrawal, the tool will increase the inner index and use address #1 the next time a withdrawal is being made.
