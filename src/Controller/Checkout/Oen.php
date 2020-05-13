<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Checkout;

use Magento\Framework\App\RequestInterface;

/**
 * Magento 2.3 added CsrfAwareActionInterface, and that has to be used for all controllers supporting
 * post requests.
 * But it does not exist in Magento 2.2, so this is just a wrapper that adds required methods, and implements
 * the interface IF it exists.
 */

if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    class Oen extends AbstractOen implements \Magento\Framework\App\CsrfAwareActionInterface
    {
        public function createCsrfValidationException(RequestInterface $request): ?\Magento\Framework\App\Request\InvalidRequestException
        {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool
        {
            return true;
        }
    }
} else {
    class Oen extends AbstractOen
    {
    }
}
