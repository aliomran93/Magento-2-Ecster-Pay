/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'uiComponent',
        'underscore',
        'Magento_Checkout/js/model/step-navigator',
        'mage/translate',
        'Evalent_EcsterPay/js/model/config'
    ],
    function (
        ko,
        Component,
        _,
        stepNavigator,
        $t,
        ecsterConfig
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/checkout'
            },

            isVisible: ko.observable(true),
            stepCode: 'checkout',
            stepTitle: $t('Ecster Checkout'),
            isTestMode: ko.observable(ecsterConfig.mode),
            testModeMessage: ko.observable(ecsterConfig.testModeMessage),

            initialize: function () {
                this._super();
                // register your step
                stepNavigator.registerStep(
                    this.stepCode,
                    null,
                    this.stepTitle,
                    this.isVisible,
                    _.bind(this.navigate, this),
                    10
                );

                return this;
            },

            navigate: function () {
            },

            navigateToNextStep: function () {
                stepNavigator.next();
            }
        });
    }
);