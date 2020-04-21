/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'Evalent_EcsterPay/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Customer/js/customer-data'
    ],
    function (
        ko,
        quote,
        resourceUrlManager,
        storage,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        customerData
    ) {
        'use strict';

        return {

            saveShippingInformation: function () {

                var payload;

                payload = {
                    addressInformation: {
                        shipping_address: quote.shippingAddress(),
                        billing_address: quote.billingAddress(),
                        shipping_method_code: quote.shippingMethod() ? quote.shippingMethod()['method_code'] : null,
                        shipping_carrier_code: quote.shippingMethod() ? quote.shippingMethod()['carrier_code'] : null
                    }
                };

                if (quote.shippingMethod()['carrier_code'] == "amstorepickup" && !isNaN(customerData.get('am_pickup_store')()) && customerData.get('am_pickup_store')() != 0) {
                    payload.addressInformation.extension_attributes = {
                        am_pickup_store:customerData.get('am_pickup_store')()
                    }
                }

                fullScreenLoader.startLoader();

                selectBillingAddressAction(quote.shippingAddress());

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        if (response) {
                            quote.setTotals(response.totals);
                            if (response.extension_attributes && response.extension_attributes.ecster_cart_key) {
                                quote.setEcsterCartKey(response.extension_attributes.ecster_cart_key);
                            }
                        }
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);