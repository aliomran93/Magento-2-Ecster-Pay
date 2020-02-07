<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Checkout\Model;

use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Quote\Model\QuoteRepository;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use phpDocumentor\Reflection\Types\Parent_;
use Psr\Log\LoggerInterface as Logger;

class ShippingInformationManagement extends \Magento\Checkout\Model\ShippingInformationManagement
{

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    private $ecsterApi;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    private $ecsterpayHelper;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param \Magento\Quote\Model\QuoteRepository                             $quoteRepository
     * @param \Evalent\EcsterPay\Model\Api\Ecster                              $ecsterApi
     * @param \Evalent\EcsterPay\Helper\Data                                   $ecsterpayHelper
     * @param \Magento\Checkout\Model\PaymentDetailsFactory                     $paymentMethodManagement
     * @param \Evalent\EcsterPay\Override\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface                  $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface                       $quoteRepository
     * @param \Magento\Quote\Model\QuoteAddressValidator                       $addressValidator
     * @param \Psr\Log\LoggerInterface                                         $logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface                 $addressRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface               $scopeConfig
     * @param \Magento\Quote\Model\Quote\TotalsCollector                       $totalsCollector
     * @param \Magento\Quote\Api\Data\CartExtensionFactory|null                $cartExtensionFactory
     * @param \Magento\Quote\Model\ShippingAssignmentFactory|null              $shippingAssignmentFactory
     * @param \Magento\Quote\Model\ShippingFactory|null                        $shippingFactory
     */
    public function __construct(
        EcsterApi $ecsterApi,
        EcsterPayHelper $ecsterpayHelper,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        CartExtensionFactory $cartExtensionFactory = null,
        ShippingAssignmentFactory $shippingAssignmentFactory = null,
        ShippingFactory $shippingFactory = null
    ) {
        parent::__construct($paymentMethodManagement, $paymentDetailsFactory,
            $cartTotalsRepository, $quoteRepository, $addressValidator, $logger,
            $addressRepository, $scopeConfig, $totalsCollector,
            $cartExtensionFactory, $shippingAssignmentFactory,
            $shippingFactory);
        $this->quoteRepository = $quoteRepository;
        $this->ecsterApi = $ecsterApi;
        $this->ecsterpayHelper = $ecsterpayHelper;
    }

    public function saveAddressInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        if (!$this->ecsterpayHelper->isEnabled()) {
            return parent::saveAddressInformation($cartId,$addressInformation);
        }
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

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
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

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
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

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
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

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
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

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $cartExtension = $paymentDetails->getExtensionAttributes();
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();
                $cartExtension->setEcsterCartKey($ecsterCartKey);
                $paymentDetails->setExtensionAttributes($cartExtension);
            }

            return $paymentDetails;
        }
    }
}