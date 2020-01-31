<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Checkout\Controller\Index\Index as CheckoutIndex;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\LayoutFactory as ResultLayoutFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\ForwardFactory;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;

class Index extends CheckoutIndex
{
    protected $resultForwardFactory;
    protected $_checkoutSession;
    protected $_helper;
    protected $_ecsterApi;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        CoreRegistry $coreRegistry,
        InlineInterface $translateInline,
        FormKeyValidator $formKeyValidator,
        ScopeConfigInterface $scopeConfig,
        LayoutFactory $layoutFactory,
        CartRepositoryInterface $quoteRepository,
        PageFactory $resultPageFactory,
        ResultLayoutFactory $resultLayoutFactory,
        RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        ForwardFactory $resultForwardFactory,
        EcsterPayHelper $helper,
        EcsterApi $ecsterApi
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );

        $this->_helper = $helper;
        $this->_ecsterApi = $ecsterApi;
        $this->_checkoutSession = $checkoutSession;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    protected function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    protected function getStoreId()
    {
        return $this->getQuote()->getStore()->getStoreId();
    }

    protected function getAddress()
    {
        if ($this->getQuote()->getIsVirtual()) {
            return $this->getQuote()->getBillingAddress();
        }

        return $this->getQuote()->getShippingAddress();
    }

    protected function isShippingMethods()
    {
        if (!$this->getQuote()->getIsVirtual()
            && (is_null($this->getAddress()->getCountryId())
                || (!is_null($this->getAddress()->getCountryId()) && $this->getAddress()->getCountryId() == $this->_helper->getDefaultCountry($this->getStoreId())))
            && is_null($this->getQuote()->getData('ecster_cart_key'))) {

            $this->getAddress()->setCollectShippingRates(true);
            $this->getAddress()->collectShippingRates();

            if (count($this->getAddress()->getAllShippingRates()) == 0) {
                return false;
            }

            return true;
        }

        return true;
    }

    public function execute()
    {
        $resultPage = parent::execute();
        if ($resultPage instanceof Redirect) {
            return $resultPage;
        }

        if ($this->_helper->isEnabled($this->getQuote()->getStore()->getStoreId())) {
            try {
                $storeId = $this->getStoreId();

                if (is_null($this->_helper->getTermsPageContent($storeId))) {
                    throw new \Exception($this->_helper->getNotDefinedTermsPageContentNotification());
                }

                if ((count($this->_helper->getAllowedCountries($storeId)) > 0
                    && !in_array(
                        $this->_helper->getDefaultCountry($storeId),
                        $this->_helper->getAllowedCountries($storeId)
                    ))) {
                    throw new \Exception(__(
                        "Ecster Checkout payment method does not support this country, %1.",
                        $this->_helper->getCountryName($this->_helper->getDefaultCountry($storeId))
                    ));
                }

                if ((!is_null($this->getAddress()->getCountryId())
                    && !is_null($this->getAddress()->getCustomerId())
                    && count($this->_helper->getAllowedCountries($storeId)) > 0
                    && !in_array($this->getAddress()->getCountryId(), $this->_helper->getAllowedCountries($storeId)))) {
                    throw new \Exception(__("Ecster Checkout payment method does not support this country, %1. Please change your default %2 country.",
                        $this->_helper->getCountryName($this->getAddress()->getCountryId()),
                        ($this->getQuote()->getIsVirtual() ? 'billing' : 'shipping')));
                }

                if (is_null($this->getAddress()->getCountryId())) {
                    if (!$this->getQuote()->getIsVirtual()) {
                        $this->getQuote()->getShippingAddress()->setCountryId($this->_helper->getDefaultCountry($this->getQuote()->getStore()->getStoreId()))->save();
                    }
                    $this->getQuote()->getBillingAddress()->setCountryId($this->_helper->getDefaultCountry($this->getQuote()->getStore()->getStoreId()))->save();
                }

                if (!$this->isShippingMethods()) {
                    throw new \Exception(__(
                        "We could not find a valid delivery method for %1. Please contact the site administration.",
                        $this->_helper->getCountryName(!is_null($this->getAddress()->getCountryId()) ? $this->getAddress()->getCountryId() : $this->_helper->getDefaultCountry($storeId))
                    ));
                }

//                if(is_null($this->getQuote()->getShippingMethod())
//                        && count($this->getAddress()->getAllShippingRates()) == 1) {
//                    $rates = $this->getAddress()->getAllShippingRates();
//                    $this->getAddress()->setShippingMethod($rates[0]->getCarrier() . "_" . $rates[0]->getMethod())
//                            ->setCollectShippingRates(true)
//                            ->collectShippingRates()
//                            ->save();
//                }

                if (is_null($this->getQuote()->getData('ecster_cart_key'))) {
                    if ($ecsterCartKey = $this->_ecsterApi->initCart($this->getQuote())) {
                        $this->getQuote()->setData('ecster_cart_key', $ecsterCartKey)->save();
                    }
                } else {
                    if ($ecsterCartKey = $this->_ecsterApi->updateCart($this->getQuote())) {
                        $this->getQuote()->setData('ecster_cart_key', $ecsterCartKey)->save();
                    }
                }

                $resultPage = $this->resultPageFactory->create();
                $resultPage->addHandle('ecsterpay_checkout_index');
                $resultPage->getConfig()->getTitle()->set(__('Ecster Checkout'));

                return $resultPage;

            } catch (\Exception $ex) {
                $this->messageManager->addError($ex->getMessage());

                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }
        }

        return $resultPage;
    }
}