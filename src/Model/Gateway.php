<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Gateway extends AbstractMethod
{
    public $_code = 'ecsterpay';

    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canUseCheckout = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    protected $_infoBlockType = \Evalent\EcsterPay\Block\Payment\Info::class;
}
