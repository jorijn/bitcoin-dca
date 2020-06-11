.. _installation:

Installation
============

.. include:: ./includes/beta-warning.rst

Requirements
------------
* You need to have an account on a supported Exchange;
* You need to have Docker installed: https://docs.docker.com/get-docker/;
* You need to have an API key active on a supported Exchange. It needs **read**, **trade** and **withdraw** permission.

.. include:: ./includes/add-user-to-docker-group.rst

Using Docker Hub (easiest)
--------------------------

Installing
^^^^^^^^^^
Use these commands to download this tool from Docker Hub:

.. code-block:: bash

   $ docker pull jorijn/bitcoin-dca:latest

Upgrading
^^^^^^^^^
Using these commands you can download the newest version from Docker Hub:

.. code-block:: bash

   $ docker image rm jorijn/bitcoin-dca
   $ docker pull jorijn/bitcoin-dca:latest

Build your own (more control)
-----------------------------
If you desire more control, pull this project from `GitHub <https://github.com/Jorijn/bitcoin-dca>`_ and build it yourself. To do this, execute these commands:

.. code-block:: bash

   cd ~
   git clone https://github.com/Jorijn/bitcoin-dca.git
   cd bitcoin-dca
   docker build . -t jorijn/bitcoin-dca:latest

When an upgrade is available, run ``git pull`` to fetch the latest changes and build the docker container again.

Next: :ref:`Configuration <configuration>`
