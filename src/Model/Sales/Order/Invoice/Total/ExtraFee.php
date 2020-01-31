<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Sales\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use Magento\Sales\Model\Order\Invoice;

class ExtraFee extends AbstractTotal
{
    public function collect(Invoice $invoice)
    {
        $invoice->setEcsterExtraFee(0);
        $ecsterExtraFee = $invoice->getOrder()->getEcsterExtraFee() - $invoice->getOrder()->getEcsterExtraInvoiceRemainFee();

        if ($ecsterExtraFee > 0) {
            $invoice->setEcsterExtraFee($ecsterExtraFee);
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() + $ecsterExtraFee);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $ecsterExtraFee);

        return $this;
    }
}