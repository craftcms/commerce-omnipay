<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\OffsitePaymentForm;

/**
 * This is a trait to be used by offsite gateways
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2017, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 * @see       https://craftcommerce.com
 * @package   craft.commerce
 * @since     2.0
 */
trait OffsiteGatewayTrait
{
    /**
     * @inheritdoc
     */
    public function getPaymentFormModel()
    {
        return new OffsitePaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $defaults = [
            'gateway' => $this,
            'paymentForm' => $this->getPaymentFormModel()
        ];

        $params = array_merge($defaults, $params);

        return Craft::$app->getView()->renderTemplate('commerce/_components/gateways/common/creditCardPaymentForm', $params);
    }

    /**
     * @inheritdoc
     */
    public function populateRequest(array &$request, BasePaymentForm $paymentForm = null)
    {
    }
}
