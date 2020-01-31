/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/summary/totals'
            },
            isDisplayed: function () {
                return this.isFullMode();
            }
        });
    }
);