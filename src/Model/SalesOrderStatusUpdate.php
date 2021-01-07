<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\Api\Ecster;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Model\ResourceModel\TransactionHistory\CollectionFactory;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class SalesOrderStatusUpdate
{

    const LOGGER_PREFIX = "Ecster OEN: ";

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

    /**
     * @var \Magento\Framework\App\AreaList
     */
    private $areaList;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Evalent\EcsterPay\Model\ResourceModel\TransactionHistory\CollectionFactory
     */
    private $transactionsHistoryCollection;

    public function __construct(
        Order $order,
        EcsterPayHelper $helper,
        EcsterApi $ecsterApi,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        EventManagerInterface $eventManager,
        Checkout $ecsterCheckout,
        AreaList $areaList,
        State $state,
        CollectionFactory $transactionsHistoryCollection
    ) {
        $this->_order = $order;
        $this->helper = $helper;
        $this->ecsterApi = $ecsterApi;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
        $this->ecsterCheckout = $ecsterCheckout;
        $this->areaList = $areaList;
        $this->appState = $state;
        $this->transactionsHistoryCollection = $transactionsHistoryCollection;
    }

    /**
     * @param      $responseJson
     * @param bool $secondTry
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function process($responseJson, $secondTry = false)
    {
        $response = (array)json_decode($responseJson);
        if (isset($response['status'])) {
            $order = $this->_order->load($response['orderId'], 'ecster_internal_reference');

            if ($order && $order->getId()) {
                $message = null;
                $state = null;

                // We don't want to apply an OEN twice even if they shouldn't be sent twice
                // This is also due to the 10 second sleep function as Ecster expect a 200 response in 5 seconds. This helps to ignore the second call due to this
                if ($this->checkIfOenAlreadyApplied($order->getId(), $response)) {
                    $this->logger->info(__(self::LOGGER_PREFIX . "The OEN update have already been processed"));
                    return;
                }

                try {
                    $ecsterOrder = $this->ecsterApi->getOrder($response['orderId']);
                    if ($ecsterOrder->status != $response['status']) {
                        $this->logger->info(__(self::LOGGER_PREFIX . "The OEN status does not match the one fetched from Ecster. Ignore Call"));
                        return;
                    }
                } catch (Exception $e) {
                }


                $assignedStatus = $this->helper->getOenStatus(
                    $response['status'],
                    $order->getStoreId()
                );

                if (!$assignedStatus) {
                    $this->logger->info(self::LOGGER_PREFIX . "Couldn't get assigned status.");
                    return;
                }

                if ($order->getStatus() == $assignedStatus) {
                    $this->logger->info(self::LOGGER_PREFIX . "Status is the same as order. Ignored");
                    return;
                }

                $state = $this->helper->getOenStatus($response['status'], $order->getStoreId());

                if ($order->getStatus() == Order::STATE_CANCELED) {
                    $assignedStatus = Order::STATE_CANCELED;
                }

                if ($order->getStatus() == Order::STATE_COMPLETE) {
                    $assignedStatus = Order::STATE_COMPLETE;
                }

                $this->orderStatusUpdate($order, $assignedStatus, $message, $state);

                $transactionHistoryData = [
                    'id' => null,
                    'order_id' => $order->getId(),
                    'entity_type' => 'order',
                    'entity_id' => $order->getId(),
                    'amount' => $order->getGrandTotal(),
                    'transaction_type' => EcsterApi::ECSTER_OMA_TYPE_OEN_UPDATE,
                    'request_params' => null,
                    'order_status' => $response['status'],
                    'transaction_id' => null,
                    'response_params' => serialize($response),
                ];

                $this->helper->addTransactionHistory($transactionHistoryData);
            } elseif ($secondTry && $response['event'] == "FULL_DEBIT" && $response['status'] == 'FULLY_DELIVERED') {
                //This fixes the issue with payment with Swish where the user is not redirected to the success page
                // and thus we need to create the order through the OEN request
                $this->logger->info(self::LOGGER_PREFIX . "Creating order for response: " . $responseJson);
                $this->createOrderFromOen($response);
            } else {
                throw new LocalizedException(__(
                    self::LOGGER_PREFIX . "Could not find order by %1 Ecster reference number.",
                    $response['orderId']
                ));
            }
        } else {
            throw new Exception(__(self::LOGGER_PREFIX . "Status Error"));
        }
    }

    public function orderStatusUpdate($order, $status, $message = null, $state = null)
    {
        try {
            if (is_null($state)) {
                $state = Order::STATE_PROCESSING;
            }
            $this->logger->info(self::LOGGER_PREFIX . sprintf("Updating Order %s with state %s and status %s", $order->getId(), $status, $state));

            $order->setData('state', $state)
                ->setData('status', $status)
                ->addStatusToHistory($status, $message, false)
                ->save();

            return true;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    protected function createOrderFromOen($oenData)
    {
        if (!isset($oenData['orderId'])) {
            return;
        }
        try {
            $area = $this->areaList->getArea($this->appState->getAreaCode());
            $area->load(Area::PART_TRANSLATE);
        } catch (LocalizedException $exception) {
            $this->logger->error("Could not load area code. Locale translation might not be loaded");
        }
        $response = (array)$this->ecsterApi->getOrder($oenData['orderId']);
        if ($response['id'] == $oenData['orderId']) {
            $quoteId = str_replace(Ecster::ECSTER_ORDER_PREFIX, "", $response['orderReference']);
            try {
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $this->quoteRepository->get($quoteId);
            } catch (NoSuchEntityException $e) {
                $this->logger->info(self::LOGGER_PREFIX . "Create Order: Could not find quote with id $quoteId");
                return;
            }

            if (!$quote->getIsActive()) {
                $this->logger->info(self::LOGGER_PREFIX . "Create Order: Could not create order from quote $quoteId cause it was not active");
                return;
            }

            //Validate that the order really is payed
            if ($response['status'] != 'FULLY_DELIVERED') {
                $this->logger->info("Ecster OEN Create Order: Could not create order from quote $quoteId cause it didn't have the status FULLY_DELIVERED");
                return;
            }

            // Check so the totals still match, so the quote has not been tempered with
            if (number_format(((float)$response['amount'])/100, 2, '.', '') != number_format($quote->getGrandTotal(), 2, '.', '')) {
                $this->logger->info("Ecster OEN Create Order: Could not create order from quote $quoteId cause the totals differed.");
                return;
            }

            $order = $this->ecsterCheckout->convertEcsterQuoteToOrder($response, $quote);
            $this->ecsterApi->updateOrderReference($response["id"], $order->getIncrementId());

            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);

            //We still want to try and lets event observers to have their go at the order although they will not be able to fetch the checkout session
            $this->eventManager->dispatch(
                'checkout_onepage_controller_success_action',
                [
                    'order_ids' => [$order->getId()],
                    'order' => $order->getId(),
                ]
            );
        }
    }

    protected function checkIfOenAlreadyApplied($orderId, $oenResponse)
    {
        $previousOenUpdates = $this->transactionsHistoryCollection->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('order_id', $orderId);
        /** @var \Evalent\EcsterPay\Model\TransactionHistory $previousOenUpdate */
        foreach ($previousOenUpdates as $previousOenUpdate) {
            $previousResponse = @unserialize($previousOenUpdate->getData('response_params'));
            if ($previousResponse) {
                // We see if the request was already processed by checking if the status and timestamp are the same
                if (isset($previousResponse['time']) && isset($oenResponse['time'])
                    && isset($previousResponse['status']) && isset($oenResponse['status'])
                    && $previousResponse['time'] == $oenResponse['time']
                    && $previousResponse['status'] == $oenResponse['status']
                ) {
                    return true;
                }
            }
        }
        return false;
    }
}
