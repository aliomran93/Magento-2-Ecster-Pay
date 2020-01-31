
define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Evalent_EcsterPay/js/model/ecster',
    'Evalent_EcsterPay/js/model/config',
    'mage/translate'
], function ($, Select, ecster, ecsterConfig) {
    'use strict';

    return Select.extend({
        defaults: {
            listens: {
                value: 'updateEcsterPurchaseType'
            }
        },

        /**
         * Extends instance with defaults, extends config with formatted values
         *     and options, and invokes initialize method of AbstractElement class.
         *     If instance's 'customEntry' property is set to true, calls 'initInput'
         */
        initialize: function () {
            this._super();
            this.setOptions([
                { label: $.mage.__('Private'), value: "B2C" },
                { label: $.mage.__('Business'), value: "B2B" },
            ]);
            this.value(ecsterConfig.preselectedPurchaseType)
            return this;
        },

        updateEcsterPurchaseType: function (value) {
            if(!ecster.updateCart(value)) {
                if (value == "B2B") {
                    this.value("B2C")
                } else {
                    this.value("B2B")
                }
            }
        }
    });
});

