/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent'
    ],
    function (Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/summary/items/details'
            },
            getValue: function (quoteItem) {
                return quoteItem.name;
            }
        });
    }
);
