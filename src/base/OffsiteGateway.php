<?php

namespace craft\commerce\omnipay\base;

use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\records\Transaction as TransactionRecord;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
abstract class OffsiteGateway extends Gateway
{
    use OffsiteGatewayTrait;

    /**
     * @inheritdoc
     */
    protected function getRequest(Transaction $transaction, BasePaymentForm $form = null)
    {
        // For authorize and capture we're referring to a transaction that already took place so no card or item shenanigans.
        if (in_array($transaction->type, [TransactionRecord::TYPE_REFUND, TransactionRecord::TYPE_CAPTURE], false)) {
            $request = $this->createPaymentRequest($transaction);
        } else {
            $order = $transaction->getOrder();
            $itemBag = $this->getItemBagForOrder($order);
            $request = $this->createPaymentRequest($transaction, null, $itemBag);
            $this->populateRequest($request, $form);
        }

        return $request;
    }
}
