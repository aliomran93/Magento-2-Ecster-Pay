<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Override\Quote\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Evalent\EcsterPay\Model\Api\Ecster as EcsterApi;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class CouponManagement extends \Magento\Quote\Model\CouponManagement
{

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Evalent\EcsterPay\Model\Api\Ecster
     */
    protected $ecsterApi;

    /**
     * @var \Evalent\EcsterPay\Helper\Data
     */
    protected $ecsterpayHelper;


    /**
     * CouponManagement constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Evalent\EcsterPay\Model\Api\Ecster        $ecsterApi
     * @param \Evalent\EcsterPay\Helper\Data             $ecsterpayHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        EcsterApi $ecsterApi,
        EcsterPayHelper $ecsterpayHelper
    ) {
        parent::__construct($quoteRepository);
        $this->quoteRepository = $quoteRepository;
        $this->ecsterApi = $ecsterApi;
        $this->ecsterpayHelper = $ecsterpayHelper;
    }

    public function set(
        $cartId,
        $couponCode
    ) {
        if (!$this->ecsterpayHelper->isEnabled()) {
            return parent::set($cartId, $couponCode);
        }
        $quote = $this->quoteRepository->getActive($cartId);

        try {
            $result = parent::set($cartId, $couponCode);

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return $ecsterCartKey;
            }

            return $result;
        } catch (NoSuchEntityException $e) {
            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        } catch (CouldNotSaveException $e) {
            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        }
    }

    public function remove(
        $cartId
    ) {
        if (!$this->ecsterpayHelper->isEnabled()) {
            return parent::remove($cartId);
        }
        $quote = $this->quoteRepository->getActive($cartId);

        try {
            $result = parent::remove($cartId);

            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return $ecsterCartKey;
            }

            return $result;
        } catch (NoSuchEntityException $e) {
            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        } catch (CouldNotDeleteException $e) {
            if ($ecsterCartKey = $this->ecsterApi->cartProcess($quote)) {
                $quote->setData('ecster_cart_key', $ecsterCartKey)->save();

                return [$ecsterCartKey, $e->getMessage()];
            }

            return $e->getMessage();
        }
    }
}