<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Ui\Component\Form\Element\Multiline;

class DefaultConfigProvider
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
     * @var \Magento\Customer\Api\AddressMetadataInterface
     */
    protected $addressMetadata;

    /**
     * DefaultConfigProvider constructor.
     *
     * @param \Magento\Checkout\Model\Session                $checkoutSession
     * @param \Evalent\EcsterPay\Helper\Data                 $ecsterpayHelper
     * @param \Magento\Customer\Api\AddressMetadataInterface $addressMetadata
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        EcsterPayHelper $ecsterpayHelper,
        AddressMetadataInterface $addressMetadata
    ) {
        $this->ecsterpayHelper = $ecsterpayHelper;
        $this->checkoutSession = $checkoutSession;
        $this->addressMetadata = $addressMetadata;
    }

    protected function getAddress($quote)
    {
        if ($quote->getIsVirtual()) {
            return $quote->getBillingAddress();
        }

        return $quote->getShippingAddress();
    }

    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        $result
    ) {
        if ($this->ecsterpayHelper->isEnabled()
            && empty($result['shippingAddressFromData'])) {
            $quote = $this->checkoutSession->getQuote();

            if (is_null($this->getAddress($quote)->getCountryId())) {
                $quote->getShippingAddress()
                    ->setCountryId($this->ecsterpayHelper->getDefaultCountry($quote->getStoreId()))
                    ->save();

                $quote->getBillingAddress()
                    ->setCountryId($this->ecsterpayHelper->getDefaultCountry($quote->getStoreId()))
                    ->save();
            }

            $result['shippingAddressFromData'] = $this->getAddressFromData($quote->getShippingAddress());
        }

        return $result;
    }

    protected function getAddressFromData(AddressInterface $address)
    {
        $addressData = [];
        $attributesMetadata = $this->addressMetadata->getAllAttributesMetadata();
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            $attributeData = $address->getData($attributeCode);
            if ($attributeData) {
                if ($attributeMetadata->getFrontendInput() === Multiline::NAME) {
                    $attributeData = \is_array($attributeData) ? $attributeData : explode("\n", $attributeData);
                    $attributeData = (object)$attributeData;
                }
                if ($attributeMetadata->isUserDefined()) {
                    $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES][$attributeCode] = $attributeData;
                    continue;
                }
                $addressData[$attributeCode] = $attributeData;
            }
        }

        return $addressData;
    }

}