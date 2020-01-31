<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Sales\Order\Pdf\Total;

use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;

class ExtraFee extends DefaultTotal
{
    public function getTotalsForDisplay()
    {
        $ecsterpayExtraFee = $this->getOrder()->formatPriceTxt($this->getAmount());

        if ($this->getAmountPrefix()) {
            $ecsterpayExtraFee = $this->getAmountPrefix() . $ecsterpayExtraFee;
        }

        $title = __($this->getTitle());

        if ($this->getTitleSourceField()) {
            $label = $title . ' (' . $this->getTitleDescription() . '):';
        } else {
            $label = $title . ':';
        }

        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $total = ['amount' => $ecsterpayExtraFee, 'label' => $label, 'font_size' => $fontSize];

        return [$total];
    }
}