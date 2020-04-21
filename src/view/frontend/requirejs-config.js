/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/quote': 'Evalent_EcsterPay/js/model/quote'
        }
    },
    config : {
        mixins: {
            'Amasty_StorePickupWithLocator/js/view/pickup/pickup-store': {
                'Evalent_EcsterPay/js/override/view/pickup/pickup-store': true
            }
        }
    }
};