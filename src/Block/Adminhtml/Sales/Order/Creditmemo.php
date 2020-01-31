<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\Sales\Order;

use Magento\Backend\Block\Widget\Grid\Container;
use Magento\Framework\App\RequestInterface;

class Creditmemo extends Container
{
    protected function _construct()
    {
        $this->_blockGroup = "Evalent_EcsterPay";
        $this->_controller = "adminhtml_sales_order_creditmemo";
        $this->_headerText = __("Invoice(s)");
        parent::_construct();

        $this->buttonList->remove('add');

        $this->addButton(
            'order_reorder',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getBackUrl() . '\')',
                'class' => 'back'
            ]
        );
    }

    public function getBackUrl()
    {
        return $this->getUrl('sales/order/view', ['order_id' => $this->getRequest()->getParam('order_id')]);
    }
}