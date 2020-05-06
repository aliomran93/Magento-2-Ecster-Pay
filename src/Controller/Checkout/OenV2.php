<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Class OenV2
 * @package Evalent\EcsterPay\Controller\Checkout
 *
 * Magento 2.3 added CsrfAwareActionInterface, and that has to be used for all controllers supporting
 * post requests.
 * But it does not exist in Magento 2.2, so this is just a wrapper that adds required methods.
 */
class OenV2 extends OenV1 implements CsrfAwareActionInterface
{
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}