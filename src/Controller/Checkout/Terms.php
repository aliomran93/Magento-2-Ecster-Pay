<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Framework\App\Action\Action;

class Terms extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Ecster Checkout: Shop Terms'));
        $this->_view->renderLayout();
    }
}

