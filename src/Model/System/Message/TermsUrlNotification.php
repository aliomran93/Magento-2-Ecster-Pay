<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\System\Message;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class TermsUrlNotification implements \Magento\Framework\Notification\MessageInterface
{
    const MESSAGE_IDENTITY = 'terms_url_notification';

    protected $_helper;
    protected $urlBuilder;

    public function __construct(
        EcsterPayHelper $ecsterpayHelper,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->_helper = $ecsterpayHelper;
        $this->urlBuilder = $urlBuilder;
    }

    public function getIdentity()
    {
        return md5(self::MESSAGE_IDENTITY);
    }

    public function isDisplayed()
    {
        return $this->_helper->isDefinedTermsPageContent();
    }

    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/payment');

        return __(
            'Ecster payment integration requires the definition of the contents of terms condition. You can define it by clicking the <a href="%1">link.</a>',
            $url
        );
    }

    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}