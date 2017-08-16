<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\CreditCardPaymentForm;

/**
 * CreditCardGatewayTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  1.0
 */
trait CreditCardGatewayTrait
{
    /**
     * @inheritdoc
     */
    public function getPaymentFormModel()
    {
        return new CreditCardPaymentForm();
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
        if ($paymentForm->hasProperty('token')) {
            $request['token'] = $paymentForm->token;
        }
    }
}
