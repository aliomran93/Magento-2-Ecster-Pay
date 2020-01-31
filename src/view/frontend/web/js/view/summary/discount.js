/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Evalent_EcsterPay/js/model/quote'
    ],
    function (
        Component,
        quote
    ) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/summary/discount'
            },
            totals: quote.getTotals(),
            isDisplayed: function () {
                return this.isFullMode() && this.getPureValue() != 0;
            },
            getCouponCode: function () {
                if (!this.totals()) {
                    return null;
                }
                return this.totals()['coupon_code'];
            },
            getPureValue: function () {
                var price = 0;
                if (this.totals() && this.totals().discount_amount) {
                    price = parseFloat(this.totals().discount_amount);
                }
                return price;
            },
            getValue: function () {
                return this.getFormattedPrice(this.getPureValue());
            }
        });
    }
);
