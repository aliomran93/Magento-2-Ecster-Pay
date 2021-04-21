/**
 * Copyright © Evalent Group AB, All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Evalent_EcsterPay/js/model/quote',
], function (quote) {
    'use strict';

    return function (billingAddress) {
        quote.billingAddress(billingAddress);
    };
});
