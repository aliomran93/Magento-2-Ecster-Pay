<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Evalent\EcsterPay\Model\SalesOrderStatusUpdate;
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
                    try {
                        $this->_orderStatusUpdate->process($responseJson);
                    } catch (\Exception $ex) {
                        // The first OEN usually comes before the order is created, causing the above to throw an
                        // exception, in that case we wait for a while and try ONCE again.
                        $this->_logger->info("OEN error: ". $ex->getMessage(). ". Retrying once in 10 sec");
                        sleep(10);
                        $this->_orderStatusUpdate->process($responseJson);
                    }
                } else {
                    $this->_logger->info(__("Ecster OPN: Json Error"));
                    $this->_logger->info($responseJson);
                    $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request')->sendResponse();
                }
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