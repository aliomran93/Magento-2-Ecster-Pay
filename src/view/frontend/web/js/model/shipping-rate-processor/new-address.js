/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/model/resource-url-manager',
        'Evalent_EcsterPay/js/model/quote',
        'mage/storage',
        'Evalent_EcsterPay/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/error-processor',
        'Evalent_EcsterPay/js/model/ecster',
        'Evalent_EcsterPay/js/view/shipping'
    ],
    function (
        $,
        resourceUrlManager,
        quote,
        storage,
        shippingService,
        rateRegistry,
        errorProcessor,
        ecster,
        shipping
    ) {
        'use strict';

        return {

            getRates: function (address) {
                shippingService.isLoading(true);
                var serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote),
                    payload = JSON.stringify({
                        address: {
                            country_id: address.countryId
                        }
                    });

                storage.post(
                    serviceUrl,
                    payload,
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
