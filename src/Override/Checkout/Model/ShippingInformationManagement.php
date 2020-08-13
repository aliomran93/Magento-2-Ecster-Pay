<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Checkout\Model;

use Exception;
use Magento\Checkout\Api\Data\PaymentDetailsExtensionInterfaceFactory;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
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
     * @var \Magento\Checkout\Api\Data\PaymentDetailsExtensionInterfaceFactory
     */
    private $paymentDetailsExtensionInterfaceFactory;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param \Evalent\EcsterPay\Model\Api\Ecster                                $ecsterApi
     * @param \Evalent\EcsterPay\Helper\Data                                     $ecsterpayHelper
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface                $paymentMethodManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory                      $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface                    $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface                         $quoteRepository
     * @param \Magento\Quote\Model\QuoteAddressValidator                         $addressValidator
     * @param \Magento\Checkout\Api\Data\PaymentDetailsExtensionInterfaceFactory $paymentDetailsExtensionInterfaceFactory
     * @param \Psr\Log\LoggerInterface                                           $logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface                   $addressRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                 $scopeConfig
     * @param \Magento\Quote\Model\Quote\TotalsCollector                         $totalsCollector
     * @param \Magento\Quote\Api\Data\CartExtensionFactory|null                  $cartExtensionFactory
     * @param \Magento\Quote\Model\ShippingAssignmentFactory|null                $shippingAssignmentFactory
     * @param \Magento\Quote\Model\ShippingFactory|null                          $shippingFactory
     */
    public function __construct(
        EcsterApi $ecsterApi,
        EcsterPayHelper $ecsterpayHelper,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        CartTotalRepositoryInterface $cartTotalsRepository,
        CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        PaymentDetailsExtensionInterfaceFactory $paymentDetailsExtensionInterfaceFactory,
        Logger $logger,
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig,
        TotalsCollector $totalsCollector,
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
        $this->paymentDetailsExtensionInterfaceFactory = $paymentDetailsExtensionInterfaceFactory;
    }

    public function saveAddressInformation(
        $cartId,
        ShippingInformationInterface $addressInformation
    ) :PaymentDetailsInterface {
        if (!$this->ecsterpayHelper->isEnabled()) {
            return parent::saveAddressInformation($cartId,$addressInformation);
        }
        /** @var \Magento\Quote\Model\Quote $quote */
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

            $result= $this->setEcsterKey($result, $quote);

            return $result;

        } catch (StateException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            $paymentDetails= $this->setEcsterKey($paymentDetails, $quote);

            return $paymentDetails;

        } catch (InputException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            $paymentDetails= $this->setEcsterKey($paymentDetails, $quote);

            return $paymentDetails;

        } catch (NoSuchEntityException $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            $paymentDetails= $this->setEcsterKey($paymentDetails, $quote);

            return $paymentDetails;

        } catch (Exception $e) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

            $paymentDetails = $this->setEcsterKey($paymentDetails, $quote);

            return $paymentDetails;
        }
    }

    /**
     * @param \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails
     * @param \Magento\Quote\Model\Quote             $quote
     *
     * @return mixed
     */
    private function setEcsterKey($paymentDetails, $quote)
    {
        if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
            $cartExtension = $paymentDetails->getExtensionAttributes();
            if ($cartExtension == null) {
                $cartExtension = $this->paymentDetailsExtensionInterfaceFactory->create();
            }
            $cartExtension->setEcsterCartKey($ecsterCartKey);
            $paymentDetails->setExtensionAttributes($cartExtension);
        }
        return $paymentDetails;
    }
}
