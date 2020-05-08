.. _installation:

Installation
============

.. include:: ./includes/beta-warning.rst

Requirements
------------
* You need to have an account on BL3P: https://bl3p.eu/.
* You need to have Docker installed: https://docs.docker.com/get-docker/
* You need to have an API key active on BL3P. Create one here: https://bl3p.eu/security. It needs **read**, **trade** and **withdraw** permission. For safety, I would recommend locking the API key to the IP address you are planning on running this tool from.

.. include:: ./includes/add-user-to-docker-group.rst

Using Docker Hub (easiest)
--------------------------

Installing
^^^^^^^^^^
Use these commands to download this tool from Docker Hub:

.. code-block:: bash

   $ docker pull jorijn/bl3p-dca:latest

Upgrading
^^^^^^^^^
Using these commands you can download the newest version from Docker Hub:

.. code-block:: bash

   $ docker image rm jorijn/bl3p-dca
   $ docker pull jorijn/bl3p-dca:latest

Build your own (more control)
-----------------------------
If you desire more control, pull this project from `GitHub <https://github.com/Jorijn/bl3p-dca>`_ and build it yourself. To do this, execute these commands:

.. code-block:: bash

   cd ~
   git clone https://github.com/Jorijn/bl3p-dca.git
   cd bl3p-dca
   docker build . -t jorijn/bl3p-dca:latest

When an upgrade is available, run ``git pull`` to fetch the latest changes and build the docker container again.

Next: :ref:`Configuration <configuration>`
