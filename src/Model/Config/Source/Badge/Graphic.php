<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Config\Source\Badge;

use Magento\Framework\Option\ArrayInterface;

class Graphic implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ["value" => "ecster_pay2_long_badge", "label" => __("ecster_pay2_long_badge")],
            ["value" => "ecster_pay2_badge_2", "label" => __("ecster_pay2_badge_2")],
            ["value" => "ecster_pay2_long_badge_transparent", "label" => __("ecster_pay2_long_badge_transparent")],
            ["value" => "ecster_pay2_badge_2_transparent", "label" => __("ecster_pay2_badge_2_transparent")],
        ];
    }
}