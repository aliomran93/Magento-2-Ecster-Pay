===============================
Ecster Pay Module for Magento 2
===============================

Installation
============

This module can only be installed with composer.

Install beta version
--------------------

First find the latest version you want to install under
`releases <https://github.com/evalentgroup/Magento-2-Ecster-Pay/releases>`_

Add the below to the "require" section of your composer.json file.

.. code:: json

  "evalent/module-ecsterpay": "1.0.0-beta.6 as 1.0.0"

The ``as 1.0.0`` part is only required if you specified ``"minimum-stability": "stable"``
in your composer.json file.

Then run the below command.

.. code::

  composer update evalent/module-ecsterpay

Install stable version
----------------------

Add the below to the "require" section of your composer.json file.

.. code:: json

  "evalent/module-ecsterpay": "^1.0"

Then run the below command.

.. code:: bash

  composer update evalent/module-ecsterpay

**Note:** There is currently no stable version released.

Licence
=======

Copyright © Evalent Group AB, Inc. All rights reserved.

See COPYING.txt for license details.
