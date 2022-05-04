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
    /**
     * @var mixed Request data
     */
    public mixed $requestData = null;

    /**
     * @var mixed Modified request data
     */
    public mixed $modifiedRequestData = null;
}
