/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Evalent_EcsterPay/js/view/summary/abstract-total'
], function (subtotal) {
    'use strict';

    var displayPriceMode = window.checkoutConfig.reviewItemPriceDisplayMode || 'including';

    return subtotal.extend({
        defaults: {
            displayArea: 'after_details',
            displayPriceMode: displayPriceMode,
            template: 'Evalent_EcsterPay/summary/items/details/subtotal'
        },

        getValue: function (quoteItem) {
            return this.getFormattedPrice(quoteItem['row_total']);
        },
        isPriceInclTaxDisplayed: function () {
            return displayPriceMode == 'both' || displayPriceMode == 'including';
        },
        isPriceExclTaxDisplayed: function () {
            return displayPriceMode == 'both' || displayPriceMode == 'excluding';
        },
        getValueInclTax: function (quoteItem) {
            return this.getFormattedPrice(quoteItem['row_total_incl_tax']);
        },
        getValueExclTax: function (quoteItem) {
            return this.getFormattedPrice(quoteItem['row_total']);
        }

    });
});
