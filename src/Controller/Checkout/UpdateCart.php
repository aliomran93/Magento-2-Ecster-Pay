<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Magento\Checkout\Controller\Index\Index as CheckoutIndex;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\LayoutFactory as ResultLayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class UpdateCart extends CheckoutIndex
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

    public function execute()
    {
        $returnArray = [];
        $returnArray['error'] = false;

        $ecsterCartKey = $this->_ecsterApi->updateCart($this->getQuote());
        if (!$ecsterCartKey) {
            $returnArray['message'] = __('Unable to update Ecster cart. Please contact the support');
            $returnArray['error'] = true;
        }
        $returnArray['ecster_key'] = $ecsterCartKey;
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($returnArray);
        return $resultJson;
    }
}
