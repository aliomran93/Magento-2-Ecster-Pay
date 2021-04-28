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
use Magento\Framework\DataObject;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\LayoutFactory as ResultLayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\CustomerManagement;

class ValidateQuote extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    protected $ecsterHelper;

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    protected $ecsterApi;

    /**
     * @var \Magento\Quote\Model\SubmitQuoteValidator
     */
    protected $submitQuoteValidator;

    /**
     * @var \Magento\Quote\Model\CustomerManagement
     */
    protected $customerManagement;

    public function __construct(
        CustomerManagement $customerManagement,
        \Magento\Quote\Model\SubmitQuoteValidator $submitQuoteValidator,
        CheckoutSession $checkoutSession,
        EcsterPayHelper $ecsterHelper,
        EcsterApi $ecsterApi,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->ecsterApi = $ecsterApi;
        $this->ecsterHelper = $ecsterHelper;
        $this->checkoutSession = $checkoutSession;
        $this->submitQuoteValidator = $submitQuoteValidator;
        $this->customerManagement = $customerManagement;
    }

    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    public function execute()
    {
        $returnArray = [];
        $returnArray['error'] = false;

        try {
            $quote = $this->getQuote();
            $_payment = $quote->getPayment();
            $_payment->unsMethodInstance()->setMethod("ecsterpay");

            $_paymentData = new DataObject([
                'payment' => "ecsterpay"
            ]);

            $quote->getPayment()->getMethodInstance()->assignData($_paymentData);
            $this->submitQuoteValidator->validateQuote($quote);
            if (!$quote->getCustomerIsGuest()) {
                if ($quote->getCustomerId()) {
                    $this->customerManagement->validateAddresses($quote);
                }
            }
        } catch (\Exception $e) {
            $returnArray['message'] = __('Unable to create order. %1', $e->getMessage());
            $returnArray['error'] = true;
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($returnArray);

        return $resultJson;
    }
}
