<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Sales\Model\Order;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;

class SalesOrderStatusUpdate
{
    protected $order;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    protected $helper;

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    protected $ecsterApi;

    public function __construct(
        Order $order,
        EcsterPayHelper $helper,
        EcsterApi $ecsterApi
    ) {

        $this->_order = $order;
        $this->helper = $helper;
        $this->ecsterApi = $ecsterApi;
    }

    public function process($responseJson)
    {

        $response = (array)json_decode($responseJson);

        if (isset($response['status'])) {
            $order = $this->_order->load($response['orderId'], 'ecster_internal_reference');

            if ($order && $order->getId()) {
                $message = null;
                $state = null;

                $assignedStatus = $this->helper->getOenStatus(
                    $response['status'],
                    $order->getStoreId()
                );

                if (!$assignedStatus) {
                    return;
                }

                if ($order->getStatus() == $assignedStatus) {
                    return;
                }

                $state = $this->helper->getOenStatus($response['status'], $order->getStoreId());

                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $assignedStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                }

                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    $assignedStatus = \Magento\Sales\Model\Order::STATE_COMPLETE;
                }

                $this->orderStatusUpdate($order, $assignedStatus, $message, $state);

                $transactionHistoryData = [
                    'id' => null,
                    'order_id' => $order->getId(),
                    'entity_type' => 'order',
                    'entity_id' => $order->getId(),
                    'amount' => $order->getGrandTotal(),
                    'transaction_type' => $this->ecsterApi::ECSTER_OMA_TYPE_OEN_UPDATE,
                    'request_params' => null,
                    'order_status' => $response['status'],
                    'transaction_id' => null,
                    'response_params' => serialize($response)
                ];

                $this->helper->addTransactionHistory($transactionHistoryData);

            } else {
                throw new \Exception(__(
                    "Ecster OEN: Could not find order by %1 ecster reference number.",
                    $response['orderId']
                ));
            }

        } else {
            throw new \Exception(__("Ecster OEN: Status Error"));
        }
    }

    public function orderStatusUpdate($order, $status, $message = null, $state = null)
    {
        try {
            if (is_null($state)) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
            }

            $order->setData('state', $state)
                ->setData('status', $status)
                ->addStatusToHistory($status, $message, false)
                ->save();

            return true;

        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
