<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Quote\Model;

use Magento\Quote\Model\QuoteRepository;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class CouponManagement extends \Magento\Quote\Model\CouponManagement
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

    public function set(
        $cartId,
        $couponCode
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_ecsterApi = $objectManager->get(\Evalent\EcsterPay\Model\Api\Ecster::class);

        $quote = $this->quoteRepository->getActive($cartId);

        try {
            $result = parent::set($cartId, $couponCode);

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return $ecsterCartKey;
            }

            return $result;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        }
    }

    public function remove(
        $cartId
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_ecsterApi = $objectManager->get(\Evalent\EcsterPay\Model\Api\Ecster::class);

        $quote = $this->quoteRepository->getActive($cartId);

        try {
            $result = parent::remove($cartId);

            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return $ecsterCartKey;
            }

            return $result;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        } catch (\Magento\Framework\Exception\CouldNotDeleteException $e) {
            if ($ecsterCartKey = $this->_ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        }
    }
}