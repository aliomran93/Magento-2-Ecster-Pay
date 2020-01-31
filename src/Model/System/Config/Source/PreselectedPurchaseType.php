<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\System\Config\Source;

class PreselectedPurchaseType
{
    public function toOptionArray()
    {
        return [
            ['value' => \Evalent\EcsterPay\Model\Api\Ecster::PURCHASE_TYPE_B2C, 'label' => __('B2C')],
            ['value' => \Evalent\EcsterPay\Model\Api\Ecster::PURCHASE_TYPE_B2B, 'label' => __('B2B')]
        ];
    }
}