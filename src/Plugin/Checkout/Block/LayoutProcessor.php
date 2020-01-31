<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Plugin\Checkout\Block;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Magento\Checkout\Model\Session as CheckoutSession;

class LayoutProcessor
{
    const visibleAddressFields = ['country_id'];
    const visibleAddressFieldSortOrder = ['country_id' => 10];

    protected $_helper;

    public function __construct(
        CheckoutSession $checkoutSession,
        EcsterPayHelper $ecsterpayHelper
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $ecsterpayHelper;
    }

    protected function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    protected function getAddress()
    {

        if ($this->getQuote()->getIsVirtual()) {
            return $this->getQuote()->getBillingAddress();
        }

        return $this->getQuote()->getShippingAddress();
    }

    public function aroundProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        \Closure $proceed,
        array $jsLayout
    ) {
        $ret = $proceed($jsLayout);

        if ($this->_helper->isEnabled()) {
            if (isset($ret['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress'])) {
                $returnLayout = [];

                $addressLayout = $ret['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children'];

                foreach ($addressLayout as $key => $field) {
                    if ($key == 'country_id') {
                        $field['value'] = !is_null($this->getAddress()->getCountryId()) ? $this->getAddress()->getCountryId() : $this->_helper->getDefaultCountry();
                    }
                    if (in_array($key, self::visibleAddressFields)) {
                        $returnLayout[$key] = $field;
                    }
                }

                $ret['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['shipping-address-fieldset']['children'] = $returnLayout;
            }
        }

        return $ret;
    }
}