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

    shippingAddress = ko.observable(null),
    shippingMethod = ko.observable(null),
    selectedShippingRate = ko.observable(null),
    shippingAddressFromData = window.checkoutConfig.shippingAddressFromData,
    billingAddressFromData = window.checkoutConfig.billingAddressFromData,
    quoteData = window.checkoutConfig.quoteData,
    paymentMethod = ko.observable(null),
    ecsterCartKey = quoteData['ecster_cart_key'],
    basePriceFormat = window.checkoutConfig.basePriceFormat,
    priceFormat = window.checkoutConfig.priceFormat,
    storeCode = window.checkoutConfig.storeCode,
    totalsData = proceedTotalsData(window.checkoutConfig.totalsData),
    totals = ko.observable(totalsData),
    collectedTotals = ko.observable({});

    return {
        totals: totals,
        shippingAddress: shippingAddress,
        shippingMethod: shippingMethod,
        billingAddress: shippingAddress,
        shippingAddressFromData: shippingAddressFromData,
        billingAddressFromData: billingAddressFromData,
        selectedShippingRate: selectedShippingRate,
        paymentMethod: paymentMethod,
        guestEmail: null,

        getQuoteId: function () {
            return quoteData['entity_id'];
        },

        isVirtual: function () {
            return !!Number(quoteData['is_virtual']);
        },

        getPriceFormat: function () {
            return priceFormat;
        },

        getBasePriceFormat: function () {
            return basePriceFormat;
        },

        getItems: function () {
            return window.checkoutConfig.quoteItemData;
        },

        getTotals: function () {
            return totals;
        },

        setTotals: function (data) {
            data = proceedTotalsData(data);
            totals(data);
            this.setCollectedTotals('subtotal_with_discount', parseFloat(data['subtotal_with_discount']));
        },

        setPaymentMethod: function (paymentMethodCode) {
            paymentMethod(paymentMethodCode);
        },

        getPaymentMethod: function () {
            return paymentMethod;
        },

        getStoreCode: function () {
            return storeCode;
        },

        setCollectedTotals: function (code, value) {
            var colTotals = collectedTotals();

            colTotals[code] = value;
            collectedTotals(colTotals);
        },

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
        }
    };
});
