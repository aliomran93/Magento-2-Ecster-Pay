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

class SalesOrderCancelAfter implements ObserverInterface
{
    protected $_ecsterApi;
    protected $_helper;

    public function __construct(
        EcsterApi $ecsterApi,
        EcsterHelper $helper
    ) {
        $this->_ecsterApi = $ecsterApi;
        $this->_helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        if ($this->_helper->isEnabled($order->getStoreId())
            && $method->getCode() == 'ecsterpay'
        ) {
            $ecsterReferenceId = $order->getData('ecster_internal_reference');

            if (!is_null($ecsterReferenceId)) {
                try {
                    $total = $order->getGrandTotal();
                    if ($order->getEcsterExtraFee() > 0) {
                        $total += $$order->getEcsterExtraFee();
                    }

                    $requestParams = [
                        "type" => $this->_ecsterApi::ECSTER_OMA_TYPE_ANNUL,
                        "amount" => $this->_helper->ecsterFormatPrice($total),
                        "transactionReference" => $order->getIncrementId(),
                        "closeDebit" => true
                    ];

                    $responseParams = $this->_ecsterApi->orderProcess($ecsterReferenceId, $requestParams);

                    if ($responseParams
                        && $responseParams->transaction
                    ) {
                        $transactionHistoryData = [
                            'id' => null,
                            'order_id' => $order->getId(),
                            'entity_type' => 'cancel_order',
                            'entity_id' => $order->getId(),
                            'amount' => $total,
                            'transaction_type' => $this->_ecsterApi::ECSTER_OMA_TYPE_ANNUL,
                            'order_status' => $responseParams->orderStatus,
                            'request_params' => serialize($requestParams),
                            'transaction_id' => $responseParams->transaction->id,
                            'response_params' => serialize((array)$responseParams),
                        ];

                        $this->_helper->addTransactionHistory($transactionHistoryData);
                    }
                } catch (\Exception $ex) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($ex->getMessage()));
                }
            }
        }

        return $this;
    }
}