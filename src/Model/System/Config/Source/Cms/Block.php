<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\System\Config\Source\Cms;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Block implements OptionSourceInterface
{
    private $options;

    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->collectionFactory->create()->toOptionIdArray();
        }

        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($this->options as $key => $value) {
            $options[] = ['value' => $value['value'], 'label' => $value['label']];
        }

        return $options;
    }
}
