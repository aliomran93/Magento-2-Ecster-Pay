<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Evalent\EcsterPay\Helper\Data as EcsterHelper;

class Info
{
    protected $_helper;
    
    public function __construct(
        EcsterHelper $helper
    ) {
        $this->_helper = $helper;
    }
    
    public function getPaymentInfo(\Magento\Payment\Model\InfoInterface $payment)
    {
        $result = [];
        $result[(string)__('Ecster Payment Type')] = $payment->getOrder()->getEcsterPaymentType();
        $result[(string)__('Ecster Transaction ID')] = $payment->getOrder()->getEcsterInternalReference();
        return $result;
    }
}