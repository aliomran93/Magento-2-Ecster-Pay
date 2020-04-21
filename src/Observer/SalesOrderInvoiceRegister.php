<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;

class SalesOrderInvoiceRegister implements ObserverInterface
{
    protected $_ecsterApi;
    protected $_helper;

    protected $_invoiceTotal;
    protected $_invoiceTotalControl;

    public function __construct(
        EcsterApi $ecsterApi,
        EcsterHelper $helper
    ) {
        $this->_ecsterApi = $ecsterApi;
        $this->_helper = $helper;
    }

    public function convertInvoiceItemsToEcster($invoice)
    {
        $invoiceItems = $invoice->getAllItems();

        $items = [];
        $this->_invoiceTotal = 0;
        $this->_invoiceTotalControl = 0;

        $discountApplyMethod = $this->_helper->getApplyDiscountMethod($invoice->getStoreId());

        /** @var \Magento\Sales\Model\Order\Invoice\Item $invoiceItem */
        foreach ($invoiceItems as $invoiceItem) {
            $item = [];
            $orderItem = $invoiceItem->getOrderItem();

            if ($orderItem->getProductType() != 'simple'
                && $orderItem->getHasChildren()
            ) {
                foreach ($this->_ecsterApi->ecsterInvoiceItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    }  else {
                        if ($field == 'description') {
                            $description = [];
                            foreach ($orderItem->getChildrenItems() as $childItem) {
                                $description[] = $childItem->getName();
                            }
                            $var = implode(",", $description);
                        } else {
                            if ($options['column'] == 'price') {
                                if ($discountApplyMethod) {
                                    $var = $invoiceItem->getData('price') + (($invoiceItem->getData('tax_amount') + $invoiceItem->getData('discount_tax_compensation_amount')) / $invoiceItem->getData('qty'));
                                } else {
                                    $var = $invoiceItem->getData('price_incl_tax');
                                }

                                $this->_invoiceTotal += $var * $invoiceItem->getData('qty');
                                $this->_invoiceTotalControl += $this->_helper->ecsterFormatPrice($var) * $invoiceItem->getData('qty');

//                        } else if($options['column'] == 'discount_amount') {
//
//                            if($orderItem->getProductType() == 'bundle'
//                                    && $orderItem->getHasChildren()) {
//
//                                $var = 0;
//                                foreach($orderItem->getChildrenItems() as $childItem) {
//                                    $var += $childItem->getData('discount_amount');
//                                }
//
//                                $this->_invoiceTotal -= $var;
//                                $this->_invoiceTotalControl -= $this->_helper->ecsterFormatPrice($var);
//
//                            } else {
//                                $var = $invoiceItem->getData('discount_amount');
//                                $this->_invoiceTotal -= $var;
//                                $this->_invoiceTotalControl -= $this->_helper->ecsterFormatPrice($var);
//                            }

                            } else {
                                $var = $invoiceItem->getData($options['column']);
                                if ($var == null) {
                                    $var = $orderItem->getData($options['column']);
                                }
                            }
                        }
                    }

                    if ($options['ecster_type'] == 'float') {
                        $var = $this->_helper->ecsterFormatPrice($var);
                    }

                    settype($var, $options['type']);
                    $item[$field] = $var;
                }
            } else {
                if (!is_null($orderItem->getParentItemId())) {
                    continue;
                }

                foreach ($this->_ecsterApi->ecsterInvoiceItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($options['column'] == 'price') {
                            if ($discountApplyMethod) {
                                $var = $invoiceItem->getData('price') + (($invoiceItem->getData('tax_amount') + $invoiceItem->getData('discount_tax_compensation_amount')) / $invoiceItem->getData('qty'));
                            } else {
                                $var = $invoiceItem->getData('price_incl_tax');
                            }

                            $this->_invoiceTotal += $var * $invoiceItem->getData('qty');
                            $this->_invoiceTotalControl += $this->_helper->ecsterFormatPrice($var) * $invoiceItem->getData('qty');

//                    } else if($options['column'] == 'discount_amount') {
//
//                            $var = $invoiceItem->getData('discount_amount');
//                            $this->_invoiceTotal -= $var;
//                            $this->_invoiceTotalControl -= $this->_helper->ecsterFormatPrice($var);
//
                        } else {
                            $var = $invoiceItem->getData($options['column']);
                            if ($var == null) {
                                $var = $orderItem->getData($options['column']);
                            }
                        }
                    }

                    if ($options['ecster_type'] == 'float') {
                        $var = $this->_helper->ecsterFormatPrice($var);
                    }

                    settype($var, $options['type']);
                    $item[$field] = $var;
                }
            }


            $items[] = $item;
        }

        if ($invoice->getDiscountAmount() < 0) {
            $this->_invoiceTotal += $invoice->getDiscountAmount();
            $this->_invoiceTotalControl += $this->_helper->ecsterFormatPrice($invoice->getDiscountAmount());
            $items[] = $this->_ecsterApi->createDummyItem($invoice->getDiscountAmount(), "Discount", "Discount");
        }

        $this->_invoiceTotal = (float)$this->_helper->ecsterFormatPrice($this->_invoiceTotal);

        if ($this->_invoiceTotal != $this->_invoiceTotalControl) {
            $diff = ($this->_invoiceTotal - $this->_invoiceTotalControl) / 100;
            $items[] = $this->_ecsterApi->createDummyItem($diff);
        }

        return $items;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        if ($this->_helper->isEnabled($order->getStoreId())
            && $method->getCode() == 'ecsterpay'
        ) {
            $items = $this->convertInvoiceItemsToEcster($invoice);

            $ecsterReferenceId = $order->getData('ecster_internal_reference');

            try {
                $order->setEcsterExtraInvoiceRemainFee($order->getEcsterExtraInvoiceRemainFee() + $invoice->getEcsterExtraFee())->save();

                if (!is_null($ecsterReferenceId)) {
                    if ($invoice->getShippingInclTax() > 0) {
                    $shippingItem = $this->_ecsterApi->createDummyItem($invoice->getShippingInclTax(),
                        $order->getShippingDescription(), $order->getShippingDescription());
                    $shippingTaxAmount = $invoice->getShippingTaxAmount() ?? $order->getShippingTaxAmount();
                    $shippingCost = $invoice->getShippingAmount() ?? $order->getShippingAmount();
                    if ($shippingCost > 0) {
                        $shippingItem["vatRate"] = $shippingTaxAmount / $shippingCost * 10000; // Times 100 to get percentage and times 100 to get it to ecster value i.e 25,00 => 2500
                    }
                        $items[] = $shippingItem;
                    }

                    if ($invoice->getOrder()->getEcsterPaymentType() == 'INVOICE' && $invoice->getEcsterExtraFee() > 0) {
                        $items[] = $this->_ecsterApi->createDummyItem($invoice->getEcsterExtraFee(), __('Invoice Fee'),
                            __('Invoice Fee'));
                    }

                    $requestParams = [
                        "type" => $this->_ecsterApi::ECSTER_OMA_TYPE_DEBIT,
                        "amount" => $this->_helper->ecsterFormatPrice($invoice->getGrandTotal()),
                        "transactionReference" => $order->getIncrementId(),
                        "rows" => $items,
                        "message" => !is_null($invoice->getCustomerNote()) ? $invoice->getCustomerNote() : "",
                        "closeDebit" => false,
                    ];

                    $responseParams = $this->_ecsterApi->orderProcess($ecsterReferenceId, $requestParams);
                    if ($responseParams
                        && $responseParams->transaction
                    ) {
                        $invoice->setData('ecster_debit_reference', $responseParams->transaction->id)->save();

                        $transactionHistoryData = [
                            'id' => null,
                            'order_id' => $order->getId(),
                            'entity_type' => 'invoice',
                            'entity_id' => $invoice->getId(),
                            'amount' => $invoice->getGrandTotal(),
                            'transaction_type' => $this->_ecsterApi::ECSTER_OMA_TYPE_DEBIT,
                            'request_params' => serialize($requestParams),
                            'order_status' => $responseParams->orderStatus,
                            'transaction_id' => $responseParams->transaction->id,
                            'response_params' => serialize((array)$responseParams),
                        ];

                        $this->_helper->addTransactionHistory($transactionHistoryData);
                    }
                }

                $invoice->setData('ecster_creditmemo_remain_fee', $invoice->getGrandTotal())
                    ->setData('ecster_creditmemo_status', 'new')
                    ->save();

            } catch (\Exception $ex) {
                throw new \Magento\Framework\Exception\LocalizedException(__($ex->getMessage()));
            }
        }

        return $this;
    }
}