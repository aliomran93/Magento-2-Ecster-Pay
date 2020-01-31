<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Checkout\Model;

use Magento\Quote\Model\QuoteRepository;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class ShippingInformationManagement extends \Magento\Checkout\Model\ShippingInformationManagement
{

    protected $quoteRepository;
    protected $_ecsterApi;
    protected $_helper;

    public function _construct(
        QuoteRepository $quoteRepository,
        EcsterApi $ecsterApi,
        EcsterPayHelper $ecsterpayHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_ecsterApi = $ecsterApi;
        $this->_helper = $ecsterpayHelper;
    }

    public function saveAddressInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_ecsterApi = $objectManager->get(\Evalent\EcsterPay\Model\Api\Ecster::class);

        $quote = $this->quoteRepository->getActive($cartId);

        try {
            $result = parent::saveAddressInformation($cartId, $addressInformation);

            $address = $addressInformation->getShippingAddress();

            if ($addressExternalAttributes = $address->getExtensionAttributes()) {
                if ($nationalId = $addressExternalAttributes->getNationalId()) {
                    $quote->getShippingAddress()->setData('national_id', $nationalId)->save();
                    $quote->getBillingAddress()->setData('national_id', $nationalId)->save();
                }

                if ($firstname = $addressExternalAttributes->getFirstname()) {
                    $quote->getShippingAddress()->setData('firstname', $firstname)->save();
                    $quote->getBillingAddress()->setData('firstname', $firstname)->save();
                }

                if ($lastname = $addressExternalAttributes->getLastname()) {
                    $quote->getShippingAddress()->setData('lastname', $lastname)->save();
                    $quote->getBillingAddress()->setData('lastname', $lastname)->save();
                }

                if ($address = $addressExternalAttributes->getAddress()) {
                    $quote->getShippingAddress()->setData('street', $address)->save();
                    $quote->getBillingAddress()->setData('street', $address)->save();
                }

                if ($city = $addressExternalAttributes->getCity()) {
                    $quote->getShippingAddress()->setData('city', $city)->save();
                    $quote->getBillingAddress()->setData('city', $city)->save();
                }

                if ($region = $addressExternalAttributes->getRegion()) {
                    $quote->getShippingAddress()->setData('region', $region)->save();
                    $quote->getBillingAddress()->setData('region', $region)->save();
                }

                if ($zip = $addressExternalAttributes->getZip()) {
                    $quote->getShippingAddress()->setData('postcode', $zip)->save();
                    $quote->getBillingAddress()->setData('postcode', $zip)->save();
                }
            }

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $cartExtension = $result->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $result->setExtensionAttributes($cartExtension);
            }

            return $result;

        } catch (\Magento\Framework\Exception\StateException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $cartExtension = $paymentDetails->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $paymentDetails->setExtensionAttributes($cartExtension);
            }

            return $paymentDetails;

        } catch (\Magento\Framework\Exception\InputException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $cartExtension = $paymentDetails->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $paymentDetails->setExtensionAttributes($cartExtension);
            }

            return $paymentDetails;

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $cartExtension = $paymentDetails->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $paymentDetails->setExtensionAttributes($cartExtension);
            }

            return $paymentDetails;

        } catch (\Exception $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $cartExtension = $paymentDetails->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $paymentDetails->setExtensionAttributes($cartExtension);
            }

            return $paymentDetails;
        }
    }
}