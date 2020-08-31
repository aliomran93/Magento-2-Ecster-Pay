<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Observer;

use Evalent\EcsterPay\Helper\Data;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderInvoiceRegister implements ObserverInterface
{

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    protected $ecsterApi;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    protected $helper;

    /**
     * @var float
     */
    protected $invoiceTotal;

    /**
     * @var float
     */
    protected $invoiceTotalControl;

    public function __construct(
        EcsterApi $ecsterApi,
        EcsterHelper $helper
    ) {
        $this->ecsterApi = $ecsterApi;
        $this->helper = $helper;
    }

    public function convertInvoiceItemsToEcster($invoice)
    {
        $invoiceItems = $invoice->getAllItems();

        $items = [];
        $this->invoiceTotal = 0;
        $this->invoiceTotalControl = 0;

        $discountApplyMethod = $this->helper->getApplyDiscountMethod($invoice->getStoreId());

        /** @var \Magento\Sales\Model\Order\Invoice\Item $invoiceItem */
        foreach ($invoiceItems as $invoiceItem) {
            $item = [];
            $orderItem = $invoiceItem->getOrderItem();

            if ($orderItem->getProductType() != 'simple'
                && $orderItem->getHasChildren()
            ) {
                foreach ($this->ecsterApi->ecsterInvoiceItemFields as $field => $options) {
                    if ($field == 'description') {
                        $description = [];
                        foreach ($orderItem->getChildrenItems() as $childItem) {
                            $description[] = $childItem->getName();
                        }
                        $var = implode(",", $description);
                    } elseif ($field == 'vatRate' && $orderItem->getProductType() == 'bundle') {
                        $vatRate = 0;
                        foreach ($orderItem->getChildrenItems() as $childItem) {
                            $vatRate += $childItem->getData('tax_percent');
                        }
                        $var = $vatRate / sizeof($orderItem->getChildrenItems());
                    } else {
                        if ($options['column'] == 'price') {
                            if ($discountApplyMethod) {
                                if ($orderItem->getProductType() == 'bundle') {
                                    $taxAmount = 0;
                                    foreach ($orderItem->getChildrenItems() as $childItem) {
                                        $taxAmount += $childItem->getData('tax_amount');
                                    }
                                } else {
                                    $taxAmount = $invoiceItem->getData('tax_amount');
                                }
                                $var = $invoiceItem->getData('price') + (($taxAmount + $invoiceItem->getData('discount_tax_compensation_amount')) / $invoiceItem->getData('qty'));
                            } else {
                                $var = $invoiceItem->getData('price_incl_tax');
                            }

                            $this->invoiceTotal += $var * $invoiceItem->getData('qty');
                            $this->invoiceTotalControl += $this->helper->ecsterFormatPrice($var) * $invoiceItem->getData('qty');

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

                    if ($options['ecster_type'] == 'float') {
                        $var = $this->helper->ecsterFormatPrice($var);
                    }

                    settype($var, $options['type']);
                    $item[$field] = $var;
                }
            } else {
                if (!is_null($orderItem->getParentItemId())) {
                    continue;
                }

                foreach ($this->ecsterApi->ecsterInvoiceItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($options['column'] == 'price') {
                            if ($discountApplyMethod) {
                                $var = $invoiceItem->getData('price') + (($invoiceItem->getData('tax_amount') + $invoiceItem->getData('discount_tax_compensation_amount')) / $invoiceItem->getData('qty'));
                            } else {
                                $var = $invoiceItem->getData('price_incl_tax');
                            }
                            $this->invoiceTotal += $var * $invoiceItem->getData('qty');
                            $this->invoiceTotalControl += $this->helper->ecsterFormatPrice($var) * $invoiceItem->getData('qty');

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
                        $var = $this->helper->ecsterFormatPrice($var);
                    }

                    settype($var, $options['type']);
                    $item[$field] = $var;
                }
            }

            $items[] = $item;
        }

        if ($invoice->getDiscountAmount() < 0) {
            $this->invoiceTotal += $invoice->getDiscountAmount();
            $this->invoiceTotalControl += $this->helper->ecsterFormatPrice($invoice->getDiscountAmount());
            $items[] = $this->ecsterApi->createDummyItem($invoice->getDiscountAmount(), "Discount", "Discount");
        }

        $this->invoiceTotal = (float)$this->helper->ecsterFormatPrice($this->invoiceTotal);

        if ($this->invoiceTotal != $this->invoiceTotalControl) {
            $diff = ($this->invoiceTotal - $this->invoiceTotalControl) / 100;
            $items[] = $this->ecsterApi->createDummyItem($diff);
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

        if ($this->helper->isEnabled($order->getStoreId())
            && $method->getCode() == 'ecsterpay'
        ) {
            $items = $this->convertInvoiceItemsToEcster($invoice);

            $ecsterReferenceId = $order->getData('ecster_internal_reference');

            try {
                if (!is_null($ecsterReferenceId)) {
                    if ($invoice->getShippingInclTax() > 0) {
                        $shippingItem = $this->ecsterApi->createDummyItem(
                            $invoice->getShippingInclTax(),
                            $order->getShippingDescription(),
                            $order->getShippingDescription()
                        );
                        $shippingTaxAmount = $invoice->getShippingTaxAmount() ?? $order->getShippingTaxAmount();
                        $shippingCost = $invoice->getShippingAmount() ?? $order->getShippingAmount();
                        if ($shippingCost > 0) {
                            $shippingItem["vatRate"] = $shippingTaxAmount / $shippingCost * 10000; // Times 100 to get percentage and times 100 to get it to ecster value i.e 25,00 => 2500
                        }
                        $items[] = $shippingItem;
                    }

                    if ($invoice->getOrder()->getEcsterPaymentType() == 'INVOICE' && $invoice->getEcsterExtraFee() > 0) {
                        $items[] = $this->ecsterApi->createDummyItem(
                            $invoice->getEcsterExtraFee(),
                            __('Invoice Fee'),
                            __('Invoice Fee')
                        );
                    }

                    $requestParams = [
                        "type" => $this->ecsterApi::ECSTER_OMA_TYPE_DEBIT,
                        "amount" => $this->helper->ecsterFormatPrice($invoice->getGrandTotal()),
                        "transactionReference" => $order->getIncrementId(),
                        "rows" => $items,
                        "message" => !is_null($invoice->getCustomerNote()) ? $invoice->getCustomerNote() : "",
                        "closeDebit" => false,
                    ];

                    // Some method does not support synchronous transaction flow
                    if (!in_array($invoice->getOrder()->getEcsterPaymentType(), Data::NON_SYNC_TRANSACTION_METHODS)) {
                        $responseParams = $this->ecsterApi->orderProcess($ecsterReferenceId, $requestParams);
                        if ($responseParams
                            && $responseParams->transaction
                        ) {
                            $invoice->setData('ecster_debit_reference', $responseParams->transaction->id);
                            $invoice->setData('transaction_id', $responseParams->transaction->id)->save();

                            $transactionHistoryData = [
                                'id' => null,
                                'order_id' => $order->getId(),
                                'entity_type' => 'invoice',
                                'entity_id' => $invoice->getId(),
                                'amount' => $invoice->getGrandTotal(),
                                'transaction_type' => $this->ecsterApi::ECSTER_OMA_TYPE_DEBIT,
                                'request_params' => serialize($requestParams),
                                'order_status' => $responseParams->orderStatus,
                                'transaction_id' => $responseParams->transaction->id,
                                'response_params' => serialize((array)$responseParams),
                            ];

                            $this->helper->addTransactionHistory($transactionHistoryData);
                        }
                    }
                }
                $order->setEcsterExtraInvoiceRemainFee($order->getEcsterExtraInvoiceRemainFee() + $invoice->getEcsterExtraFee())->save();

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
