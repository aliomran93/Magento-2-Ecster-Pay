/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'ko',
        'mage/translate',
        'Evalent_EcsterPay/js/model/ecster'
    ],
    function (
        $,
        _,
        Component,
        ko,
        $t,
        ecster
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/payment'
            },

            visible: ko.observable(true),

            initialize: function () {
                this._super();
                return this;
            },
            loadJsAfterKoRender: function () {
                ecster.init();
            }
        });
    }
);