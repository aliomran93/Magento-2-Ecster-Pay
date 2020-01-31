<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Magento\Config\Model\Config;

class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    protected $_backendConfig;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    protected function _getHeaderTitleHtml($element)
    {

        $html = '<div class="config-heading" >';

        $groupConfig = $element->getGroup();

        $disabledAttributeString = $this->_isPaymentEnabled($element) ? '' : ' disabled="disabled"';
        $disabledClassString = $this->_isPaymentEnabled($element) ? '' : ' disabled';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"' .
            $disabledAttributeString .
            ' class="button action-configure' .
            (empty($groupConfig['ecsterpay_ec_separate']) ? '' : ' ecsterpay-ec-separate') .
            $disabledClassString .
            '" id="' .
            $htmlId .
            '-head" onclick="ecsterpayToggleSolution.call(this, \'' .
            $htmlId .
            "', '" .
            $this->getUrl(
                'adminhtml/*/state'
            ) . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        if (!empty($groupConfig['more_url'])) {
            $html .= '<a class="link-more" href="' . $groupConfig['more_url'] . '" target="_blank">' . __(
                    'Learn More'
                ) . '</a>';
        }

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    protected function _isCollapseState($element)
    {
        return false;
    }

    protected function _getFrontendClass($element)
    {
        $enabledString = $this->_isPaymentEnabled($element) ? ' enabled' : '';

        return parent::_getFrontendClass($element) . ' with-button' . $enabledString;
    }

    protected function _isPaymentEnabled($element)
    {
        $groupConfig = $element->getGroup();
        $activityPaths = isset($groupConfig['activity_path']) ? $groupConfig['activity_path'] : [];

        if (!is_array($activityPaths)) {
            $activityPaths = [$activityPaths];
        }

        $isPaymentEnabled = false;
        foreach ($activityPaths as $activityPath) {
            $isPaymentEnabled = $isPaymentEnabled
                || (bool)(string)$this->_backendConfig->getConfigDataValue($activityPath);
        }

        return $isPaymentEnabled;
    }

    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.ecsterpayToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    $$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
