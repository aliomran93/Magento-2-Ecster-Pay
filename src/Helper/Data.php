<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Helper;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Framework\App\Config\Initial as InitialConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\Country;
use GuzzleHttp\Exception\BadResponseException;
use Evalent\EcsterPay\Model\TransactionHistoryFactory;

class Data extends PaymentHelper
{

    // Some methods in ecster need to be invoiced directly and not send to ecster as they do not support synchronous transaction flow
    const NON_SYNC_TRANSACTION_METHODS = [
        "SWISH"
    ];

    const XML_PATH_ECSTER_PAYMENT_METHODS = 'payment/ecsterpay/';

    const OEN_ORDER_STATUSES = [
        "READY" => "order_status_ready",
        "PENDING_PAYMENT" => "order_status_pending_payment",
        "PENDING_DECISION" => "order_status_pending_decision",
        "PENDING_SIGNATURE" => "order_status_pending_signature",
        "PENDING_PROCESSING" => "order_status_pending_processing",
        "DENIED" => "order_status_denied",
        "FAILED" => "order_status_failed",
        "ABORTED" => "order_status_aborted",
        "PARTIALLY_DELIVERED" => "order_status_partially_delivered",
        "FULLY_DELIVERED" => "order_status_fully_delivered",
        "ANNULLED" => "order_status_anulled",
        "EXPIRED" => "order_status_expired",
        "BLOCKED" => "order_status_blocked",
        "MANUAL_PROCESSING" => "order_status_manual_processing",
    ];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Model\Country
     */
    protected $country;

    /**
     * @var \Evalent\EcsterPay\Model\TransactionHistoryFactory
     */
    protected $transactionHistoryFactory;


    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        PaymentMethodFactory $paymentMethodFactory,
        Emulation $appEmulation,
        PaymentConfig $paymentConfig,
        InitialConfig $initialConfig,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Country $country,
        TransactionHistoryFactory $transactionHistoryFactory
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->country = $country;
        $this->transactionHistoryFactory = $transactionHistoryFactory;

        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );
    }

    /**
     * @param string $jsonData
     * @return bool
     */
    public function isValidJson($jsonData)
    {
        json_decode($jsonData);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function ecsterFormatPrice($value)
    {
        return number_format($value, 2, "", "");
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return bool
     */
    public function isEnabled($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'active', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getApiKey($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'api_key', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getMerchantKey($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'merchant_key', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTestModeMessage($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return __('Test mode');
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getTransactionMode($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'mode', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getPurchaseType($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'purchase_type', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getPreselectedPurchaseType($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'preselected_purchase_type', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getTermsPageContent($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'shop_terms_page_content', $scopeType, $storeId);
    }

    /**
     * @param string   $storeId
     * @param null|string   $storeId
     * @param string $scopeType
     *
     * @return mixed
     */
    public function getOenStatus($oenStatus, $storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        if (!isset(self::OEN_ORDER_STATUSES[$oenStatus])) {
            return null;
        }
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . self::OEN_ORDER_STATUSES[$oenStatus], $scopeType, $storeId);
    }


    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getShowCart($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'display_cart', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getShowDiscount($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'display_discount', $scopeType, $storeId);
    }


    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getShowDeliveryMethods($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'display_delivery_methods', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return mixed
     */
    public function getDefaultCountry($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'default_country', $scopeType, $storeId);
    }

    public function getDefaultShippingMethod(
        $countryId = null,
        $storeId = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ) {
        return null;
    }

    /**
     * @param $quote
     *
     * @return array
     */
    public function getSingleShippingMethod($quote)
    {
        if (count($quote->getShippingAddress()->getAllShippingRates()) == 1) {
            $rates = $quote->getShippingAddress()->getAllShippingRates();

            return [
                'amount' => $rates[0]->getPrice(),
                'base_amount' => $rates[0]->getPrice(),
                'available' => true,
                'carrier_code' => $rates[0]->getCarrier(),
                'carrier_title' => $rates[0]->getCarrierTitle(),
                'method_code' => $rates[0]->getMethod(),
                'method_title' => $rates[0]->getMethodTitle(),
                'price_excl_tax' => $rates[0]->getPrice(),
                'price_incl_tax' => $rates[0]->getPrice(),
            ];
        }
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return array
     */
    public function getAllowedCountries($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $allowspecific = $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'allowspecific', $scopeType, $storeId);

        if (!(bool)$allowspecific) {
            return [];
        }

        return explode(
            ",",
            $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'specificcountry', $scopeType, $storeId)
        );
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function isMultipleCountry($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return !empty($this->getAllowedCountries($storeId, $scopeType));
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function isDefinedTermsPageContent($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isEnabled($storeId, $scopeType)
        && is_null($this->getTermsPageContent($storeId, $scopeType))
            ? true
            : false;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getNotDefinedTermsPageContentNotification()
    {
        return __('You must define the Shopping Terms Url Content');
    }

    public function showPaymentResult($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return false;
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return mixed
     */
    public function getTermsBlockId($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_ECSTER_PAYMENT_METHODS . 'shop_terms_page_content', $scopeType, $storeId);
    }

    /**
     * @param $countryId
     *
     * @return string
     */
    public function getCountryName($countryId)
    {
        return $this->country->load($countryId)->getName();
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getApplyDiscountMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag('tax/calculation/apply_after_discount', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return mixed
     */
    public function getShippingTaxId($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue('tax/classes/shipping_tax_class', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getCatalogTaxCalculationMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag('tax/calculation/price_includes_tax', $scopeType, $storeId);
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getShippingTaxCalculationMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag('tax/calculation/shipping_includes_tax', $scopeType, $storeId);
    }

    public function getDataWithGuzzle($baseUri, $url, $header, $method = "POST", $params = [])
    {
        try {
            $ecsterApiClient = new \GuzzleHttp\Client(
                ['base_uri' => $baseUri]
            );
            $response = $ecsterApiClient->request(
                $method,
                $url,
                [
                    'headers' => $header,
                    'body' => isset($params['body']) ? $params['body'] : "",
                    'query' => isset($params['query']) ? $params['query'] : ""
                ]
            );

            return $response->getBody()->getContents();

        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();

            return $response->getBody()->getContents();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * @param null|string   $storeId
     * @param string        $scopeType
     *
     * @return bool
     */
    public function getAutoInvocie($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ECSTER_PAYMENT_METHODS . "auto_invoice", $scopeType, $storeId);
    }

    public function addTransactionHistory($data)
    {
        try {
            $transactionHistoryFactory = $this->transactionHistoryFactory->create();
            $transactionHistoryFactory->addData($data)->save();

            return $transactionHistoryFactory;
        } catch (\Exception $e) {
            return;
        }
    }
}
