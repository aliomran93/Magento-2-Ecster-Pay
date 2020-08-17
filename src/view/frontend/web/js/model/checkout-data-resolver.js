/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Customer/js/model/address-list',
        'Evalent_EcsterPay/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Evalent_EcsterPay/js/model/ecster',
        'Evalent_EcsterPay/js/model/shipping-save-processor',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/action/select-billing-address',
        'Evalent_EcsterPay/js/model/config',
        'underscore'
    ],
    function (
        $,
        addressList,
        quote,
        checkoutData,
        ecster,
        shippingSaveProcessor,
        createShippingAddress,
        selectShippingAddress,
        selectShippingMethodAction,
        addressConverter,
        selectBillingAddress,
        ecsterConfig,
        _
    ) {
        'use strict';
        return {

            resolveEstimationAddress: function () {
                var address;
                if (quote.getShippingAddressFromData()) {
                    address = addressConverter.formAddressDataToQuoteAddress(quote.getShippingAddressFromData());
                    selectShippingAddress(address);
                } else {
                    this.resolveShippingAddress();
                }

                if (quote.isVirtual()) {
                    if (quote.getBillingAddressFromData()) {
                        address = addressConverter.formAddressDataToQuoteAddress(
                            quote.getBillingAddressFromData()
                        );
                        selectBillingAddress(address);
                    } else {
                        this.resolveBillingAddress();
                    }
                }
            },

            resolveBillingAddress: function () {
                createBillingAddress(quote.getBillingAddressFromData());
                this.applyBillingAddress();
            },

            resolveShippingAddress: function () {
                createShippingAddress(quote.getShippingAddressFromData());
                this.applyShippingAddress();
            },

            applyShippingAddress: function (isEstimatedAddress) {
                var address,
                    shippingAddress,
                    isConvertAddress,
                    addressData,
                    isShippingAddressInitialized;

                if (addressList().length == 0) {
                    address = addressConverter.formAddressDataToQuoteAddress(
                        quote.getShippingAddressFromData()
                    );
                    selectShippingAddress(address);
                }

                shippingAddress = quote.shippingAddress();
                isConvertAddress = isEstimatedAddress || false;

                if (!shippingAddress) {
                    isShippingAddressInitialized = addressList.some(function (addressFromList) {
                        if (checkoutData.getSelectedShippingAddress() == addressFromList.getKey()) {
                            addressData = isConvertAddress ?
                                addressConverter.addressToEstimationAddress(addressFromList)
                                : addressFromList;
                            selectShippingAddress(addressData);
                            return true;
                        }

                        return false;
                    });

                    if (!isShippingAddressInitialized) {
                        isShippingAddressInitialized = addressList.some(function (address) {
                            if (address.isDefaultShipping()) {
                                addressData = isConvertAddress ?
                                    addressConverter.addressToEstimationAddress(address)
                                    : address;
                                selectShippingAddress(addressData);

                                return true;
                            }

                            return false;
                        });
                    }

                    if (!isShippingAddressInitialized && addressList().length == 1) {
                        addressData = isConvertAddress ?
                            addressConverter.addressToEstimationAddress(addressList()[0])
                            : addressList()[0];
                        selectShippingAddress(addressData);
                    }
                }
            },

            applyBillingAddress: function (isEstimatedAddress) {
                var address,
                    billingAddress,
                    isConvertAddress,
                    addressData,
                    isBillingAddressInitialized;

                if (addressList().length == 0) {
                    address = addressConverter.formAddressDataToQuoteAddress(
                        quote.getBillingAddressFromData()
                    );
                    selectBillingAddress(address);
                }

                billingAddress = quote.billingAddress();
                isConvertAddress = isEstimatedAddress || false;

                if (!billingAddress) {
                    isBillingAddressInitialized = addressList.some(function (addressFromList) {
                        if (quote.getSelectedBillingAddress() == addressFromList.getKey()) {
                            addressData = isConvertAddress ?
                                addressConverter.addressToEstimationAddress(addressFromList)
                                : addressFromList;
                            selectBillingAddress(addressData);
                            return true;
                        }

                        return false;
                    });

                    if (!isBillingAddressInitialized) {
                        isBillingAddressInitialized = addressList.some(function (address) {
                            if (address.isDefaultBilling()) {
                                addressData = isConvertAddress ?
                                    addressConverter.addressToEstimationAddress(address)
                                    : address;
                                selectBillingAddress(addressData);

                                return true;
                            }

                            return false;
                        });
                    }

                    if (!isBillingAddressInitialized && addressList().length == 1) {
                        addressData = isConvertAddress ?
                            addressConverter.addressToEstimationAddress(addressList()[0])
                            : addressList()[0];
                        selectBillingAddress(addressData);
                    }
                }
            },

            resolveShippingRates: function (ratesData) {

                var selectedShippingRate = quote.getSelectedShippingRate(),
                    availableRate = false;
                if (quote.shippingMethod()) {
                    availableRate = _.find(ratesData, function (rate) {
                        return rate.carrier_code == quote.shippingMethod().carrier_code &&
                            rate.method_code == quote.shippingMethod().method_code;
                    });
                }

                if (!availableRate && selectedShippingRate) {
                    availableRate = _.find(ratesData, function (rate) {
                        return rate.carrier_code + '_' + rate.method_code === selectedShippingRate;
                    });
                }

                if (!availableRate && ecsterConfig.defaultShippingMethod) {
                    availableRate = true;
                    selectShippingMethodAction(ecsterConfig.defaultShippingMethod);
                }

                if (!availableRate) {
                    if (ratesData.length == 1) {
                        selectShippingMethodAction(ratesData[0]);
                        $('#co-shipping-method-form').submit();
                    } else {
                        selectShippingMethodAction(null);
                        var updateCartCallBack = ecster.updateInitCart(quote.getEcsterCartKey());
                        var setShippingInformationAction = shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
                        setShippingInformationAction.done(
                            function () {
                                updateCartCallBack(quote.getEcsterCartKey());
                            }
                        ).fail(
                            function () {
                                updateCartCallBack(quote.getEcsterCartKey());
                            }
                        )
                    }
                } else {
                    if (typeof (availableRate) === 'object') {
                        selectShippingMethodAction(availableRate);
                    } else {
                        selectShippingMethodAction(availableRate);
                    }
                    $('#co-shipping-method-form').submit();
                }
            }
        };
    }
);
