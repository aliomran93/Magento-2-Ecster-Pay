/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent',
        'Evalent_EcsterPay/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Evalent_EcsterPay/js/model/totals'
    ],
    function (
        Component,
        quote,
        priceUtils,
        totals
    ) {
        "use strict";
        return Component.extend({
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },
            getTotals: function () {
                return totals.totals();
            },
            isFullMode: function () {
                if (window.checkoutConfig.isEnabledOneStepCheckout) {
                    return true;
                }
            }
        });
    }
);
