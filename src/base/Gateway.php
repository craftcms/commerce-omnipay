<?php

namespace craft\commerce\omnipay\base;

use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\elements\Order;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\ItemBag;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
abstract class Gateway extends BaseGateway
{
    /**
     * @var AbstractGateway
     */
    private $_gateway;

    /**
     * @param Order $order
     *
     * @return ItemBag
     */
    public function createItemBag(Order $order): ItemBag
    {
        if (!$this->canSendCartInfo) {
            return null;
        }

        $items = $this->generateItemList($order);
        $itemBagClassName = $this->getItemBagClassName();

        return new $itemBagClassName($items);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @return AbstractGateway
     */
    protected function gateway(): AbstractGateway
    {
        if ($this->_gateway !== null) {
            return $this->_gateway;
        }

        return $this->_gateway = $this->createGateway();
    }

    /**
     * Return the class name used for item bags by this gateway.
     *
     * @return string
     */
    protected function getItemBagClassName(): string {
        return ItemBag::class;
    }
}
