<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Plugin\Checkout\Block;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class DirectoryDataProcessor
{
    protected $_helper;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection,
        StoreResolverInterface $storeResolver,
        DirectoryHelper $directoryHelper,
        CheckoutSession $checkoutSession,
        EcsterPayHelper $ecsterpayHelper,
        StoreManagerInterface $storeManager = null
    ) {
        $this->countryCollectionFactory = $countryCollection;
        $this->regionCollectionFactory = $regionCollection;
        $this->directoryHelper = $directoryHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->_helper = $ecsterpayHelper;
    }

    protected function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    protected function getAddress()
    {
        if ($this->getQuote()->getIsVirtual()) {
            return $this->getQuote()->getBillingAddress();
        }

        return $this->getQuote()->getShippingAddress();
    }

    public function afterProcess(
        \Magento\Checkout\Block\Checkout\DirectoryDataProcessor $subject,
        array $jsLayout
    ) {
        if ($this->_helper->isEnabled()) {
            if (isset($jsLayout['components']['checkoutProvider']['dictionaries']['country_id'])) {
                $countryList = [];
                $selectedCountries = $this->_helper->getAllowedCountries($this->storeManager->getStore()->getId());
                foreach ($jsLayout['components']['checkoutProvider']['dictionaries']['country_id'] as &$country) {
                    if ((count($selectedCountries) < 1 || in_array($country['value'], $selectedCountries))
                        && $country['value'] != '') {
                        if ($country['value'] == !is_null($this->getAddress()->getCountryId()) ? $this->getAddress()->getCountryId() : $this->_helper->getDefaultCountry()) {
                            $country["is_default"] = true;
                        }
                        $countryList[] = $country;
                    }
                }

                $jsLayout['components']['checkoutProvider']['dictionaries']['country_id'] = $countryList;
            }
        }

        return $jsLayout;
    }
}