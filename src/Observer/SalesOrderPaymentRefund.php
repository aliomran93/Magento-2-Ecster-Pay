<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\App\RequestInterface;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;

class SalesOrderPaymentRefund implements ObserverInterface
{
    protected $_invoice;
    protected $_request;
    protected $_ecsterApi;
    protected $_helper;

    protected $_creditmemoTotal;
    protected $_creditmemoTotalControl;

    public function __construct(
        Invoice $invoice,
        RequestInterface $request,
        EcsterApi $ecsterApi,
        EcsterHelper $helper
    ) {
        $this->_invoice = $invoice;
        $this->_request = $request;
        $this->_ecsterApi = $ecsterApi;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     *
     * @return array
     */
    public function convertCreditMemoItemsToEcster($creditmemo)
    {
        $creditmemoItems = $creditmemo->getAllItems();

        $items = [];
        $this->_creditmemoTotal = 0;
        $this->_creditmemoTotalControl = 0;

        $discountApplyMethod = $this->_helper->getApplyDiscountMethod($creditmemo->getStoreId());

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */
        foreach ($creditmemoItems as $creditmemoItem) {
            $item = [];
            $orderItem = $creditmemoItem->getOrderItem();

            if ($orderItem->getProductType() != 'simple'
                && $orderItem->getHasChildren()
            ) {
                foreach ($this->_ecsterApi->ecsterCreditmemoItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($field == 'description') {
                            $description = [];
                            foreach ($orderItem->getChildrenItems() as $childItem) {
                                $description[] = $childItem->getName();
                            }
                            $var = implode(",", $description);
                        } else {
                            if ($options['column'] == 'price') {
                                if ($discountApplyMethod) {
                                    $var = $creditmemoItem->getData('price')
                                        + (
                                            ($creditmemoItem->getData('tax_amount') + $creditmemoItem->getData('discount_tax_compensation_amount'))
                                            / $creditmemoItem->getData('qty')
                                        );
                                } else {
                                    $var = $creditmemoItem->getData('price_incl_tax');
                                }

                                $this->_creditmemoTotal += $var * $creditmemoItem->getData('qty');
                                $this->_creditmemoTotalControl += $this->_helper->ecsterFormatPrice($var) * $creditmemoItem->getData('qty');

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
//                                $this->_creditmemoTotal -= $var;
//                                $this->_creditmemoTotalControl -= $this->_helper->ecsterFormatPrice($var);
//
//                            } else {
//                                $var = $creditmemoItem->getData('discount_amount');
//                                $this->_creditmemoTotal -= $var;
//                                $this->_creditmemoTotalControl -= $this->_helper->ecsterFormatPrice($var);
//                            }
//
                            } else {
                                $var = $creditmemoItem->getData($options['column']);
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

                foreach ($this->_ecsterApi->ecsterCreditmemoItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($options['column'] == 'price') {
                            if ($discountApplyMethod) {
                                $var = $creditmemoItem->getData('price')
                                    + (
                                        ($creditmemoItem->getData('tax_amount') + $creditmemoItem->getData('discount_tax_compensation_amount'))
                                        / $creditmemoItem->getData('qty')
                                    );
                            } else {
                                $var = $creditmemoItem->getData('price_incl_tax');
                            }

                            $this->_creditmemoTotal += $var * $creditmemoItem->getData('qty');
                            $this->_creditmemoTotalControl +=
                                $this->_helper->ecsterFormatPrice($var) * $creditmemoItem->getData('qty');

//                    } else if($options['column'] == 'discount_amount') {
//
//                            $var = $creditmemoItem->getData('discount_amount');
//                            $this->_creditmemoTotal -= $var;
//                            $this->_creditmemoTotalControl -= $this->_helper->ecsterFormatPrice($var);
                        } else {
                            $var = $creditmemoItem->getData($options['column']);
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

        if ($creditmemo->getDiscountAmount() < 0) {
            $this->_creditmemoTotal += $creditmemo->getDiscountAmount();
            $this->_creditmemoTotalControl += $this->_helper->ecsterFormatPrice($creditmemo->getDiscountAmount());
            $items[] = $this->_ecsterApi->createDummyItem($creditmemo->getDiscountAmount(), __("Discount"), "Discount");
        }

        $this->_creditmemoTotal = (float)$this->_helper->ecsterFormatPrice($this->_creditmemoTotal);

        if ($this->_creditmemoTotal != $this->_creditmemoTotalControl) {
            $diff = ($this->_creditmemoTotal - $this->_creditmemoTotalControl) / 100;
            $items[] = $this->_ecsterApi->createDummyItem($diff);
        }

        return $items;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getData('creditmemo');
        if (!$creditmemo->getDoTransaction()) {
            return;
        }
        $order = $creditmemo->getOrder();
        $payment = $observer->getEvent()->getData('payment');

        try {
            if ($invoiceId = $this->_request->getParam('invoice_id')) {
                $invoiceModel = $this->_invoice->load($invoiceId);

                if ($invoiceModel
                    && $invoiceModel->getId()) {
                    $items = $this->convertCreditMemoItemsToEcster($creditmemo);

                    if ($ecsterDebitReference = $invoiceModel->getData('ecster_debit_reference')) {
                        $ecsterReferenceId = $order->getData('ecster_internal_reference');

                        if ($creditmemo->getShippingInclTax() > 0) {
                            $items[] = $this->_ecsterApi->createDummyItem(
                                $creditmemo->getShippingInclTax(),
                                $order->getShippingDescription(),
                                $order->getShippingDescription()
                            );
                        }

                        $requestParams = [
                            "type" => $this->_ecsterApi::ECSTER_OMA_TYPE_CREDIT,
                            "amount" => $this->_helper->ecsterFormatPrice($creditmemo->getGrandTotal()),
                            "transactionReference" => $order->getIncrementId(),
                            "rows" => $items,
                            "debitTransaction" => $ecsterDebitReference,
                            "closeDebit" => true,
                        ];

                        $responseParams = $this->_ecsterApi->orderProcess($ecsterReferenceId, $requestParams);
                        if ($responseParams
                            && $responseParams->transaction
                        ) {
                            $transactionHistoryData = [
                                'id' => null,
                                'order_id' => $order->getId(),
                                'entity_type' => 'creditmemo',
                                'entity_id' => $creditmemo->getId(),
                                'amount' => $creditmemo->getGrandTotal(),
                                'transaction_type' => $this->_ecsterApi::ECSTER_OMA_TYPE_CREDIT,
                                'request_params' => serialize($requestParams),
                                'order_status' => $responseParams->orderStatus,
                                'transaction_id' => $responseParams->transaction->id,
                                'response_params' => serialize((array)$responseParams),
                            ];

                            $this->_helper->addTransactionHistory($transactionHistoryData);
                        }

                    }

                    $remainFee = (float)($invoiceModel->getEcsterCreditmemoRemainFee() - $creditmemo->getGrandTotal());

                    if ($remainFee > 0) {
                        $invoiceModel->setData('ecster_creditmemo_remain_fee', $remainFee)
                            ->setData('ecster_creditmemo_status', 'partial')
                            ->save();
                    } else {
                        $invoiceModel->setData('ecster_creditmemo_remain_fee', 0)
                            ->setData('ecster_creditmemo_status', 'fully')
                            ->save();
                    }

                    $creditmemo->setData('invoice_id', $invoiceId)->save();
                }
            }

            $order->setEcsterExtraCreditmemoRemainFee(
                $order->getEcsterExtraCreditmemoRemainFee() + $creditmemo->getEcsterExtraFee()
            )->save();

        } catch (\Exception $ex) {
            throw new \Magento\Framework\Exception\LocalizedException(__($ex->getMessage()));
        }

        return $this;
    }
}
