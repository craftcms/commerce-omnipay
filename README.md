Omnipay payment gateway support for Craft Commerce
=======================

## Events

### The `beforeGatewayRequestSend` event

This event is used if you need to modify a gateway request or add some data to it before it's being prepared by the gateway object.

### The `afterCreateItemBag` event

This event is useful if you want to perform some additional actions when items are rounded up for an Order.

### The `beforeSendPaymentRequest` event

This event is fired right before sending a request to the payment gateway and gives you one last chance to modify it.