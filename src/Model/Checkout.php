<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Exception;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory as CustomerDataFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Checkout extends Onepage
{


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Data $helper,
        Url $customerUrl,
        LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        AddressFactory $customrAddrFactory,
        \Magento\Customer\Model\FormFactory $customerFormFactory,
        CustomerFactory $customerFactory,
        OrderFactory $orderFactory,
        Copy $objectCopyService,
        ManagerInterface $messageManager,
        FormFactory $formFactory,
        CustomerDataFactory $customerDataFactory,
        Random $mathRandom,
        EncryptorInterface $encryptor,
        AddressRepositoryInterface $addressRepository,
        AccountManagementInterface $accountManagement,
        OrderSender $orderSender,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        CartManagementInterface $quoteManagement,
        DataObjectHelper $dataObjectHelper,
        TotalsCollector $totalsCollector
    ) {
        parent::__construct($eventManager, $helper, $customerUrl, $logger,
            $checkoutSession, $customerSession, $storeManager, $request,
            $customrAddrFactory, $customerFormFactory, $customerFactory,
            $orderFactory, $objectCopyService, $messageManager, $formFactory,
            $customerDataFactory, $mathRandom, $encryptor, $addressRepository,
            $accountManagement, $orderSender, $customerRepository,
            $quoteRepository, $extensibleDataObjectConverter, $quoteManagement,
            $dataObjectHelper, $totalsCollector);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $ecsterOrderData
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|object|null
     * @throws \Exception
     */
    public function convertEcsterQuoteToOrder($ecsterOrderData)
    {
        try {
            $_quote = $this->getQuote();
            $_isVirtual = $_quote->getIsVirtual();

            $_billingCountryId = "";
            $_shippingCountryId = "";

            if ($_isVirtual) {
                $_billingCountryId = $_shipppingCountryId = $_quote->getBillingAddress()->getCountryId();
            } else {
                $_billingCountryId = $_quote->getBillingAddress()->getCountryId();
                $_shippingCountryId = $_quote->getShippingAddress()->getCountryId();
            }

            $_customerBillingAddressData = [];
            $_customerShippingAddressData = [];
            $_customerName = [];

            $_consumer = (array)$ecsterOrderData["consumer"];
            $_recipient = isset($ecsterOrderData["recipient"]) ? (array)$ecsterOrderData["recipient"] : [];
            $_contactInfo = (array)$_consumer["contactInfo"];
            $_cellular = (array)$_contactInfo["cellular"];
            $_nationalId = isset($_consumer["nationalId"]) ? $_consumer["nationalId"] : "";

            if ($_recipient) {
                $_shippingName = (array)$_recipient["name"];
                $_shippingAddress = (array)$_recipient["address"];

                if (isset($_shippingAddress['country'])) {
                    $_shippingCountryId = $_shippingAddress['country'];
                }

                $_billingName = (array)$_consumer["name"];
                if (!$_billingName) {
                    $_billingName = $_shippingName;
                }

                $_billingAddress = (array)$_consumer["address"];
                if (!$_billingAddress) {
                    $_billingAddress = $_shippingAddress;
                }

                if (isset($_billingAddress['country'])) {
                    $_billingCountryId = $_billingAddress['country'];
                }

                $_customerName = $_billingName;

                if (isset($_recipient['country'])) {
                    $_billingCountryId = $_recipient['country'];
                    $_shippingCountryId = $_recipient['country'];
                }

                $_customerBillingAddressData = [
                    "email" => $_contactInfo["email"],
                    "nationalId" => $_nationalId,
                    "firstname" => $_billingName["firstName"],
                    "lastname" => $_billingName["lastName"],
                    "street" => $_billingAddress["line1"] . (isset($_billingAddress["line2"]) ? " " . $_billingAddress["line2"] : ""),
                    "city" => $_billingAddress["city"],
                    "region" => $_billingAddress["province"] ?? "",
                    "postcode" => $_billingAddress["zip"],
                    "telephone" => $_cellular["number"],
                    "country_id" => $_billingCountryId
                ];

                $_customerShippingAddressData = [
                    "email" => $_contactInfo["email"],
                    "nationalId" => $_nationalId,
                    "firstname" => $_shippingName["firstName"],
                    "lastname" => $_shippingName["lastName"],
                    "street" => $_shippingAddress["line1"] . (isset($_shippingAddress["line2"]) ? " " . $_shippingAddress["line2"] : ""),
                    "city" => $_shippingAddress["city"],
                    "region" => $_shippingAddress["province"] ?? "",
                    "postcode" => $_shippingAddress["zip"],
                    "telephone" => $_cellular["number"],
                    "country_id" => $_shippingCountryId
                ];
            } else {
                $_customerName = (array)$_consumer["name"];
                $_address = (array)$_consumer["address"];

                $_customerBillingAddressData = [
                    "email" => $_contactInfo["email"],
                    "nationalId" => $_nationalId,
                    "firstname" => $_customerName["firstName"],
                    "lastname" => $_customerName["lastName"],
                    "street" => $_address["line1"] . (isset($_address["line2"]) ? " " . $_address["line2"] : ""),
                    "city" => $_address["city"],
                    "region" => $_address["province"] ?? "",
                    "postcode" => $_address["zip"],
                    "telephone" => $_cellular["number"],
                    "country_id" => $_shippingCountryId
                ];

                $_customerShippingAddressData = $_customerBillingAddressData;
            }

            $customer = $_quote->getCustomer();

            if ($customer->getId()) {
                $_quote->setCheckoutMethod(self::METHOD_CUSTOMER)
                    ->setCustomerIsGuest(0)
                    ->setCustomerId($customer->getId())
                    ->setCustomerEmail($_contactInfo["email"])
                    ->setCustomerFirstname($_customerName['firstName'])
                    ->setCustomerLastname($_customerName['lastName']);
            } else {
                $_quote->setCheckoutMethod(self::METHOD_GUEST)
                    ->setCustomerIsGuest(1)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID)
                    ->setCustomerId(null)
                    ->setCustomerEmail($_contactInfo["email"])
                    ->setCustomerFirstname($_customerName['firstName'])
                    ->setCustomerLastname($_customerName['lastName']);
            }

            $_customerAddressData["customer_id"] = !is_null($customer->getId()) ? $customer->getId() : null;

            $_quote->getBillingAddress()
                ->addData($_customerBillingAddressData)
                ->setCountryId($_billingCountryId)
                ->setShouldIgnoreValidation(true);

            if (!$_isVirtual) {
                $_quote->getShippingAddress()
                    ->addData($_customerShippingAddressData)
                    ->setCountryId($_shippingCountryId)
                    ->setSameAsBilling(1)
                    ->setShouldIgnoreValidation(true);
            }

            $_payment = $_quote->getPayment();
            $_payment->unsMethodInstance()->setMethod("ecsterpay");

            $_paymentData = new DataObject([
                'reference' => $ecsterOrderData["id"],
                'status' => $ecsterOrderData["status"],
                'payment' => "ecsterpay"
            ]);

            $_quote->getPayment()->getMethodInstance()->assignData($_paymentData);
            $_quote->setEcsterInternalReference($ecsterOrderData["id"]);

            if (!is_null($ecsterOrderData['properties'])) {
                $orderProperties = (array)$ecsterOrderData['properties'];

                $_quote->setEcsterProperties(serialize($orderProperties));
                $_quote->setEcsterPaymentType($orderProperties['method']);

                $extraFee = 0;
                switch ($orderProperties['method']) {
                    case "CARD":
                        break;
                    case "INVOICE":
                        $extraFee = $orderProperties['invoiceFee'] / 100;
                        $_quote->setEcsterExtraFee($extraFee);
                        break;
                }
            }

            $_quote->setTotalsCollectedFlag(false)->collectTotals();

            if ($_isVirtual) {
                $_quote->getBillingAddress()->setGrandTotal($_quote->getBillingAddress()->getGrandTotal() + $extraFee);
                $_quote->getBillingAddress()->setBaseGrandTotal($_quote->getBillingAddress()->getBaseGrandTotal() + $extraFee);
            } else {
                $_quote->getShippingAddress()->setGrandTotal($_quote->getShippingAddress()->getGrandTotal() + $extraFee);
                $_quote->getShippingAddress()->setBaseGrandTotal($_quote->getShippingAddress()->getBaseGrandTotal() + $extraFee);
            }

            $order = $this->quoteManagement->submit($_quote);

            $this->_eventManager->dispatch(
                'checkout_type_onepage_save_order_after',
                ['order' => $order, 'quote' => $this->getQuote()]
            );

            $this->getCheckout()
                ->setLastSuccessQuoteId($_quote->getId())
                ->setLastQuoteId($_quote->getId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $this->_eventManager->dispatch(
                'checkout_submit_all_after',
                ['order' => $order, 'quote' => $this->getQuote()]
            );

            if ((bool)$this->scopeConfig->getValue(
                'tax/calculation/shipping_includes_tax',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            )) {
                $orderBaseShippingDiscountTaxCompensationAmnt = number_format(
                    (float)$order->getShippingDiscountTaxCompensationAmount() / (float)$order->getBaseToOrderRate(),
                    2,
                    ".",
                    ""
                );
                $order->setShippingAmount(
                    (float)$order->getShippingAmount() - (float)$order->getShippingTaxAmount() - (float)$order->getShippingDiscountTaxCompensationAmount()
                )
                    ->setBaseShippingDiscountTaxCompensationAmnt($orderBaseShippingDiscountTaxCompensationAmnt)
                    ->setBaseShippingAmount(
                        (float)$order->getBaseShippingAmount() - (float)$order->getBaseShippingTaxAmount() - (float)$orderBaseShippingDiscountTaxCompensationAmnt
                    )
                    ->save();
            }

            if (is_null($order->getEcsterInternalReference())) {
                $order->setEcsterInternalReference($_quote->getEcsterInternalReference())->save();
            }

            return $order;

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}
