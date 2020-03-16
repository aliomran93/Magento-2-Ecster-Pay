/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Evalent_EcsterPay/js/model/quote',
        'Evalent_EcsterPay/js/action/apply-discount',
        'Evalent_EcsterPay/js/action/cancel-discount',
        'Evalent_EcsterPay/js/model/config',
        'Evalent_EcsterPay/js/model/discount'
    ],
    function (
        $,
        ko,
        Component,
        quote,
        setCouponCodeAction,
        cancelCouponAction,
        ecsterConfig
    ) {

        'use strict';

        var totals = quote.getTotals();
        var couponCode = ko.observable(null);

        if (totals()) {
            couponCode(totals()['coupon_code']);
        }

        var isApplied = ko.observable(couponCode() != null);
        var isLoading = ko.observable(false);
        var isVisible = ko.observable(ecsterConfig.showDiscount);

        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/discount'
            },
            couponCode: couponCode,
            isApplied: isApplied,
            isLoading: isLoading,
            isVisible: isVisible,

            /**
             * Initializes component.
             *
             * @returns {Object} Chainable.
             */
            initialize: function () {
                this._super();
                console.log("Discount: " + ecsterConfig.showDiscount)

                return this;
            },

            apply: function () {
                if (this.validate()) {
                    isLoading(true);
                    setCouponCodeAction(couponCode(), isApplied, isLoading);
                }
            },

            cancel: function () {
                if (this.validate()) {
                    isLoading(true);
                    couponCode('');
                    cancelCouponAction(isApplied, isLoading);
                }
            },

            validate: function () {
                var form = '#discount-form';
                return $(form).validation() && $(form).validation('isValid');
            }

        });
    }
);
