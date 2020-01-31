<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Config\Source\Badge;

use Magento\Framework\Option\ArrayInterface;

class Format implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ["value" => "png", "label" => __("PNG")],
            ["value" => "svg", "label" => __("SVG")]
        ];
    }
}