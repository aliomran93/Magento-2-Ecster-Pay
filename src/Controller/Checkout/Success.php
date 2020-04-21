<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Evalent\EcsterPay\Model\Checkout;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;

class Success extends Action
{
    protected $resultPageFactory;
    protected $_checkout;
    protected $_helper;
    protected $_ecsterApi;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        Context $context,
        Checkout $checkout,
        EcsterPayHelper $helper,
        EcsterApi $ecsterApi,
        Session $checkoutSession
    ) {
        parent::__construct(
            $context
        );

        $this->_checkout = $checkout;
        $this->_helper = $helper;
        $this->_ecsterApi = $ecsterApi;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        if ($ecsterReference = $this->getRequest()->getParam('ecster-reference')) {
            try {
                $response = (array)$this->_ecsterApi->getOrder($ecsterReference);

                if ($response['id'] == $ecsterReference) {
                    $order = $this->_checkout->convertEcsterQuoteToOrder($response);
                    $this->_ecsterApi->updateOrderReference($response["id"], $order->getIncrementId());

                    return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
                } else {
                    throw new \Exception(__('The Internal Reference does not match the Order Reference.'));
                }

            } catch (\Exception $ex) {
                $this->messageManager->addError($ex->getMessage());

                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
            }
        }

        return $this->resultRedirectFactory->create()->setPath('/');
    }
}