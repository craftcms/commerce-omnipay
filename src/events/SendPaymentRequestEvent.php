<?php

namespace craft\commerce\omnipay\events;

use yii\base\Event;

/**
 * Class SendPaymentRequestEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class SendPaymentRequestEvent extends Event
{
    // Properties
    // ==========================================================================

    /**
     * @var mixed Request data
     */
    public $requestData;

    /**
     * @var mixed Modified request data
     */
    public $modifiedRequestData;
}
