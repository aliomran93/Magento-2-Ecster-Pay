/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/country',
    'Evalent_EcsterPay/js/model/config',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
], function (_, registry, Select, ecsterConfig, addressConverter,selectShippingAddress) {
    'use strict';

    return Select.extend({
        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
        setInitialValue: function () {
            this.value(ecsterConfig.defaultCountry)
            if (_.isUndefined(this.value()) && !this.default) {
                this.clear();
            }

            return this._super();
        },
        countrySelected: function () {
            var address = addressConverter.formAddressDataToQuoteAddress(this.value());
            selectShippingAddress(address);
        }
    });
});

