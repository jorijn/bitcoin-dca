.. _faq:

Frequently Asked Questions
==========================

.. toctree::
   :caption: Table of Contents
   :maxdepth: 2

   faq

I already have MyNode running, can I use this tool too?
-------------------------------------------------------

Yes! MyNode is based on Linux and has Docker already installed. You can use all features of BL3P-DCA.

Things you should keep in mind: The default user, ``admin`` doesn't have permission to run Docker by default. Instead, you can prefix all ``docker`` commands with ``sudo``.

.. code-block:: bash
   :caption: Example with sudo

   $ sudo docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest balance

See :ref:`getting-started` for more information.
