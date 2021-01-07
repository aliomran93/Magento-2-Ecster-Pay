<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\Api;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;

class Ecster
{
    private $_localeResolver;

    private $customerData;

    // todo: phpdocs
    protected $_urlBuilder;
    protected $_taxCalculation;
    protected $_helper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;
    protected $_storeId;
    protected $_cartTotal;

    const ECSTER_GATEWAY_TEST_URL = "https://labs.ecster.se";
    const ECSTER_GATEWAY_LIVE_URL = "https://secure.ecster.se";

    const ECSTER_JS_TEST_URL = "https://labs.ecster.se/pay/integration/ecster-pay-labs.js";
    const ECSTER_JS_LIVE_URL = "https://secure.ecster.se/pay/integration/ecster-pay.js";

    const ECSTER_DEFAULT_LANGUAGE = "en";

    const ECSTER_ORDER_PREFIX = "ORDN-";

    const PURCHASE_TYPE_B2C = 'B2C';
    const PURCHASE_TYPE_B2B = 'B2B';
    const PURCHASE_TYPE_OPTIONAL = 'OPTIONAL';

    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    const ECSTER_OMA_TYPE_OEN_UPDATE = 'OEN_UPDATE';
    const ECSTER_OMA_TYPE_DEBIT = 'DEBIT';
    const ECSTER_OMA_TYPE_CREDIT = 'CREDIT';
    const ECSTER_OMA_TYPE_ANNUL = 'ANNUL';

    protected $_supportedLanguages = ["sv", "en", "no", "da", "fi"];

    public $ecsterQuoteItemFields = [
        "partNumber" => [
            "column" => "sku",
            "ecster_type" => "integer",
            "type" => "string"
        ],
        "name" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "description" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "quantity" => [
            "column" => "qty",
            "ecster_type" => "integer",
            "type" => "float"
        ],
        "unitAmount" => [
            "column" => "price",
            "ecster_type" => "float",
            "type" => "float"
        ],
        "vatRate" => [
            "column" => "tax_percent",
            "ecster_type" => "float",
            "type" => "float",
            //"default_value" => 25
        ],
//        "discountAmount" => array(
//            "column" => "discount_amount",
//            "ecster_type" => "float",
//            "type" => "float"
//        )
    ];

    public $ecsterInvoiceItemFields = [
        "partNumber" => [
            "column" => "sku",
            "ecster_type" => "integer",
            "type" => "string"
        ],
        "name" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "description" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "quantity" => [
            "column" => "qty",
            "ecster_type" => "integer",
            "type" => "float"
        ],
        "unitAmount" => [
            "column" => "price",
            "ecster_type" => "float",
            "type" => "float"
        ],
        "vatRate" => [
            "column" => "tax_percent",
            "ecster_type" => "float",
            "type" => "float",
            //"default_value" => 25
        ],
//        "discountAmount" => array(
//            "column" => "discount_amount",
//            "ecster_type" => "float",
//            "type" => "float"
//        )
    ];

    public $ecsterCreditmemoItemFields = [
        "partNumber" => [
            "column" => "sku",
            "ecster_type" => "integer",
            "type" => "string"
        ],
        "name" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "description" => [
            "column" => "name",
            "ecster_type" => "string",
            "type" => "string"
        ],
        "quantity" => [
            "column" => "qty",
            "ecster_type" => "integer",
            "type" => "float"
        ],
        "unitAmount" => [
            "column" => "price",
            "ecster_type" => "float",
            "type" => "float"
        ],
        "vatRate" => [
            "column" => "tax_percent",
            "ecster_type" => "float",
            "type" => "float",
            //"default_value" => 25
        ],
//        "discountAmount" => array(
//            "column" => "discount_amount",
//            "ecster_type" => "float",
//            "type" => "float"
//        )
    ];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $addressRepository;

    public function __construct(
        EcsterPayHelper $helper,
        Resolver $resolver,
        UrlInterface $urlBuilder,
        TaxCalculation $taxCalculation,
        RequestInterface $request,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->_helper = $helper;
        $this->_localeResolver = $resolver;
        $this->_taxCalculation = $taxCalculation;
        $this->_urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->addressRepository = $addressRepository;
    }

    public function getJsUrl($storeId)
    {
        switch ($this->getTransactionMode($storeId)) {
            case self::MODE_TEST:
                return self::ECSTER_JS_TEST_URL;
                break;
            case self::MODE_LIVE:
                return self::ECSTER_JS_LIVE_URL;
                break;
        }
    }

    protected function getEcsterCartUrl()
    {
        return "/rest/public/v1/carts";
    }

    protected function getEcsterOrderUrl()
    {
        return "/rest/public/v1/orders";
    }

    public function getCartUrl()
    {
        return $this->_urlBuilder->getUrl('checkout/cart', ['_secure' => true]);
    }

    public function getShopTermsUrl()
    {
        return $this->_urlBuilder->getUrl('ecsterpay/checkout/terms', ['_secure' => true]);
    }

    public function getReturnUrl()
    {
        return $this->_urlBuilder->getUrl('ecsterpay/checkout/success', ['_secure' => true]);
    }

    public function getNotificationUrl()
    {
        return $this->_urlBuilder->getUrl('ecsterpay/checkout/oen', ['_secure' => true]);
    }

    protected function getApiKey()
    {
        return $this->_helper->getApiKey($this->_storeId);
    }

    protected function getMerchantKey()
    {
        return $this->_helper->getMerchantKey($this->_storeId);
    }

    protected function getTransactionMode()
    {
        return $this->_helper->getTransactionMode($this->_storeId);
    }

    protected function getTransactionUrl()
    {
        switch ($this->getTransactionMode()) {
            case self::MODE_TEST:
                return self::ECSTER_GATEWAY_TEST_URL;
            default:
            case self::MODE_LIVE:
                return self::ECSTER_GATEWAY_LIVE_URL;
        }
    }

    protected function getShowCart()
    {
        return $this->_helper->getShowCart($this->_storeId);
    }

    protected function getPurchaseType()
    {
        $purchaseType = $this->_helper->getPurchaseType($this->_storeId);
        if ($purchaseType == Ecster::PURCHASE_TYPE_OPTIONAL) {
            return $this->_helper->getPreselectedPurchaseType();
        }
        return $purchaseType;
    }

    protected function getCountryId()
    {
        if (!is_null($this->getAddress()->getCountryId())) {
            return $this->getAddress()->getCountryId();
        }

        return $this->_helper->getDefaultCountry($this->_storeId);
    }

    protected function getLocale()
    {
        $langCode = $this->_localeResolver->getLocale();

        if ($langCode && strpos($langCode, "_") !== false) {
            $_langCode = explode("_", $langCode);

            if (in_array($_langCode[0], $this->_supportedLanguages)) {
                return $_langCode[0];
            } else {
                return self::ECSTER_DEFAULT_LANGUAGE;
            }
        }

        return self::ECSTER_DEFAULT_LANGUAGE;
    }

    protected function getAddress()
    {
        if ((bool)$this->_quote->getIsVirtual()) {
            return $this->_quote->getBillingAddress();
        }

        return $this->_quote->getShippingAddress();
    }

    public function createDummyItem($price, $label = "Roundig Difference", $partNumber = "dummy-9999")
    {
        return [
            "partNumber" => $partNumber,
            "name" => $label,
            "description" => $label,
            "quantity" => 1,
            "unitAmount" => $price * 100,
            "vatRate" => 0,
            "discountAmount" => 0,
            "fee" => true
        ];
    }

    protected function convertQuoteItemsToEcster()
    {
        $quoteItems = $this->_quote->getAllVisibleItems();

        $items = [];
        $this->_cartTotal = 0;
        $this->_cartTotalControl = 0;

        $catalogCalculationMethod = $this->_helper->getCatalogTaxCalculationMethod($this->_storeId);
        $discountApplyMethod = $this->_helper->getApplyDiscountMethod($this->_storeId);

        foreach ($quoteItems as $quoteItem) {
            $item = [];

            if ($quoteItem->getProductType() != 'simple' && $quoteItem->getHasChildren()
            ) {
                foreach ($this->ecsterQuoteItemFields as $field => $options) {
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($field == 'description') {
                            $description = [];
                            foreach ($quoteItem->getChildren() as $child) {
                                $description[] = $child->getName();
                            }
                            $var = implode(",", $description);
                        } elseif ($field == 'vatRate' && $quoteItem->getProductType() == 'bundle') {
                            $vatRate = 0;
                            foreach ($quoteItem->getChildren() as $childItem) {
                                $vatRate += $childItem->getData('tax_percent');
                            }
                            $var = $vatRate / sizeof($quoteItem->getChildren());
                        } else {
                            if ($options['column'] == 'price') {
                                if ($discountApplyMethod) {
                                    if ($catalogCalculationMethod) {
                                        $var = $quoteItem->getData('price_incl_tax');
                                    } else {
                                        $var = $quoteItem->getData('price') + ($quoteItem->getData('tax_amount') / $quoteItem->getData('qty'));
                                    }
                                } else {
                                    $var = $quoteItem->getData('price_incl_tax');
                                }

                                $this->_cartTotal += $var * $quoteItem->getData('qty');
                                $this->_cartTotalControl += $this->_helper->ecsterFormatPrice($var) * $quoteItem->getData('qty');

//                        } else if($options['column'] == 'discount_amount') {
//
//                            if($quoteItem->getProductType() == 'bundle'
//                                    && $quoteItem->getHasChildren()) {
//
//                                $var = 0;
//                                foreach($quoteItem->getChildren() as $childItem) {
//                                    $var += $childItem->getData('discount_amount');
//                                }
//
//                                $this->_cartTotal -= $var;
//                                $this->_cartTotalControl -= $this->_helper->ecsterFormatPrice($var);
//
//                            } else {
//                                $var = $quoteItem->getData('discount_amount');
//                                $this->_cartTotal -= $var;
//                                $this->_cartTotalControl -= $this->_helper->ecsterFormatPrice($var);
//                            }
//
//                        }
                            } else {
                                $var = $quoteItem->getData($options['column']);
                            }
                        }
                    }

                    if ($options['ecster_type'] == 'float') {
                        $var = $this->_helper->ecsterFormatPrice($var);
                    }

                    if ($options['column'] != 'discount_amount') {
                        settype($var, $options['type']);
                        $item[$field] = $var;
                    }
                }
            } else {
                foreach ($this->ecsterQuoteItemFields as $field => $options) {
                    $itemDiscountAmount = 0;
                    if (isset($options["default_value"])) {
                        $var = $options["default_value"];
                    } else {
                        if ($options['column'] == 'price') {
                            if ($discountApplyMethod) {
                                if ($catalogCalculationMethod) {
                                    $var = $quoteItem->getData('price_incl_tax');
                                } else {
                                    $var = $quoteItem->getData('price') + ($quoteItem->getData('tax_amount') / $quoteItem->getData('qty'));
                                }
                            } else {
                                $var = $quoteItem->getData('price_incl_tax');
                            }

                            $this->_cartTotal += $var * $quoteItem->getData('qty');
                            $this->_cartTotalControl += $this->_helper->ecsterFormatPrice($var) * $quoteItem->getData('qty');

//                    } else if($options['column'] == 'discount_amount') {
//
//                            $var = $quoteItem->getData('discount_amount');
//                            $this->_cartTotal -= $var;
//                            $this->_cartTotalControl -= $this->_helper->ecsterFormatPrice($var);
//
                        } else {
                            $var = $quoteItem->getData($options['column']);
                        }
                    }

                    if ($options['ecster_type'] == 'float') {
                        $var = $this->_helper->ecsterFormatPrice($var);
                    }

                    if ($options['column'] != 'discount_amount') {
                        settype($var, $options['type']);
                        $item[$field] = $var;
                    }
                }
            }

            $items[] = $item;
        }

        if ($this->getAddress()->getDiscountAmount() < 0) {
            $this->_cartTotal += $this->getAddress()->getDiscountAmount();
            $this->_cartTotalControl += $this->_helper->ecsterFormatPrice($this->getAddress()->getDiscountAmount());
            $items[] = $this->createDummyItem($this->getAddress()->getDiscountAmount(), "Discount", "Discount");
        }

        $this->_cartTotal = (float)$this->_helper->ecsterFormatPrice($this->_cartTotal);

        if ($this->_cartTotal != $this->_cartTotalControl) {
            $diff = ($this->_cartTotal - $this->_cartTotalControl) / 100;
            $items[] = $this->createDummyItem($diff);
        }

        return $items;
    }

    protected function getQuoteCurrencyCode()
    {
        return $this->_quote->getQuoteCurrencyCode();
    }

    protected function getQuoteMessage()
    {
        return !is_null($this->_quote->getCustomerNote()) ? $this->_quote->getCustomerNote() : "";
    }

    protected function getHeaderParams()
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->getApiKey(),
            'x-merchant-key' => $this->getMerchantKey()
        ];
    }

    protected function getLocaleDatas()
    {
        return [
            "language" => $this->getLocale(),
            "country" => $this->_helper->getDefaultCountry($this->_storeId)
        ];
    }

    protected function getDefaultDatas()
    {
        $purchaseType = strtoupper($this->getPurchaseType());
        if ($this->request->getParam('type')) {
            $purchaseType = strtoupper($this->request->getParam('type'));
            if ($purchaseType != self::PURCHASE_TYPE_B2B && $purchaseType != self::PURCHASE_TYPE_B2C) {
                $purchaseType = strtoupper($this->getPurchaseType());
            }
        }
        return [
            "shopTermsUrl" => $this->getShopTermsUrl(),
            "returnUrl" => $this->getReturnUrl(),
            "defaultDeliveryCountry" => $this->getCountryId(),
            "purchaseType" => [
                "type" => $purchaseType,
                "show" => true
            ]
        ];
    }

    protected function getCartDatas()
    {
        $cartItems = $this->convertQuoteItemsToEcster();

        return [
            "amount" => $this->_cartTotal,
            "currency" => $this->getQuoteCurrencyCode(),
            "message" => $this->getQuoteMessage(),
            "rows" => $cartItems
        ];
    }

    protected function getConsumerDatas()
    {
        if (!$this->customerData) {
            $customerData = [];

            $customer = $this->_quote->getCustomer();
            $customerAddress = null;
//            if ($customer) {
//                if ($this->_quote->isVirtual() && $customer->getDefaultBilling()) {
//                    $customerAddress = $this->addressRepository->getById($customer->getDefaultBilling());
//                } elseif ($customer->getDefaultShipping()) {
//                    $customerAddress = $this->addressRepository->getById($customer->getDefaultShipping());
//                }
//            }

//            if (!is_null($this->getAddress()->getNationalId())
//                && $this->getAddress()->getNationalId() != ""
//            ) {
//                $customerData["nationalId"] = $this->getAddress()->getNationalId();
//            }

            if (!is_null($this->getAddress()->getFirstname())
                && $this->getAddress()->getFirstname() != ""
            ) {
                $customerData["name"]["firstName"] = trim($this->getAddress()->getFirstname());
            }

            if (!is_null($this->getAddress()->getLastname())
                && $this->getAddress()->getLastname() != ""
            ) {
                $customerData["name"]["lastName"] = trim($this->getAddress()->getLastname());
            }

            if (implode(" ", $this->getAddress()->getStreet()) != "") {
                $street = $this->getAddress()->getStreet();
                if (!in_array($this->getAddress()->getCountryId(), ["DE", "AT"])) {
                    if (sizeof($street) > 1) {
                        $customerData["address"]["line1"] = $street[0];
                        $customerData["address"]["line2"] = implode(" ", array_slice($street, 1));
                    } else {
                        $customerData["address"]["line1"] = implode(" ", $street);
                    }
                } else {
                    $customerData["address"]["streetName"] = implode(" ", $street);
                }
            }

            if (!is_null($this->getAddress()->getCity()) && $this->getAddress()->getCity() != "") {
                $customerData["address"]["city"] = trim($this->getAddress()->getCity());
            }

            if (!is_null($this->getAddress()->getPostCode()) && $this->getAddress()->getPostCode() != "") {
                $customerData["address"]["zip"] = trim($this->getAddress()->getPostCode());
            }

            if (!is_null($this->getAddress()->getCountryId()) && $this->getAddress()->getCountryId() != "") {
                $customerData["address"]["country"] = $this->getAddress()->getCountryId();
            }

            if (!is_null($this->getAddress()->getTelephone()) && $this->getAddress()->getTelephone() != "") {
                $customerData["contactInfo"]["cellular"]['number'] = trim($this->getAddress()->getTelephone());
            }

            $customerData["contactInfo"]["email"] = $this->_quote->getCustomerEmail();

            $this->customerData = $customerData;
        }
        return $this->customerData;
    }

    protected function getOrderReference()
    {
        return self::ECSTER_ORDER_PREFIX . $this->_quote->getId();
    }

    protected function calculateShippingPrice($price)
    {
        $discountApplyMethod = $this->_helper->getApplyDiscountMethod($this->_storeId);

        if ($discountApplyMethod) {
            $priceForTaxCalculation = $price - $this->_quote->getShippingAddress()->getShippingDiscountAmount();
        } else {
            $priceForTaxCalculation = $price;
        }

        if ($this->_helper->getShippingTaxCalculationMethod($this->_storeId)) {
            return $price;
        }

        if (!is_null($this->_quote->getShippingAddress()->getShippingTaxAmount())) {
            return $price + $this->_quote->getShippingAddress()->getShippingTaxAmount();
        } else {
            $shippingTaxId = $this->_helper->getShippingTaxId($this->_storeId);

            $customerTaxClassId = $this->_taxCalculation->getDefaultCustomerTaxClass($this->_storeId);

            $request = $this->_taxCalculation->getRateRequest(
                $this->_quote->getShippingAddress(),
                $this->_quote->getBillingAddress(),
                $customerTaxClassId,
                $this->_storeId
            );

            $taxPercent = $this->_taxCalculation->getRate($request->setProductClassId($shippingTaxId));

            return $price + $this->_taxCalculation->calcTaxAmount(
                $priceForTaxCalculation,
                $taxPercent,
                $this->_helper->getShippingTaxCalculationMethod($this->_storeId)
            );
        }
    }

    protected function getAvailableShippingMethod()
    {
        $shippingMethods = [];

        if ($this->_quote->getIsVirtual()) {
            return $shippingMethods;
        }

        $this->_quote->collectTotals();

        $address = $this->_quote->getShippingAddress();
        $address->setCollectShippingRates(true);
        $address->collectShippingRates();

        $_rates = $address->getAllShippingRates();

        foreach ($_rates as $_rate) {
            $shippingMethods[] = [
                "id" => $_rate->getMethod(),
                "name" => "Shipping: " . $_rate->getMethodTitle(),
                "description" => $_rate->getMethodDescription(),
                "price" => $_rate->getPrice() > 0 ? $this->_helper->ecsterFormatPrice($this->calculateShippingPrice($_rate->getPrice())) : 0,
                "selected" => !is_null($address->getShippingMethod()) && $_rate->getCarrier() . "_" . $_rate->getMethod() == $address->getShippingMethod() ? true : false
                //($_rate->getCarrier() . "_" .  $_rate->getMethod() == $this->_helper->getDefaultShippingMethod($this->getAddress()->getCountryId()) ? true : false)
            ];
        }

        // IF the are no available shipping method we want to trigger the "Select shipping method" message in ecster and to do that we need two shipping methods whish is not selected
        if (sizeof($shippingMethods) < 1) {
            $shippingMethods[] = [
                "id" => "0",
                "name" => "NO SHIPPING CHOSEN",
                "price" => 0,
                "selected" => false
            ];
            $shippingMethods[] = [
                "id" => "1",
                "name" => "NO SHIPPING CHOSEN",
                "price" => 0,
                "selected" => false
            ];
        }

        return $shippingMethods;
    }

    protected function getEcstrParams()
    {
        $ecsterParams = [
            "locale" => $this->getLocaleDatas(),
            "countryCode" => $this->_helper->getDefaultCountry($this->_storeId),
            "parameters" => $this->getDefaultDatas(),
            "deliveryMethods" => $this->getAvailableShippingMethod(),
            "cart" => $this->getCartDatas(),
            "orderReference" => $this->getOrderReference(),
            "notificationUrl" => $this->getNotificationUrl(),
            "platform" => ["reference" => "bfd8d780-c090-4f22-9c7b-49dd9db1c4cd"]
        ];

        if ($this->getConsumerDatas()) {
            $ecsterParams = array_merge($ecsterParams, ["consumer" => $this->getConsumerDatas()]);
        }

        return $ecsterParams;
    }

    protected function _createCart($quote)
    {
        $this->_quote = $quote;
        $this->_storeId = $this->_quote->getStoreId();

        $jsonDataEncoded = [
            'body' => json_encode($this->getEcstrParams())
        ];

        return $this->setResponse($this->getEcsterCartUrl(), $jsonDataEncoded);
    }

    protected function _updateCart($quote, $cartKey)
    {
        $this->_quote = $quote;
        $this->_storeId = $this->_quote->getStoreId();

        $cartUpdateUrl = $this->getEcsterCartUrl() . "/" . $cartKey;

        $jsonDataEncoded = [
            'body' => json_encode($this->getEcstrParams())
        ];

        return $this->setResponse($cartUpdateUrl, $jsonDataEncoded, 'PUT');
    }

    protected function _getOrderFromEcster($ecsterOrderId)
    {
        $orderUrl = $this->getEcsterOrderUrl() . "/" . $ecsterOrderId;
        $jsonDataEncoded = [];

        return $this->setResponse($orderUrl, $jsonDataEncoded, "GET");
    }

    protected function _updateOrderReference($ecsterOrderId, $orderReference)
    {
        $orderUpdateUrl = $this->getEcsterOrderUrl() . "/" . $ecsterOrderId . "/orderReference";

        $jsonDataEncoded = [
            'body' => json_encode([
                "orderReference" => $orderReference
            ])
        ];

        return $this->setResponse($orderUpdateUrl, $jsonDataEncoded, 'PUT');
    }

    public function orderProcess($ecsterOrderId, $requestParams)
    {
        $orderProcessUrl = $this->getEcsterOrderUrl() . "/" . $ecsterOrderId . "/transactions";

        $jsonDataEncoded = [
            'body' => json_encode($requestParams)
        ];

        return $this->setResponse($orderProcessUrl, $jsonDataEncoded);
    }

    protected function setResponse($url, $data, $method = 'POST')
    {
        // todo: fix logger
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ecster_api.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($url, true));
        $logger->info(print_r($method, true));
        $logger->info(print_r($data, true));

        $responseJson = $this->_helper->getDataWithGuzzle(
            $this->getTransactionUrl(),
            $url,
            $this->getHeaderParams(),
            $method,
            $data
        );

        $logger->info(print_r($responseJson, true));

        if ($this->_helper->isValidJson($responseJson)) {
            $response = json_decode($responseJson);

            if (is_object($response)
                && property_exists($response, 'checkoutCart')
                && isset($response->checkoutCart->key)
            ) {
                return $response->checkoutCart->key;
            } else {
                if (is_object($response)
                    && property_exists($response, 'id')
                    && property_exists($response, 'merchantId')
                    && property_exists($response, 'status')
                    && (isset($response->id) && isset($response->merchantId) && isset($response->status))
                ) {
                    return $response;
                } else {
                    if (is_object($response)
                        && property_exists($response, 'orderStatus')
                    ) {
                        return $response;
                    } else {
                        if (is_object($response)
                            && property_exists($response, 'code')
                            && (isset($response->message) || isset($response->type))
                        ) {
                            throw new \Exception(__(
                                "Ecster Checkout: %1",
                                $response->code . " " . (isset($response->message) ? $response->message : (isset($response->type) ? $response->type : ""))
                            ));
                        } else {
                            if (is_object($response)
                                && property_exists($response, 'error')
                            ) {
                                throw new \Exception(__(
                                    "Ecster Checkout: %1",
                                    $response->error . " " . $response->message
                                ));
                            } else {
                                throw new \Exception(__("Ecster Checkout: Object Error: " . $responseJson));
                            }
                        }
                    }
                }
            }
        } else {
            throw new \Exception(__("Ecster Checkout: Json Error"));
        }
    }

    public function initCart($quote)
    {
        if (is_null($quote->getEcsterCartKey())) {
            return $this->_createCart($quote);
        }
    }

    public function updateCart($quote)
    {
        if (!is_null($quote->getEcsterCartKey())) {
            return $this->_updateCart($quote, $quote->getEcsterCartKey());
        }
    }

    public function cartProcess($quote)
    {
        if (is_null($quote->getEcsterCartKey())) {
            return $this->_createCart($quote);
        } else {
            return $this->_updateCart($quote, $quote->getEcsterCartKey());
        }
    }

    public function getOrder($ecsterOrderId)
    {
        return $this->_getOrderFromEcster($ecsterOrderId);
    }

    public function updateOrderReference($ecsterOrderId, $orderReference)
    {
        return $this->_updateOrderReference($ecsterOrderId, $orderReference);
    }
}
