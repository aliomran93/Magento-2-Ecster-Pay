/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Checkout/js/model/address-converter',
        'Evalent_EcsterPay/js/model/quote',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Evalent_EcsterPay/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Evalent_EcsterPay/js/action/set-shipping-information-override',
        'Evalent_EcsterPay/js/action/set-shipping-information',
        'Evalent_EcsterPay/js/model/checkout-data-resolver',
        'uiRegistry',
        'Evalent_EcsterPay/js/model/config',
        'Evalent_EcsterPay/js/model/ecster',
        'mage/translate',
        'Magento_Customer/js/customer-data',
        'Evalent_EcsterPay/js/model/shipping-rate-service',
    ],
    function (
        $,
        _,
        Component,
        ko,
        addressConverter,
        quote,
        selectShippingAddress,
        selectBillingAddress,
        shippingService,
        selectShippingMethodAction,
        setShippingInformation,
        setShippingInformationAction,
        checkoutDataResolver,
        registry,
        ecsterConfig,
        ecster,
        $t,
        customerData
    ) {
        'use strict';

        var defaultCountry = {};

        return Component.extend({

            defaults: {
                template: 'Evalent_EcsterPay/shipping',
                shippingFormTemplate: 'Evalent_EcsterPay/shipping-address/form',
                purchaseTypeFormTemplate: 'Evalent_EcsterPay/purchase-type',
                shippingMethodListTemplate: 'Evalent_EcsterPay/shipping-address/shipping-method-list',
                shippingMethodItemTemplate: 'Evalent_EcsterPay/shipping-address/shipping-method-item'

            },
            visible: ko.observable(!quote.isVirtual()),
            isMultipleCountry: ko.observable(ecsterConfig.isMultipleCountry),
            isPurchaseTypeOptional: ko.observable(ecsterConfig.purchaseType == 'OPTIONAL'),
            singleShippingMethod: ecsterConfig.singleShippingMethod,
            errorValidationMessage: ko.observable(false),
            initCountryElCount: 0,

            initialize: function () {
                this._super();
                checkoutDataResolver.resolveEstimationAddress();
                if (window.checkoutConfig.selectedShippingMethod) {
                    this.selectShippingMethod(window.checkoutConfig.selectedShippingMethod)
                }

                return this;
            },

            rates: shippingService.getShippingRates(),
            isLoading: shippingService.isLoading,
            carriers: shippingService.getShippingCarriers(),
            ratesLength: ko.computed(function () {
                return shippingService.getShippingRates().length;
            }),



            isSelectedShippmentMethod: function (shippingMethod) {
                var selectedShippingMethod = null;
                if (quote.shippingMethod() && quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] !== 'undefined_undefined') {
                    selectedShippingMethod = quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] === shippingMethod ? quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] : null;
                } else if (this.singleShippingMethod != null) {
                    selectedShippingMethod = this.singleShippingMethod['carrier_code'] + '_' + this.singleShippingMethod['method_code'];
                }
                return selectedShippingMethod;
            },

            selectShippingMethod: function (shippingMethod) {
                selectShippingMethodAction(shippingMethod);
                quote.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);
                $('#co-shipping-method-form').submit();
                return true;
            },

            loadShipmentAfterKoRender: function () {
                if (this.singleShippingMethod != null) {
                    this.selectShippingMethod(this.singleShippingMethod);
                }
            },

            setShippingInformation: function () {
                if (ecster == null || ecster.isCheckoutUpdating()) {
                    return
                }

                if (this.validateShippingInformation()) {

                    // This adds support for Amasty_StorePickupWithLocator module
                    // The module throws an exception if some values are not set when saving the shipping information-
                    // and because this module saves it on every selection-
                    // we need to ignore that selection before these are set
                    if (quote.shippingMethod().carrier_code == "amstorepickup" && (isNaN(customerData.get('am_pickup_store')()) || customerData.get('am_pickup_store')() == 0)) {
                        return
                    }

                    var updateCartCallBack = ecster.updateInitCart(quote.getEcsterCartKey());
                    setShippingInformationAction().done(
                        function (response) {
                            updateCartCallBack(quote.getEcsterCartKey());
                        }
                    ).error(
                        function (response) {
                            updateCartCallBack(quote.getEcsterCartKey());
                        }
                    );
                }
            },

            validateShippingInformation: function () {

                var shippingAddress,
                    addressData;

                if (!quote.shippingMethod()) {
                    this.errorValidationMessage('Please specify a shipping method');
                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = addressConverter.formAddressDataToQuoteAddress(
                    this.source.get('shippingAddress')
                );

                for (var field in addressData) {
                    if (addressData.hasOwnProperty(field)
                        && shippingAddress.hasOwnProperty(field)
                        && typeof addressData[field] != 'function'
                    ) {
                        shippingAddress[field] = addressData[field];
                    }
                }

                selectShippingAddress(shippingAddress);
                selectBillingAddress(shippingAddress);

                return true;
            }
        });
    }
);
