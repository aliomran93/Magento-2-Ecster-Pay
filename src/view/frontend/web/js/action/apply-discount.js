/**
* Copyright © Evalent Group AB, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
define(
    [
        'ko',
        'jquery',
        'Evalent_EcsterPay/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/error-processor',
        'Evalent_EcsterPay/js/model/discount/messages',
        'mage/storage',
        'Magento_Checkout/js/action/get-totals',
        'mage/translate',
        'Evalent_EcsterPay/js/model/ecster'
    ],
    function (
        ko,
        $,
        quote,
        urlManager,
        errorProcessor,
        messageContainer,
        storage,
        getTotalsAction,
        $t,
        ecster
    ) {
        'use strict';

        return function (couponCode, isApplied, isLoading) {
            var updateCartCallBack = ecster.updateInitCart(quote.getEcsterCartKey());
            var quoteId = quote.getQuoteId(),
                url = urlManager.getApplyCouponUrl(couponCode, quoteId),
                message = $t('Your coupon was successfully applied');
                messageContainer.clear();

            return storage.put(
                url,
                {},
                false
            ).done(
                function (response) {
                    if (response) {
                        var deferred = $.Deferred();
                        var ecsterKey = response;
                        var responseMessage = "";
                        isLoading(false);
                        isApplied(true);
                        if (Array.isArray(response)) {
                            ecsterKey = response[0];
                            responseMessage = response[1];
                            isApplied(false);
                        }
                        getTotalsAction([], deferred);
                        $.when(deferred).done(function () {
                            quote.setEcsterCartKey(ecsterKey);
                            updateCartCallBack(ecsterKey);
                        });
                        if (responseMessage) {
                            messageContainer.addErrorMessage({'message': responseMessage});
                        } else {
                            messageContainer.addSuccessMessage({'message': message});
                        }
                    }
                }
            ).fail(
                function (response) {
                    isLoading(false);
                    quote.setEcsterCartKey(response);
                    updateCartCallBack(response);
                    errorProcessor.process(response, messageContainer);
                }
            );
        };
    }
);
