<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Cart;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;

class PaymentConfigProvider implements ConfigProviderInterface
{

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    private $ecsterHelper;

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    private $ecsterapi;

    /**
     * PaymentConfigProvider constructor.
     *
     * @param Cart        $cart
     * @param EcsterHelper      $ecsterHelper
     * @param EcsterApi $ecsterapi
     */
    public function __construct(
        Cart $cart,
        EcsterHelper $ecsterHelper,
        EcsterApi $ecsterapi
    ) {
        $this->cart = $cart;
        $this->ecsterHelper = $ecsterHelper;
        $this->ecsterapi = $ecsterapi;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = [];

        if ($this->ecsterHelper->isEnabled($this->cart->getQuote()->getStoreId())) {
            $config = [
                'payment' => [
                    'ecsterpay' => [
                        'active' => $this->ecsterHelper->isEnabled($this->cart->getQuote()->getStoreId()),
                        'mode' => $this->ecsterHelper->getTransactionMode($this->cart->getQuote()->getStoreId()),
                        'testModeMessage' => $this->ecsterHelper->getTestModeMessage($this->cart->getQuote()->getStoreId()),
                        'jsUrl' => $this->ecsterapi->getJsUrl($this->cart->getQuote()->getStoreId()),
                        'shopTermsUrl' => $this->ecsterapi->getShopTermsUrl(),
                        'successUrl' => $this->ecsterapi->getReturnUrl(),
                        'cartUrl' => $this->ecsterapi->getCartUrl(),
                        'purchaseType' => $this->ecsterHelper->getPurchaseType(),
                        'preselectedPurchaseType' => $this->ecsterHelper->getPreselectedPurchaseType(),
                        'showCart' => $this->ecsterHelper->getShowCart($this->cart->getQuote()->getStoreId()),
                        'showDelivery' => $this->ecsterHelper->getShowDeliveryMethods($this->cart->getQuote()->getStoreId()),
                        'showPaymentResult' => $this->ecsterHelper->showPaymentResult($this->cart->getQuote()->getStoreId()),
                        'isMultipleCountry' => $this->ecsterHelper->isMultipleCountry($this->cart->getQuote()->getStoreId()),
                        'defaultCountry' => $this->ecsterHelper->getDefaultCountry($this->cart->getQuote()->getStoreId()),
                        'defaultShippingMethod' => $this->ecsterHelper->getDefaultShippingMethod(
                            $this->ecsterHelper->getDefaultCountry($this->cart->getQuote()->getStoreId()),
                            $this->cart->getQuote()->getStoreId()
                        ),
                        'singleShippingMethod' => $this->ecsterHelper->getSingleShippingMethod($this->cart->getQuote())
                    ]
                ]
            ];
        }

        return $config;
    }
}
