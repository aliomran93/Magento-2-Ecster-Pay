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
            storeIdSet: 0,
            onChangeStore: function (storeId) {
                // No need to reset the selected store
                if (storeId === this.storeIdSet) {
                    return;
                }
                var self = this;

                // If the checkout is updating we want to wait until it is ready to be set
                if (ecster.isCheckoutUpdating()) {
                    var updatingSubscription = ecster.isUpdating.subscribe(function (updating) {
                        if (!updating) {
                            updatingSubscription.dispose();
                            self.onChangeStore(storeId)
                        }
                    });
                    return
                }

                if (!isNaN(storeId)) {
                    this._super(storeId);
                    customerData.set('am_pickup_store', storeId)
                } else { //If the user select the empty value we will automatically choose the first store in the list
                    let storeId = this.options()[0].id
                    this._super(storeId);
                    customerData.set('am_pickup_store', storeId)
                }
                if (!ecster.isCheckoutUpdating()) {
                    this.storeIdSet = storeId
                    $('#co-shipping-method-form').submit()
                }
            }
        })
    }
});
