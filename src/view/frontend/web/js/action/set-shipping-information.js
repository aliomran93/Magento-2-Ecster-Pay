/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Evalent_EcsterPay/js/model/quote',
    'Evalent_EcsterPay/js/model/shipping-save-processor'
], function (quote, shippingSaveProcessor) {
    'use strict';

    return function () {
        return shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
    };
});

