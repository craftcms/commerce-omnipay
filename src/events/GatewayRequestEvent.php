<?php

namespace craft\commerce\omnipay\events;

use craft\commerce\models\Transaction;
use yii\base\Event;

/**
 * Class GatewayRequestEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class GatewayRequestEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string Transaction type
     */
    public $type;

    /**
     * @var mixed The request
     */
    public $request;

    /**
     * @var Transaction The transaction being sent
     */
    public $transaction;
}
