/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
    'Evalent_EcsterPay/js/model/shipping-save-processor',
    'Evalent_EcsterPay/js/model/quote',
    'Evalent_EcsterPay/js/model/ecster'
], function (
    $,
    addressConverter,
    selectShippingAddress,
    shippingSaveProcessor,
    quote,
    ecster
) {

    'use strict';

    var observedValues = {};
    $(document).ready(function () {
        $(document).on('change', "[name='country_id']", function () {
            if (ecster.isUpdateCountry()) {
                observedValues['country_id'] = $(this).children("option:selected").val();
                var address = addressConverter.formAddressDataToQuoteAddress(observedValues);
                selectShippingAddress(address);
                var updateCartCallBack = ecster.updateInitCart(quote.getEcsterCartKey());
                var setShippingInformationAction = shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
                setShippingInformationAction.done(
                    function () {
                        updateCartCallBack(quote.getEcsterCartKey());
                    }
                ).fail(
                    function () {
                        updateCartCallBack(quote.getEcsterCartKey());
                    }
                )
            }
            ecster.setUpdateCountry();
        });
    });
});