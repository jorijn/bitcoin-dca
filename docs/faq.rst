.. _faq:

Frequently Asked Questions
==========================

.. toctree::
   :caption: Table of Contents
   :maxdepth: 2

   faq

I already have MyNode / Umbrel running, can I use this tool too?
----------------------------------------------------------------

Yes! MyNode and Umbrel are both based on Linux and have Docker pre-installed. You can use all features of Bitcoin DCA.

Things you should keep in mind: The default user doesn't have permission to run Docker by default. MyNode uses user ``admin`` and Umbrel uses ``umbrel``.

.. include:: ./includes/add-user-to-docker-group.rst

See :ref:`getting-started` for more information.

How do I make sure the time is set up correctly?
------------------------------------------------

You can check the current system time with this command:

.. code-block:: bash

   $ date
   Fri May 28 08:46:37 CEST 2021

In some cases, it is possible that the timezone is configured incorrectly. On MyNode, Umbrel or on any other Debian-based system you can update this as following:

.. code-block:: bash

   $ sudo dpkg-reconfigure tzdata
