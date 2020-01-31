/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Evalent_EcsterPay/js/view/summary/abstract-total',
    'Evalent_EcsterPay/js/model/quote'
], function (Component, quote) {
    'use strict';

    var displaySubtotalMode = window.checkoutConfig.reviewTotalsDisplayMode;

    return Component.extend({
        defaults: {
            displaySubtotalMode: displaySubtotalMode,
            template: 'Evalent_EcsterPay/summary/subtotal'
        },
        getPureValue: function () {
            var totals = quote.getTotals()();
            if (totals) {
                return totals.subtotal;
            }
            return quote.subtotal;
        },
        totals: quote.getTotals(),
        getValue: function () {
            var price = 0;

            if (this.totals()) {
                price = this.totals().subtotal;
            }

            return this.getFormattedPrice(price);
        },
        isBothPricesDisplayed: function () {
            return this.displaySubtotalMode == 'both';
        },
        isIncludingTaxDisplayed: function () {
            return this.displaySubtotalMode == 'including';
        },
        getValueInclTax: function () {
            var price = 0;

            if (this.totals()) {
                price = this.totals()['subtotal_incl_tax'];
            }

            return this.getFormattedPrice(price);
        }
    });
});

