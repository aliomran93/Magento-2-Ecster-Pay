<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Evalent\EcsterPay\Model\Api\Ecster;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Psr\Log\LoggerInterface;

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

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Evalent\EcsterPay\Model\Checkout
     */
    private $ecsterCheckout;

    public function __construct(
        Order $order,
        EcsterPayHelper $helper,
        EcsterApi $ecsterApi,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        EventManagerInterface $eventManager,
        Checkout $ecsterCheckout
    ) {

        $this->_order = $order;
        $this->helper = $helper;
        $this->ecsterApi = $ecsterApi;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
        $this->ecsterCheckout = $ecsterCheckout;
    }

    public function process($responseJson, $forceCreateOrder = false)
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

            } else if ($forceCreateOrder && $response['event'] == "FULL_DEBIT" && $response['status'] == 'FULLY_DELIVERED'){
                //This fixes the issue with payment with Swish where the user is not redirected to the success page and thus we need to create the order through the OEN request
                $this->createOrderFromOen($response);
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

    protected function createOrderFromOen($oenData)
    {
        if (!isset($oenData['orderId'])) {
            return;
        }
        $response = (array)$this->ecsterApi->getOrder($oenData['orderId']);
        if ($response['id'] == $oenData['orderId']) {
            $quoteId = str_replace(Ecster::ECSTER_ORDER_PREFIX, "", $response['orderReference']);
            try {
                $quote = $this->quoteRepository->get($quoteId);
            } catch (NoSuchEntityException $e) {
                $this->logger->info("OEN Update: Could not find quote with id $quoteId");
                return;
            }

            $order = $this->ecsterCheckout->convertEcsterQuoteToOrder($response, $quote);
            $this->ecsterApi->updateOrderReference($response["id"], $order->getIncrementId());

            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);

            $this->eventManager->dispatch(
                'checkout_onepage_controller_success_action',
                [
                    'order_ids' => [$order->getId()],
                    'order' => $order->getId()
                ]
            );
        }
    }
}
