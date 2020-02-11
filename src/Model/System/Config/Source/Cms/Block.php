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
            $this->options =  $this->toOptionIdArray($this->collectionFactory->create());
        }

        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($this->options as $key => $value) {
            $options[] = ['value' => $value['value'], 'label' => $value['label']];
        }

        return $options;
    }

    /**
     * Returns pairs identifier - title for unique identifiers
     * and pairs identifier|entity_id - title for non-unique after first
     *
     * @param \Magento\Cms\Model\ResourceModel\Block\Collection $collection
     *
     * @return array
     */
    private function toOptionIdArray($collection)
    {
        $res = [];
        $existingIdentifiers = [];
        /** @var \Magento\Cms\Api\Data\BlockInterface $item */
        foreach ($collection as $item) {
            $identifier = $item->getData('identifier');

            $data['value'] = $identifier;
            $data['label'] = $item->getData('title');

            if (in_array($identifier, $existingIdentifiers)) {
                $data['value'] .= '|' . $item->getData($collection->getIdFieldName());
            } else {
                $existingIdentifiers[] = $identifier;
            }

            $res[] = $data;
        }

        return $res;
    }
}
