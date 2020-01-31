/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/view/messages',
    'Evalent_EcsterPay/js/model/discount/messages'
], function (Component, messageContainer) {
    'use strict';

    return Component.extend({
        initialize: function (config) {
            return this._super(config, messageContainer);
        }
    });
});
