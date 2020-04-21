define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Evalent_EcsterPay/js/model/ecster',
], function (
    $,
    customerData,
    ecster
) {
    'use strict';
    return function (amastySelect) {
        return amastySelect.extend({
            onChangeStore: function (storeId) {
                this._super(storeId);
                if (!isNaN(storeId)) {
                    customerData.set('am_pickup_store', storeId)
                } else { //If the user select the empty value we will automatically choose the first store in the list
                    let firstStoreId = this.options()[0].id
                    this._super(firstStoreId);
                    customerData.set('am_pickup_store', firstStoreId)
                }
                if (!ecster.isCheckoutUpdating()) {
                    $('#co-shipping-method-form').submit()
                }
            }
        })
    }
});
