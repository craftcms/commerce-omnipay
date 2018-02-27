<?php

namespace craft\commerce\omnipay\base;

use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\OffsitePaymentForm;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
abstract class OffsiteGateway extends Gateway
{
    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new OffsitePaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function populateRequest(array &$request, BasePaymentForm $paymentForm = null)
    {
    }
}
