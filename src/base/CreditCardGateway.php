<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\controllers\PaymentsController;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\helpers\Html;
use craft\web\View;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
abstract class CreditCardGateway extends Gateway
{
    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params): ?string
    {
        $defaults = [
            'paymentForm' => $this->getPaymentFormModel()
        ];

        $params = array_merge($defaults, $params);

        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = Craft::$app->getView()->renderTemplate('commerce/_components/gateways/_creditCardFields', $params);
        $html = Html::namespaceInputs($html, sprintf('%s[%s]', PaymentsController::PAYMENT_FORM_NAMESPACE, $this->handle));
        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new CreditCardPaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function populateRequest(array &$request, BasePaymentForm $form = null): void
    {
        if ($form && $form->hasProperty('token') && $form->token) {
            $request['token'] = $form->token;
        }
    }
}
