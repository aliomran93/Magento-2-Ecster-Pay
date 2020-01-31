/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Evalent_EcsterPay/js/model/totals',
        'uiComponent',
        'Evalent_EcsterPay/js/model/quote'
    ],
    function (
        $,
        ko,
        totals,
        Component,
        quote
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Evalent_EcsterPay/summary/items'
            },
            totals: totals.totals(),
            items: ko.observable([]),
            getItems: totals.getItems(),
            initialize: function () {
                this._super();
                this.setItems(totals.getItems()());
                totals.getItems().subscribe(function (items) {
                    this.setItems(items);
                }.bind(this));
            },
            setItems: function (items) {
                if (items && items.length > 0) {
                    items = items.slice(parseInt(-this.maxCartItemsToDisplay, 10));
                }
                this.items(items);
            },
            getItemsQty: function () {
                return parseFloat(this.totals.items_qty);
            }
        });
    }
);
