/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'underscore'
], function (ko, _) {
    'use strict';
        var proceedTotalsData = function (data) {
            if (_.isObject(data) && _.isObject(data['extension_attributes'])) {
                _.each(data['extension_attributes'], function (element, index) {
                    data[index] = element;
                });
            }

            return data;
        },
        billingAddress = ko.observable(null),
        shippingAddress = ko.observable(null),
        shippingMethod = ko.observable(null),
        paymentMethod = ko.observable(null),
        quoteData = window.checkoutConfig.quoteData,
        basePriceFormat = window.checkoutConfig.basePriceFormat,
        priceFormat = window.checkoutConfig.priceFormat,
        storeCode = window.checkoutConfig.storeCode,
        totalsData = proceedTotalsData(window.checkoutConfig.totalsData),
        totals = ko.observable(totalsData),
        selectedShippingRate = ko.observable(null),
        shippingAddressFromData = window.checkoutConfig.shippingAddressFromData,
        billingAddressFromData = window.checkoutConfig.billingAddressFromData,
        ecsterCartKey = quoteData['ecster_cart_key'],
        collectedTotals = ko.observable({});

    return {
        totals: totals,
        shippingAddress: shippingAddress,
        shippingMethod: shippingMethod,
        billingAddress: billingAddress,
        paymentMethod: paymentMethod,
        guestEmail: null,
        // billingAddress: shippingAddress,
        shippingAddressFromData: shippingAddressFromData,
        billingAddressFromData: billingAddressFromData,
        selectedShippingRate: selectedShippingRate,

        /**
         * @return {*}
         */
        getQuoteId: function () {
            return quoteData['entity_id'];
        },

        /**
         * @return {Boolean}
         */
        isVirtual: function () {
            return !!Number(quoteData['is_virtual']);
        },

        /**
         * @return {*}
         */
        getPriceFormat: function () {
            return priceFormat;
        },

        /**
         * @return {*}
         */
        getBasePriceFormat: function () {
            return basePriceFormat;
        },

        /**
         * @return {*}
         */
        getItems: function () {
            return window.checkoutConfig.quoteItemData;
        },

        /**
         *
         * @return {*}
         */
        getTotals: function () {
            return totals;
        },

        /**
         * @param {Object} data
         */
        setTotals: function (data) {
            data = proceedTotalsData(data);
            totals(data);
            this.setCollectedTotals('subtotal_with_discount', parseFloat(data['subtotal_with_discount']));
        },

        /**
         * @param {*} paymentMethodCode
         */
        setPaymentMethod: function (paymentMethodCode) {
            paymentMethod(paymentMethodCode);
        },

        /**
         * @return {*}
         */
        getPaymentMethod: function () {
            return paymentMethod;
        },

        /**
         * @return {*}
         */
        getStoreCode: function () {
            return storeCode;
        },

        /**
         * @param {String} code
         * @param {*} value
         */
        setCollectedTotals: function (code, value) {
            var colTotals = collectedTotals();

            colTotals[code] = value;
            collectedTotals(colTotals);
        },

        /**
         * @return {Number}
         */
        getCalculatedTotal: function () {
            var total = 0.; //eslint-disable-line no-floating-decimal

            _.each(collectedTotals(), function (value) {
                total += value;
            });

            return total;
        },

        getShippingAddressFromData() {
            return shippingAddressFromData;
        },

        getBillingAddressFromData() {
            return billingAddressFromData;
        },

        setSelectedShippingRate(value) {
            selectedShippingRate = value;
        },

        getSelectedShippingRate() {
            return selectedShippingRate;
        },

        getCountryId: function () {
            return shippingAddress.country_id;
        },

        getEcsterCartKey: function () {
            return ecsterCartKey;
        },

        setEcsterCartKey: function (value) {
            ecsterCartKey = value;
        },
    }
});
