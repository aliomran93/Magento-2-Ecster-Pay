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

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    protected $ecsterpayHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        StoreResolverInterface $storeResolver,
        DirectoryHelper $directoryHelper,
        CheckoutSession $checkoutSession,
        EcsterPayHelper $ecsterpayHelper,
        StoreManagerInterface $storeManager = null
    ) {
        $this->directoryHelper = $directoryHelper;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->ecsterpayHelper = $ecsterpayHelper;
        $this->checkoutSession = $checkoutSession;
    }

    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
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
        if ($this->ecsterpayHelper->isEnabled()) {
            if (isset($jsLayout['components']['checkoutProvider']['dictionaries']['country_id'])) {
                $countryList = [];
                $selectedCountries = $this->ecsterpayHelper->getAllowedCountries($this->storeManager->getStore()->getId());
                foreach ($jsLayout['components']['checkoutProvider']['dictionaries']['country_id'] as &$country) {
                    if ((count($selectedCountries) < 1 || in_array($country['value'], $selectedCountries))
                        && $country['value'] != '') {
                        if ($country['value'] == !is_null($this->getAddress()->getCountryId()) ? $this->getAddress()->getCountryId() : $this->ecsterpayHelper->getDefaultCountry()) {
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