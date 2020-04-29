<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Creditmemo\Totals\View;

use Magento\Framework\View\Element\Template\Context;
use Magento\Tax\Model\Config;

class ExtraFee extends \Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Creditmemo\Totals\ExtraFee
{

    /**
     * @var
     */
    protected $order;

    /**
     * @var
     */
    protected $source;

    public function __construct(
        Context $context,
        Config $taxConfig,
        array $data = []
    ) {
        parent::__construct($context, $taxConfig, $data);
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        $ecsterExtraFeeVal = $this->source->getEcsterExtraFee();

        if ($ecsterExtraFeeVal > 0) {

            $ecsterExtraFee = new \Magento\Framework\DataObject(
                [
                    'code' => 'ecsterpay_extra_fee',
                    'strong' => false,
                    'value' => $ecsterExtraFeeVal,
                    'label' => __('Extra Fee'),
                ]
            );

            $parent->addTotal($ecsterExtraFee, 'ecsterpay_extra_fee');
        }

        return $this;
    }
}
