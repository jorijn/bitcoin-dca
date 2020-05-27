.. _persistent-storage:

Setting up persistent storage for Bitcoin DCA
==========================================

What do I need persistent storage for?
--------------------------------------
* :ref:`tagged-balance`
* :ref:`xpub`

Since it's plain buying and withdrawing, Bitcoin DCA doesn't need to remember any state for regular operations. However, when it comes to XPUB's and tagging, you do. In the case of tagging it has to remember how much balance each tag has and for XPUB's it needs to save the active index for address derivation. Since it's starting at 0, not saving the state would cause Bitcoin DCA to always return the same, first, address.

Currently, the internal applications stores the data at the ``/app/var/storage`` path but since this is an internal Docker container path, you will need to `mount <https://docs.docker.com/storage/volumes/>`_ a new location to this path to have the storage be persistent.

Picking a location
------------------
Lets create a new directory somewhere in your home directory. For this example, we'll assume your username is ``bob`` and your home directory is found located at ``/home/bob``.

.. include:: ./includes/finding-home-directory.rst

We'll be creating a new directory here where the files will be stored:

.. code-block:: bash

   $ mkdir -p /home/bob/applications/bitcoin-dca

Running with a mounted volume
-----------------------------

Now, when running this tool you need to mount the new storage directory onto the container using argument ``-v /home/bob/applications/bitcoin-dca:/var/storage``. A typical command could look like this:

.. code-block:: bash
   :caption: Running withdraw with a persistent storage directory

   $ docker run --rm -it --env-file=/home/bob/.bitcoin-dca -v /home/bob/applications/bitcoin-dca:/var/storage jorijn/bitcoin-dca:latest withdraw --all
