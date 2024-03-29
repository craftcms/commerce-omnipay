<?php

namespace craft\commerce\omnipay\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Class ItemBagEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class ItemBagEvent extends Event
{
    /**
     * @var Order The order
     */
    public Order $order;

    /**
     * @var mixed The item bag
     */
    public mixed $items;
}
