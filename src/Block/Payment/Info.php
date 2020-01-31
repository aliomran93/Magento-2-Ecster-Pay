<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Payment;

use Magento\Payment\Block\Info as PaymentInfo;
use Magento\Framework\View\Element\Template\Context;
use Evalent\EcsterPay\Model\InfoFactory;

class Info extends PaymentInfo
{
    protected $_ecsterInfoFactory;

    public function __construct(
        Context $context,
        InfoFactory $paypalInfoFactory,
        array $data = []
    ) {
        $this->_ecsterInfoFactory = $paypalInfoFactory;
        parent::__construct($context, $data);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();

        /** @var \Evalent\EcsterPay\Model\Info $ecsterPaymentInfo */
        $ecsterPaymentInfo = $this->_ecsterInfoFactory->create();
        $info = $ecsterPaymentInfo->getPaymentInfo($payment);

        return $transport->addData($info);
    }
}