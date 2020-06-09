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

    /**
     * @inheritdoc
     */
    protected $_canAuthorize = false;

    /**
     * @inheritdoc
     */
    protected $_canUseCheckout = false;

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


}
