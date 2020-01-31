/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Evalent_EcsterPay/js/view/summary/abstract-total',
    'Evalent_EcsterPay/js/model/quote'
], function ($, Component, quote) {
    'use strict';

    var displayMode = window.checkoutConfig.reviewShippingDisplayMode;

    return Component.extend({
        defaults: {
            displayMode: displayMode,
            template: 'Evalent_EcsterPay/summary/shipping'
        },
        quoteIsVirtual: quote.isVirtual(),
        totals: quote.getTotals(),
        getShippingMethodTitle: function () {
            if (!this.isCalculated()) {
                return '';
            }
            var shippingMethod = quote.shippingMethod();
            return shippingMethod ? shippingMethod.carrier_title + " - " + shippingMethod.method_title : '';
        },
        isCalculated: function () {
            return this.totals() && null != quote.shippingMethod();
        },
        getValue: function () {
            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            var price = this.totals().shipping_amount;
            return this.getFormattedPrice(price);
        },
        isBothPricesDisplayed: function () {
            return this.displayMode == 'both';
        },
        isIncludingDisplayed: function () {
            return this.displayMode == 'including';
        },
        isExcludingDisplayed: function () {
            return this.displayMode == 'excluding';
        },
        getIncludingValue: function () {
            var price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_incl_tax'];

            return this.getFormattedPrice(price);
        },
        getExcludingValue: function () {
            var price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_amount'];

            return this.getFormattedPrice(price);
        }
    });
});

