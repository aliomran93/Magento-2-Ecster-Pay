<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\System\Config\Source;

class TransactionMode
{
    public function toOptionArray()
    {
        return [
            ['value' => \Evalent\EcsterPay\Model\Api\Ecster::MODE_TEST, 'label' => __('Test')],
            ['value' => \Evalent\EcsterPay\Model\Api\Ecster::MODE_LIVE, 'label' => __('Live')]
        ];
    }
}