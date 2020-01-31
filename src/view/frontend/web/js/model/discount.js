/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Evalent_EcsterPay/js/model/quote'
    ],
    function (
        $,
        ko,
        quote
    ) {
        'use strict';
        ko.bindingHandlers.myTestHandler = {
            init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
                if (window.discountEnable == 0) {
                    $('.ecsterpay-checkout-discount-wrapper').hide();
                }
            },
            update: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            }
        }
    }
);