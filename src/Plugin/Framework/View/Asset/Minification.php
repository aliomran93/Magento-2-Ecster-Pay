<?php

declare(strict_types=1);

namespace Evalent\EcsterPay\Plugin\Framework\View\Asset;

class Minification
{

    /**
     * We add the minification excludes to make sure the module works in both Magento 2.3 and 2.2
     *
     * @param \Magento\Framework\View\Asset\Minification $subject
     * @param callable                                   $proceed
     * @param                                            $contentType
     *
     * @return mixed
     */
    public function aroundGetExcludes(\Magento\Framework\View\Asset\Minification $subject, callable $proceed, $contentType)
    {
        if ($contentType != 'js') {
            return $proceed($contentType);
        }
        $result = $proceed($contentType);
        $result[] = "/pay/integration/";
        return $result;
    }
}