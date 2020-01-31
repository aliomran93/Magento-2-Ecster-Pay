<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Creditmemo\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class Action extends AbstractRenderer
{

    public function render(DataObject $row)
    {
        return "<a href=\"" . $this->getUrl('sales/order_creditmemo/new',
                ['order_id' => $row->getOrderId(), 'invoice_id' => $row->getId()]) . "\" >" . __('Select') . "</a>";
    }
}
