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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
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
    protected $logger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Evalent\EcsterPay\Model\Checkout
     */
    protected $ecsterCheckout;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \Evalent\EcsterPay\Model\ResourceModel\TransactionHistory\CollectionFactory
     */
    protected $transactionsHistoryCollection;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $transaction;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        Order $order,
        OrderStatusHistoryRepositoryInterface $historyRepository,
        EcsterPayHelper $helper,
        Transaction $transaction,
        Registry $registry,
        EcsterApi $ecsterApi,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        EventManagerInterface $eventManager,
        Checkout $ecsterCheckout,
        AreaList $areaList,
        State $state,
        CollectionFactory $transactionsHistoryCollection,
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        SearchCriteriaBuilder $searchCriteriaBuilder = null
    ) {
        $this->_order = $order;
        $this->helper = $helper;
        $this->ecsterApi = $ecsterApi;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->ecsterCheckout = $ecsterCheckout;
        $this->areaList = $areaList;
        $this->appState = $state;
        $this->transactionsHistoryCollection = $transactionsHistoryCollection;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->registry = $registry;
        $this->transaction = $transaction;
        $this->historyRepository = $historyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?? ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
    }

    /**
     * @param array $response
     * @param bool  $secondTry
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function process($response, $secondTry = false)
    {
        if (isset($response['status'])) {
            // We don't want to apply an OEN twice even if they shouldn't be sent twice
            // This is also due to the 10 second sleep function as Ecster expect a 200 response in 5 seconds. This helps to ignore the second call due to this
            if ($this->checkIfOenAlreadyApplied($response)) {
                $this->logger->info(__(self::LOGGER_PREFIX . "The OEN update have already been processed"));
                return;
            }

            $order = $this->loadOrderFromResponse($response);

            if ($order && $order->getId()) {
                $message = null;
                $state = null;

                try {
                    $ecsterOrder = $this->ecsterApi->getOrder($response['orderId']);
                } catch (Exception $e) {
                    throw new LocalizedException(__(self::LOGGER_PREFIX . "Couldn't get order from Ecster %1", $e->getMessage() ), $e);
                }

                if ($ecsterOrder->id != $order->getData('ecster_internal_reference')) {
                    $this->logger->info(__(self::LOGGER_PREFIX . "The OEN orderId does not match magento order ecster_internal_reference. Ignore Call"));
                    throw new LocalizedException(self::LOGGER_PREFIX . "The OEN orderId does not match magento order ecster_internal_reference. Ignore Call");
                }
                if ($ecsterOrder->status != $response['status']) {
                    throw new LocalizedException(__(self::LOGGER_PREFIX . "The OEN status does not match the one fetched from Ecster. Ignore Call"));
                }


                // If the order is fully delivered in ecster we want to create invoice
                if (!$order->hasInvoices() && $response['status'] == "FULLY_DELIVERED" && isset($response['event']) && $response['event'] == "FULL_DEBIT") {
                    try {
                        $this->createInvoiceFromOen($order, $response, $ecsterOrder);
                    } catch (LocalizedException $e) {
                        $this->logger->error(self::LOGGER_PREFIX . __("Unable to create invoice. %1", $e->getMessage()));
                    }
                }

                $assignedStatus = $this->helper->getOenStatus(
                    $response['status'],
                    $order->getStoreId()
                );


                if (!$assignedStatus) {
                    throw new LocalizedException(__(self::LOGGER_PREFIX . "Couldn't get assigned status."));
                }

                if ($order->getStatus() == $assignedStatus) {
                    throw new LocalizedException(__(self::LOGGER_PREFIX . "Status is the same as order. Ignored"));
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
                    'timetamp' => $response['time']
                ];

                $this->helper->addTransactionHistory($transactionHistoryData);
            } elseif ($secondTry && isset($response['event']) && $response['event'] == "FULL_DEBIT" && $response['status'] == 'FULLY_DELIVERED') {
                //This fixes the issue with payment with Swish where the user is not redirected to the success page
                // and thus we need to create the order through the OEN request
                $this->logger->info(self::LOGGER_PREFIX . "Creating order for response");
                $this->createOrderFromOen($response);
            } else {
                throw new NoSuchEntityException(__(
                    self::LOGGER_PREFIX . "Could not find order by %1 Ecster reference number.",
                    $response['orderId']
                ));
            }
        } else {
            throw new Exception(__(self::LOGGER_PREFIX . "Status Error"));
        }
    }

    protected function loadOrderFromResponse($responseData)
    {
        // First we try with ecster_internal_reference
        $order = $this->_order->load($responseData['orderId'], 'ecster_internal_reference');
        if ($order && $order->getId()) {
            return $order;
        }
        // Then we try if the order is already created. This occurs on SWISH payments
        $order = $this->_order->loadByIncrementId($responseData['orderReference']);
        // If we find the order on orderReference we need to update the order according to the ecster data
        if ($order
            && $order->getId()
            && !$order->getData('ecster_internal_reference')
            && $order->getData('ecster_payment_type') == "SWISH"
            && $responseData['status'] == "FULLY_DELIVERED"
        ) {
            $order = $this->updateOrderFromEcster($order, $responseData['orderId']);
        }

        return $order;

    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $ecsterOrderId
     */
    private function updateOrderFromEcster($order, $ecsterOrderId)
    {
        $ecsterOrder = (array)$this->ecsterApi->getOrder($ecsterOrderId);

        $order->setEcsterInternalReference($ecsterOrderId);

        if (!is_null($ecsterOrder['properties'])) {
            $orderProperties = (array)$ecsterOrder['properties'];

            $order->setEcsterProperties(serialize($orderProperties));
            $order->setEcsterPaymentType($orderProperties['method']);

            $extraFee = 0;
            switch ($orderProperties['method']) {
                case "CARD":
                    break;
                case "INVOICE":
                    $extraFee = $orderProperties['invoiceFee'] / 100;
                    $order->setEcsterExtraFee($extraFee);
                    break;
            }
        }
        return $this->orderRepository->save($order);
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

    /**
     * @var \Magento\Sales\Model\Order $order
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createInvoiceFromOen($order, $responseData, $ecsterOrder)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->invoiceService->prepareInvoice($order);
        if (isset($responseData['transactionId'])) {
            $invoice->setTransactionId($responseData['transactionId']);
            $invoice->setEcsterDebitReference($responseData['transactionId']);
        }
        $invoice->register();
        $invoice = $this->invoiceRepository->save($invoice);
        $this->logger->info(print_r($ecsterOrder, true));

        if ($ecsterOrder->transactions) {
            foreach ($ecsterOrder->transactions as $transaction) {
                if ($transaction->id == $responseData['transactionId']) {
                    $transactionHistoryData = [
                        'id'               => null,
                        'order_id'         => $order->getId(),
                        'entity_type'      => 'invoice',
                        'entity_id'        => $invoice->getId(),
                        'amount'           => $invoice->getGrandTotal(),
                        'transaction_type' => $this->ecsterApi::ECSTER_OMA_TYPE_DEBIT,
                        'request_params'   => serialize($responseData),
                        'order_status'     => $responseData['status'],
                        'transaction_id'   => $transaction->id,
                        'response_params'  => serialize((array)$ecsterOrder),
                    ];
                    $this->helper->addTransactionHistory($transactionHistoryData);
                    break;
                }
            }
        }

        $this->registry->register('current_invoice', $invoice);
        $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

        // If the user select "PENDING_PAYMENT" to be the OEN update on Pending payment the order does not change to
        // processing when invoice is created,
        if ($order->getState() == Order::STATE_PENDING_PAYMENT) {
            $order->setState(Order::STATE_NEW);
        }
        $invoice->getOrder()->setIsInProcess(true);

        $transactionSave = $this->transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );

        $transactionSave->save();

        try {
            //Send Invoice mail to customer
            $this->invoiceSender->send($invoice);
            $orderHistory = $order->addStatusHistoryComment(
                __('Notified customer about invoice creation #%1.', $invoice->getId())
            )->setIsCustomerNotified(true);
            $this->historyRepository->save($orderHistory);
        } catch (Exception $e) {
            $orderHistory= $order->addStatusHistoryComment(
                __('Unable notify customer about invoice creation #%1. Error message: %2', $invoice->getId(), $e->getMessage())
            )->setIsCustomerNotified(false);
            $this->historyRepository->save($orderHistory);
        }
        $this->logger->info(self::LOGGER_PREFIX . __("Created invoice for order %1", $order->getId()));
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
            $quoteId = $response['orderReference'];
            if (strpos($quoteId, Ecster::ECSTER_ORDER_PREFIX) !== false) {
                $quoteId = str_replace(Ecster::ECSTER_ORDER_PREFIX, "", $response['orderReference']);
                try {
                    /** @var \Magento\Quote\Model\Quote $quote */
                    $quote = $this->quoteRepository->get($quoteId);
                } catch (NoSuchEntityException $e) {
                    $this->logger->info(self::LOGGER_PREFIX . "Create Order: Could not find quote with id $quoteId");
                    return;
                }
            } else {
                // As we added an "Reserve order id" function in the frontend we need to check against this as well
                // otherwise some mobile customers might not get their order
                $searchCriteria = $this->searchCriteriaBuilder->addFilter('reserved_order_id', $quoteId)->create();
                $quotes = $this->quoteRepository->getList($searchCriteria)->getItems();
                if (empty($quotes)) {
                    $this->logger->info(self::LOGGER_PREFIX . "Create Order: Could not find quote with reserved order id $quoteId");
                    return;
                }
                $quote = $quotes[0];
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

    protected function checkIfOenAlreadyApplied($oenResponse)
    {
        $previousOenUpdates = $this->transactionsHistoryCollection->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('timestamp', $oenResponse['time']);
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
