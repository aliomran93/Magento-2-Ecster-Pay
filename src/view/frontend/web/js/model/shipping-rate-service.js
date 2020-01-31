/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Evalent_EcsterPay/js/model/quote',
        'Evalent_EcsterPay/js/model/shipping-rate-processor/new-address',
        'Evalent_EcsterPay/js/model/shipping-rate-processor/customer-address'
    ],
    function (
        quote,
        defaultProcessor,
        customerAddressProcessor
    ) {
        "use strict";

        var processors = [];
        processors['default'] = defaultProcessor;
        processors['new-customer-address'] = defaultProcessor;
        processors['customer-address'] = customerAddressProcessor;

        quote.shippingAddress.subscribe(function () {
            var type = quote.shippingAddress().getType();
            if (processors[type]) {
                processors[type].getRates(quote.shippingAddress());
            } else {
                processors['default'].getRates(quote.shippingAddress());
            }
        });

        return {
            registerProcessor: function (type, processor) {
                processors[type] = processor;
            }
        }
    }
);
