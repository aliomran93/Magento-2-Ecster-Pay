/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent',
        'ko',
        'jquery',
        'Evalent_EcsterPay/js/model/config'
    ],
    function (
        Component,
        ko,
        $,
        ecsterConfig
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/review'
            },
            cartUrl: ecsterConfig.cartUrl,
        });
    }
);
