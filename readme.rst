===============================
Ecster Pay Module for Magento 2
===============================

Installation
============

This module can only be installed with composer.

Install stable version
----------------------

Add the below to the "require" section of your composer.json file.

.. code:: json

  "evalent/module-ecsterpay": "^1.0"

Then run the below command.

.. code:: bash

  composer update evalent/module-ecsterpay

Install beta version
--------------------

First find the latest version you want to install under
`releases <https://github.com/evalentgroup/Magento-2-Ecster-Pay/releases>`_

Add the below to the "require" section of your composer.json file.

.. code:: json

  "evalent/module-ecsterpay": "1.0.8-beta.* as 1.0.8"

The ``as 1.0.0`` part is only required if you specified ``"minimum-stability": "stable"``
in your composer.json file.

Then run the below command.

.. code::

  composer update evalent/module-ecsterpay

Compatibility
=============

Because of the custom design and functions of the checkout some other third party extensions might not work with Ecster Checkout.
For example custom shipping methods might not work.

Here is a list of modules which has been tested and works with this module.

* Amasty_StorePickupWithLocator


Licence
=======

Copyright © Evalent Group AB, Inc. All rights reserved.

See COPYING.txt for license details.
