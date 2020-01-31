<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Frontend\Sales\Order\Totals;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;

class ExtraFee extends Template
{
    protected $_order;
    protected $_source;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock(); // todo: phpdoc
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->_order->getEcsterExtraFee() > 0) {
            $ecsterExtraFee = new \Magento\Framework\DataObject(
                [
                    'code' => 'ecsterpay_extra_fee',
                    'strong' => false,
                    'value' => $this->_order->getEcsterExtraFee(),
                    'label' => __('Extra Fee'),
                ]
            );

            $parent->addTotal($ecsterExtraFee, 'ecsterpay_extra_fee');
        }

        return $this;
    }
}