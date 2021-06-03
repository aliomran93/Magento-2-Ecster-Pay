<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\SalesOrderStatusUpdate;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

abstract class AbstractOen extends Action
{
    protected $_helper;
    protected $_orderStatusUpdate;
    protected $_logger;

    public function __construct(
        Context $context,
        EcsterPayHelper $helper,
        SalesOrderStatusUpdate $orderStatusUpdate,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $context
        );

        $this->_helper = $helper;
        $this->_orderStatusUpdate = $orderStatusUpdate;
        $this->_logger = $logger;
    }

    public function execute()
    {
        if ($responseJson = file_get_contents('php://input')) {
            try {
                if ($this->_helper->isValidJson($responseJson)) {
                    $response = (array)json_decode($responseJson);
                    try {
                        $this->_logger->info("Ecters OEN Processing data: " . $responseJson);
                        $this->_orderStatusUpdate->process($response);
                    } catch (NoSuchEntityException $ex) {
                        // The first OEN usually comes before the order is created, causing the above to throw an
                        // exception, in that case we wait for a while and try ONCE again.
                        $this->_logger->info("OEN error: " . $ex->getMessage() . ". Retrying once in 10 sec");
                        sleep(10);
                        $this->_orderStatusUpdate->process($response, true);
                    }
                } else {
                    $this->_logger->info(__("Ecster OEN: Json Error"));
                    $this->_logger->info($responseJson);
                    $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request')->sendResponse();
                }
            } catch (LocalizedException $ex) {
                // We need to return a 200 response if the order does not exist. This is because the PENDING_PAYMENT update is
                // send before the order is created and therefore causes an error and is resend after 2 hours.
                $this->_logger->info($ex->getMessage());
                $transactionHistoryData = [
                    'id' => null,
                    'order_id' => null,
                    'entity_type' => null,
                    'entity_id' => null,
                    'amount' => null,
                    'transaction_type' => EcsterApi::ECSTER_OMA_TYPE_OEN_UPDATE,
                    'request_params' => null,
                    'order_status' => $response['status'],
                    'transaction_id' => null,
                    'response_params' => serialize($response),
                    'timestamp' => $response['time'],
                ];
                $this->_helper->addTransactionHistory($transactionHistoryData);
                $this->getResponse()->setStatusHeader(200, '1.1', 'Bad Request')->sendResponse();
                return;
            } catch (\Exception $ex) {
                $this->_logger->info($ex->getMessage());
                $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request')->sendResponse();
                return;
            }
        } else {
            $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request')->sendResponse();
            return;
        }
    }
}
