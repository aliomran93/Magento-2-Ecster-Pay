/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/model/resource-url-manager',
        'Evalent_EcsterPay/js/model/quote',
        'mage/storage',
        'Evalent_EcsterPay/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/error-processor',
        'Evalent_EcsterPay/js/model/ecster'
    ],
    function (
        resourceUrlManager,
        quote,
        storage,
        shippingService,
        rateRegistry,
        errorProcessor,
        ecster
    ) {
        "use strict";
        return {
            getRates: function (address) {
                shippingService.isLoading(true);
                storage.post(
                    resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId(),
                    JSON.stringify({
                        addressId: address.customerAddressId
                    }),
                    false
                ).done(
                    function (response) {
                        shippingService.setShippingRates(response);
                    }
                ).fail(
                    function () {
                        shippingService.setShippingRates([]);
                    }
                ).always(
                    function () {
                        shippingService.isLoading(false);
                    }
                );
            }
        };
    }
);
