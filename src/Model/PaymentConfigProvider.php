<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Cart;
use Magento\Payment\Model\CcConfig;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;

class PaymentConfigProvider implements ConfigProviderInterface
{
    protected $_ccConfig;
    protected $_cart;
    protected $_helper;
    protected $_ecsterapi;

    public function __construct(
        CcConfig $ccConfig,
        Cart $cart,
        EcsterHelper $helper,
        EcsterApi $ecsterapi
    ) {
        $this->_ccConfig = $ccConfig;
        $this->_cart = $cart;
        $this->_helper = $helper;
        $this->_ecsterapi = $ecsterapi;
    }

    public function getConfig()
    {

        $config = [];

        if ($this->_helper->isEnabled($this->_cart->getQuote()->getStoreId())) {
            $config = [
                'payment' => [
                    'ecsterpay' => [
                        'active' => $this->_helper->isEnabled($this->_cart->getQuote()->getStoreId()),
                        'mode' => $this->_helper->getTransactionMode($this->_cart->getQuote()->getStoreId()),
                        'testModeMessage' => $this->_helper->getTestModeMessage($this->_cart->getQuote()->getStoreId()),
                        'jsUrl' => $this->_ecsterapi->getJsUrl($this->_cart->getQuote()->getStoreId()),
                        'shopTermsUrl' => $this->_ecsterapi->getShopTermsUrl(),
                        'successUrl' => $this->_ecsterapi->getReturnUrl(),
                        'cartUrl' => $this->_ecsterapi->getCartUrl(),
                        'purchaseType' => $this->_helper->getPurchaseType(),
                        'preselectedPurchaseType' => $this->_helper->getPreselectedPurchaseType(),
                        'showCart' => $this->_helper->getShowCart($this->_cart->getQuote()->getStoreId()),
                        'showDelivery' => $this->_helper->getShowDeliveryMethods($this->_cart->getQuote()->getStoreId()),
                        'showPaymentResult' => $this->_helper->showPaymentResult($this->_cart->getQuote()->getStoreId()),
                        'isMultipleCountry' => $this->_helper->isMultipleCountry($this->_cart->getQuote()->getStoreId()),
                        'defaultCountry' => $this->_helper->getDefaultCountry($this->_cart->getQuote()->getStoreId()),
                        'defaultShippingMethod' => $this->_helper->getDefaultShippingMethod(
                            $this->_helper->getDefaultCountry($this->_cart->getQuote()->getStoreId()),
                            $this->_cart->getQuote()->getStoreId()
                        ),
                        'singleShippingMethod' => $this->_helper->getSingleShippingMethod($this->_cart->getQuote())
                    ]
                ]
            ];
        }

        return $config;
    }
}
