<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Sales\Order\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Magento\Sales\Model\Order\Creditmemo;

class ExtraFee extends AbstractTotal
{
    public function collect(Creditmemo $creditmemo)
    {
        $creditmemo->setEcsterExtraFee(0);
        $ecsterExtraFee = $creditmemo->getOrder()->getEcsterExtraFee() - $creditmemo->getOrder()->getEcsterExtraCreditmemoRemainFee();

        if ($ecsterExtraFee > 0) {
            $creditmemo->setEcsterExtraFee($ecsterExtraFee);
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $ecsterExtraFee);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $ecsterExtraFee);

        return $this;
    }
}