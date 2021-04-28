/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'mage/storage',
    'Evalent_EcsterPay/js/model/quote',
    'mage/url',
    'Magento_Customer/js/model/customer'
], function (storage, quote, urlBuilder) {
    'use strict';

    return function (paymentData) {
        var serviceUrl;

        return storage.get(
            urlBuilder.build('ecsterpay/checkout/validateQuote', {}), true, 'application/json'
        );
    };
});
