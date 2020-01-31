<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Sales\Model\Order\Creditmemo\Total;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class Discount extends \Magento\Sales\Model\Order\Creditmemo\Total\Discount
{
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        if ($creditmemo->getOrder()->getPayment()->getMethodInstance()->getCode() == 'ecsterpay') {
            $creditmemo->setDiscountAmount(0);
            $creditmemo->setBaseDiscountAmount(0);

            $order = $creditmemo->getOrder();

            $totalDiscountAmount = 0;
            $baseTotalDiscountAmount = 0;

            /**
             * Calculate how much shipping discount should be applied
             * basing on how much shipping should be refunded.
             */
            $baseShippingAmount = $this->getBaseShippingAmount($creditmemo);

            /**
             * If credit memo's shipping amount is set and Order's shipping amount is 0,
             * throw exception with different message
             */
            if ($baseShippingAmount && $order->getBaseShippingAmount() <= 0) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("You can not refund shipping if there is no shipping amount.")
                );
            }


            // Fix
            if ($order->getBaseShippingAmount() - $order->getBaseShippingRefunded() < 1) {
                $baseShippingAmount = 0;
            } else {
                if ($order->getBaseShippingRefunded() > 0
                    && $order->getBaseShippingDiscountTaxCompensationAmnt() > 0) {
                    $baseShippingAmount = $baseShippingAmount - ($order->getBaseShippingRefunded() + $order->getBaseShippingDiscountTaxCompensationAmnt());
                }
            }

            if ($baseShippingAmount) {
                $baseShippingDiscount = $baseShippingAmount *
                    $order->getBaseShippingDiscountAmount() /
                    $order->getBaseShippingAmount();
                $shippingDiscount = $order->getShippingAmount() * $baseShippingDiscount / $order->getBaseShippingAmount();
                $totalDiscountAmount = $totalDiscountAmount + $shippingDiscount;
                $baseTotalDiscountAmount = $baseTotalDiscountAmount + $baseShippingDiscount;
            }

            /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();

                if ($orderItem->isDummy()) {
                    continue;
                }

                $orderItemDiscount = (double)$orderItem->getDiscountInvoiced();
                $baseOrderItemDiscount = (double)$orderItem->getBaseDiscountInvoiced();
                $orderItemQty = $orderItem->getQtyInvoiced();

                if ($orderItemDiscount && $orderItemQty) {
                    $discount = $orderItemDiscount - $orderItem->getDiscountRefunded();
                    $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountRefunded();
                    if (!$item->isLast()) {
                        $availableQty = $orderItemQty - $orderItem->getQtyRefunded();
                        $discount = $creditmemo->roundPrice(
                            $discount / $availableQty * $item->getQty(),
                            'regular',
                            true
                        );
                        $baseDiscount = $creditmemo->roundPrice(
                            $baseDiscount / $availableQty * $item->getQty(),
                            'base',
                            true
                        );
                    }

                    $item->setDiscountAmount($discount);
                    $item->setBaseDiscountAmount($baseDiscount);

                    $totalDiscountAmount += $discount;
                    $baseTotalDiscountAmount += $baseDiscount;
                }
            }

            $creditmemo->setDiscountAmount(-$totalDiscountAmount);
            $creditmemo->setBaseDiscountAmount(-$baseTotalDiscountAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);

            return $this;


        } else {
            return parent::collect($creditmemo);
        }
    }

    private function getBaseShippingAmount(\Magento\Sales\Model\Order\Creditmemo $creditmemo): float
    {
        $baseShippingAmount = (float)$creditmemo->getBaseShippingAmount();

        if (!$baseShippingAmount) {
            $baseShippingInclTax = (float)$creditmemo->getBaseShippingInclTax();
            $baseShippingTaxAmount = (float)$creditmemo->getBaseShippingTaxAmount();
            $baseShippingAmount = $baseShippingInclTax - $baseShippingTaxAmount;
        }

        return $baseShippingAmount;
    }

}