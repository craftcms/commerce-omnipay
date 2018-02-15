Omnipay payment gateway support for Craft Commerce
=======================

## Events

### The `beforeGatewayRequestSend` event

This event gives you a chance to do something before a request is being sent to the gateway. If you set the `isValid` property of the event to `true`, the request will be cancelled.

```php
use craft\commerce\omnipay\events\GatewayRequestEvent
use craft\commerce\omnipay\base\Gateway as Gateway;
use yii\base\Event;

// ...

Event::on(Gateway::class, Gateway::EVENT_BEFORE_GATEWAY_REQUEST_SEND, function(GatewayRequestEvent $e) {
    if ($e->request['someKey'] == 'someValue') {
        // Prevent the request from going through
        $e->isValid = false;
    }
});
```

### The `afterCreateItemBag` event

This event is useful if you want to perform some additional actions when items are rounded up for an Order or add a line item as the order is being sent to gateway.

```php
use craft\commerce\omnipay\events\ItemBagEvent
use craft\commerce\omnipay\base\Gateway as Gateway;
use yii\base\Event;

// ...

Event::on(Gateway::class, Gateway::EVENT_AFTER_CREATE_ITEM_BAG, function(ItemBagEvent $e) {
      // Add a tax line item for 0% VAT
      $e->items[] = ['name' => 'VAT', 'price' => 0.00];
});
```

### The `beforeSendPaymentRequest` event

This event gives plugins a chance to modify the request data as it's being dispatched. To change the request data, you must use the `modifiedRequestData` property.

```php
use craft\commerce\omnipay\events\SendPaymentRequestEvent
use craft\commerce\omnipay\base\Gateway as Gateway;
use yii\base\Event;

// ...

Event::on(Gateway::class, Gateway::EVENT_BEFORE_SEND_PAYMENT_REQUEST, function(SendPaymentRequestEvent $e) {
    $e->modifiedRequestData = $e->requestData;

    // Change something.
    $e->modifiedRequestData['endpoint'] = 'myCustomGatewayEndpoint';
});
```