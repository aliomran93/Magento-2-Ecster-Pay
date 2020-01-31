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
        'Evalent_EcsterPay/js/model/config'
    ],
    function (
        $,
        _,
        Component,
        ko,
        $t,
        ecsterConfig
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/mode'
            },

            isTestMode: ko.observable(ecsterConfig.mode),
            testModeMessage: ko.observable(ecsterConfig.testModeMessage),

            initialize: function () {
                this._super();
                return this;
            }
        });
    }
);