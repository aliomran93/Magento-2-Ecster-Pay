<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order\Payment;

class Gateway extends AbstractMethod
{
    public $_code = 'ecsterpay';

    protected $_isGateway = false;

    /**
     * @inheritdoc
     */
    protected $_canAuthorize = false;

    /**
     * @inheritdoc
     */
    protected $_canUseCheckout = true;

    /**
     * @inheritdoc
     */
    protected $_canRefund = true;

    /**
     * @inheritdoc
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @inheritdoc
     */
    protected $_infoBlockType = \Evalent\EcsterPay\Block\Payment\Info::class;

    public function canRefund()
    {
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Payment) {
            if ($paymentInfo->getOrder()->getEcsterPaymentType() == "SWISH") {
                return false;
            }
        }
        return $this->_canRefund;
    }

}
