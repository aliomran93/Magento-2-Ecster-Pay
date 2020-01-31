/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent',
        'Evalent_EcsterPay/js/model/totals'
    ],
    function (
        Component,
        totals
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/summary'
            },
            isLoading: totals.isLoading
        });
    }
);