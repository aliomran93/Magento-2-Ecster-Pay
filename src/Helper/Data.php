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
use GuzzleHttp\Exception\ClientException;
use Evalent\EcsterPay\Model\TransactionHistoryFactory;

class Data extends PaymentHelper
{
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_country;
    protected $_transactionHistoryFactory;

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
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_country = $country;
        $this->_transactionHistoryFactory = $transactionHistoryFactory;

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

    public function ecsterFormatPrice($value)
    {
        return number_format($value, 2, "", "");
    }

    public function isEnabled($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('payment/ecsterpay/active', $scopeType, $storeId);
    }

    public function getApiKey($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/api_key', $scopeType, $storeId);
    }

    public function getMerchantKey($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/merchant_key', $scopeType, $storeId);
    }

    public function getTestModeMessage($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return __('Test mode');
    }

    public function getTransactionMode($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/mode', $scopeType, $storeId);
    }

    public function getPurchaseType($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/purchase_type', $scopeType, $storeId);
    }

    public function getPreselectedPurchaseType($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/preselected_purchase_type', $scopeType, $storeId);
    }

    public function getTermsPageContent($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/shop_terms_page_content', $scopeType, $storeId);
    }

    public function getShowCart($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('payment/ecsterpay/display_cart', $scopeType, $storeId);
    }

    public function getShowDeliveryMethods($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('payment/ecsterpay/display_delivery_methods', $scopeType, $storeId);
    }

    public function getDefaultCountry($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/default_country', $scopeType, $storeId);
    }

    public function getDefaultShippingMethod(
        $countryId = null,
        $storeId = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ) {
        return null;
    }

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

    public function getAllowedCountries($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $allowspecific = $this->_scopeConfig->getValue('payment/ecsterpay/allowspecific', $scopeType, $storeId);

        if (!(bool)$allowspecific) {
            return [];
        }

        return explode(
            ",",
            $this->_scopeConfig->getValue('payment/ecsterpay/specificcountry', $scopeType, $storeId)
        );
    }

    public function isMultipleCountry($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return !empty($this->getAllowedCountries($storeId, $scopeType));
    }

    public function isDefinedTermsPageContent($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->isEnabled($storeId, $scopeType)
        && is_null($this->getTermsPageContent($storeId, $scopeType))
            ? true
            : false;
    }

    public function getNotDefinedTermsPageContentNotification()
    {
        return __('You must define the Shopping Terms Url Content');
    }

    public function showPaymentResult($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return true;
    }

    public function getTermsBlockId($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('payment/ecsterpay/shop_terms_page_content', $scopeType, $storeId);
    }

    public function getAssignedOrderStatus($status, $storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $path = "order_status_" . $status;

        return $this->_scopeConfig->getValue('payment/ecsterpay/' . $path, $scopeType, $storeId);
    }

    public function getCountryName($countryId)
    {
        return $this->_country->load($countryId)->getName();
    }

    public function getApplyDiscountMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('tax/calculation/apply_after_discount', $scopeType, $storeId);
    }

    public function getShippingTaxId($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue('tax/classes/shipping_tax_class', $scopeType, $storeId);
    }

    public function getCatalogTaxCalculationMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('tax/calculation/price_includes_tax', $scopeType, $storeId);
    }

    public function getShippingTaxCalculationMethod($storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return (bool)$this->_scopeConfig->getValue('tax/calculation/shipping_includes_tax', $scopeType, $storeId);
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

        } catch (ClientException $ex) {
            $response = $ex->getResponse();

            return $response->getBody()->getContents();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function addTransactionHistory($data)
    {
        try {
            $transactionHistoryFactory = $this->_transactionHistoryFactory->create();
            $transactionHistoryFactory->addData($data)->save();

            return $transactionHistoryFactory;
        } catch (\Exception $e) {
            return;
        }
    }
}