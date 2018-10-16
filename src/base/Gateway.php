<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\Purchasable;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\errors\PaymentException;
use craft\commerce\helpers\Currency;
use craft\commerce\models\LineItem;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\commerce\omnipay\events\GatewayRequestEvent;
use craft\commerce\omnipay\events\ItemBagEvent;
use craft\commerce\omnipay\events\SendPaymentRequestEvent;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\helpers\UrlHelper;
use craft\web\Response as WebResponse;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\CreditCard;
use Omnipay\Common\ItemBag;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use yii\base\NotSupportedException;

/**
 * Class Gateway
 *
 * @property string $itemBagClassName
 * @property string $gatewayClassName
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
abstract class Gateway extends BaseGateway
{
    // Constants
    // =========================================================================
    /**
     * @event ItemBagEvent The event that is triggered after an item bag is created.
     *
     * This event is useful if you want to perform some additional actions when items are rounded up for an Order or add a line item as the order is being sent to gateway.
     *
     * ```php
     * use craft\commerce\omnipay\events\ItemBagEvent
     * use craft\commerce\omnipay\base\Gateway as Gateway;
     * use yii\base\Event;
     *
     * Event::on(Gateway::class, Gateway::EVENT_AFTER_CREATE_ITEM_BAG, function(ItemBagEvent $e) {
     *     // Add a tax line item for 0% VAT
     *     $e->items[] = ['name' => 'VAT', 'price' => 0.00];
     * });
     * ```
     */
    const EVENT_AFTER_CREATE_ITEM_BAG = 'afterCreateItemBag';

    /**
     * @event GatewayRequestEvent The event that is triggered before a gateway request is sent.
     *
     * This event gives you a chance to do something before a request is being sent to the gateway.
     *
     * ```php
     * use craft\commerce\omnipay\events\GatewayRequestEvent
     * use craft\commerce\omnipay\base\Gateway as Gateway;
     * use yii\base\Event;
     *
     * Event::on(Gateway::class, Gateway::EVENT_BEFORE_GATEWAY_REQUEST_SEND, function(GatewayRequestEvent $e) {
     *     if ($e->request['someKey'] === 'someValue') {
     *         // do something
     *     }
     * });
     * ```
     */
    const EVENT_BEFORE_GATEWAY_REQUEST_SEND = 'beforeGatewayRequestSend';

    /**
     * @event SendPaymentRequestEvent The event that is triggered right before a payment request is being sent.
     *
     * This event gives plugins a chance to modify the request data as it's being dispatched. To change the request data, you must use the `modifiedRequestData` property.
     *
     * ```php
     * use craft\commerce\omnipay\events\SendPaymentRequestEvent
     * use craft\commerce\omnipay\base\Gateway as Gateway;
     * use yii\base\Event;
     *
     * Event::on(Gateway::class, Gateway::EVENT_BEFORE_SEND_PAYMENT_REQUEST, function(SendPaymentRequestEvent $e) {
     *     // Change something.
     *     $e->modifiedRequestData = $e->requestData;
     *     $e->modifiedRequestData['endpoint'] = 'myCustomGatewayEndpoint';
     * });
     * ```
     */
    const EVENT_BEFORE_SEND_PAYMENT_REQUEST = 'beforeSendPaymentRequest';

    // Properties
    // =========================================================================

    /**
     * @var bool Whether cart information should be sent to the payment gateway
     */
    public $sendCartInfo = false;

    /**
     * @var AbstractGateway
     */
    private $_gateway;

    // Public methods
    // =========================================================================
    /**
     * @inheritdocs
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        if (!$this->supportsAuthorize()) {
            throw new NotSupportedException(Craft::t('commerce', 'Authorizing is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction, $form);
        $authorizeRequest = $this->prepareAuthorizeRequest($request);

        return $this->performRequest($authorizeRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        if (!$this->supportsCapture()) {
            throw new NotSupportedException(Craft::t('commerce', 'Capturing is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction);
        $captureRequest = $this->prepareCaptureRequest($request, $reference);

        return $this->performRequest($captureRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        if (!$this->supportsCompleteAuthorize()) {
            throw new NotSupportedException(Craft::t('commerce', 'Completing authorization is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction);
        $completeRequest = $this->prepareCompleteAuthorizeRequest($request);

        return $this->performRequest($completeRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        if (!$this->supportsCompletePurchase()) {
            throw new NotSupportedException(Craft::t('commerce', 'Completing purchase is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction);
        $completeRequest = $this->prepareCompletePurchaseRequest($request);

        return $this->performRequest($completeRequest, $transaction);
    }

    /**
     * Create a card object using the payment form and the optional order
     *
     * @param BasePaymentForm $paymentForm
     * @param Order           $order
     *
     * @return CreditCard
     */
    public function createCard(BasePaymentForm $paymentForm, Order $order = null): CreditCard {

        $card = new CreditCard;

        if ($paymentForm instanceof CreditCardPaymentForm) {
            $this->populateCard($card, $paymentForm);
        }

        if ($order) {
            if ($billingAddress = $order->getBillingAddress()) {
                // Set top level names to the billing names
                $card->setFirstName($billingAddress->firstName);
                $card->setLastName($billingAddress->lastName);

                $card->setBillingFirstName($billingAddress->firstName);
                $card->setBillingLastName($billingAddress->lastName);
                $card->setBillingAddress1($billingAddress->address1);
                $card->setBillingAddress2($billingAddress->address2);
                $card->setBillingCity($billingAddress->city);
                $card->setBillingPostcode($billingAddress->zipCode);
                if ($billingAddress->getCountry()) {
                    $card->setBillingCountry($billingAddress->getCountry()->iso);
                }
                if ($billingAddress->getState()) {
                    $state = $billingAddress->getState()->abbreviation ?: $billingAddress->getState()->name;
                    $card->setBillingState($state);
                }
                $card->setBillingPhone($billingAddress->phone);
                $card->setBillingCompany($billingAddress->businessName);
                $card->setCompany($billingAddress->businessName);
            }

            if ($shippingAddress = $order->getShippingAddress()) {
                $card->setShippingFirstName($shippingAddress->firstName);
                $card->setShippingLastName($shippingAddress->lastName);
                $card->setShippingAddress1($shippingAddress->address1);
                $card->setShippingAddress2($shippingAddress->address2);
                $card->setShippingCity($shippingAddress->city);
                $card->setShippingPostcode($shippingAddress->zipCode);

                if ($shippingAddress->getCountry()) {
                    $card->setShippingCountry($shippingAddress->getCountry()->iso);
                }

                if ($shippingAddress->getState()) {
                    $state = $shippingAddress->getState()->abbreviation ?: $shippingAddress->getState()->name;
                    $card->setShippingState($state);
                }

                $card->setShippingPhone($shippingAddress->phone);
                $card->setShippingCompany($shippingAddress->businessName);
            }

            $card->setEmail($order->getEmail());
        }

        return $card;
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        if (!$this->supportsPaymentSources()) {
            throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
        }

        $cart = Commerce::getInstance()->getCarts()->getCart();

        if (!$address = $cart->getBillingAddress()) {
            $customer = Commerce::getInstance()->getCustomers()->getCustomerByUserId($userId);

            if (!$customer || !($address = $customer->getPrimaryBillingAddress())) {
                throw new NotSupportedException(Craft::t('commerce', 'You need a billing address to save a payment source.'));
            }

            $cart->setBillingAddress($address);
            $cart->billingAddressId = $address->id;
        }

        $card = $this->createCard($sourceData, $cart);
        $request = [
            'card' => $card,
            'currency' => $cart->paymentCurrency,
        ];

        $this->populateRequest($request, $sourceData);
        $createCardRequest = $this->gateway()->createCard($request);

        $response = $this->sendRequest($createCardRequest);

        $paymentSource = new PaymentSource([
            'userId' => $userId,
            'gatewayId' => $this->id,
            'token' => $this->extractCardReference($response),
            'response' => $response->getData(),
            'description' => $this->extractPaymentSourceDescription($response)
        ]);

        return $paymentSource;
    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource($token): bool
    {
        if (!$this->supportsPaymentSources()) {
            throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
        }

        // Some gateways support creating but don't support deleting. Assume deleted, then.
        if (!$this->gateway()->supportsDeleteCard()) {
            return true;
        }

        $deleteCardRequest = $this->gateway()->deleteCard(['cardReference' => $token]);
        $response = $this->sendRequest($deleteCardRequest);

        return $response->isSuccessful();
    }

    /**
     * Populate a credit card from the paymnent form.
     *
     * @param CreditCard            $card        The credit card to populate.
     * @param CreditCardPaymentForm $paymentForm The payment form.
     *                                    
     * @return void
     */
    public function populateCard($card, CreditCardPaymentForm $paymentForm)
    {
        if (!$card instanceof CreditCard) {
            return;
        }

        $card->setFirstName($paymentForm->firstName);
        $card->setLastName($paymentForm->lastName);
        $card->setNumber($paymentForm->number);
        $card->setExpiryMonth($paymentForm->month);
        $card->setExpiryYear($paymentForm->year);
        $card->setCvv($paymentForm->cvv);
    }

    /**
     * Populate the request array before it's dispatched.
     *
     * @param array $request Parameter array by reference.
     * @param BasePaymentForm $form
     *
     * @return void
     */
    abstract public function populateRequest(array &$request, BasePaymentForm $form = null);

    /**
     * @inheritdoc
     */
    public function processWebHook(): WebResponse
    {
        return null;
    }

    /**
     * @inheritdocs
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        if (!$this->supportsPurchase()) {
            throw new NotSupportedException(Craft::t('commerce', 'Purchasing is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction, $form);
        $purchaseRequest = $this->preparePurchaseRequest($request);

        return $this->performRequest($purchaseRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        if (!$this->supportsRefund()) {
            throw new NotSupportedException(Craft::t('commerce', 'Refunding is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction);
        $refundRequest = $this->prepareRefundRequest($request, $transaction->reference);

        return $this->performRequest($refundRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return $this->gateway()->supportsAuthorize();
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return $this->gateway()->supportsCapture();
    }

    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return $this->gateway()->supportsCompleteAuthorize();
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return $this->gateway()->supportsCompletePurchase();
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        // All of the currently implemented Omnipay gateways support partial refunds.
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return $this->gateway()->supportsCreateCard();
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return $this->gateway()->supportsPurchase();
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return $this->gateway()->supportsRefund();
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns an Omnipay gateway instance based on the stored settings.
     *
     * @return AbstractGateway The actual gateway.
     */
    abstract protected function createGateway(): AbstractGateway;

    /**
     * Create a gateway specific item bag for the order.
     *
     * @param Order $order The order.
     *
     * @return ItemBag|null
     */
    protected function createItemBagForOrder(Order $order)
    {
        if (!$this->sendCartInfo) {
            return null;
        }

        $items = $this->getItemListForOrder($order);
        $itemBagClassName = $this->getItemBagClassName();

        return new $itemBagClassName($items);
    }

    /**
     * Create the parameters for a payment request based on a trasaction and optional card and item list.
     *
     * @param Transaction $transaction The transaction that is basis for this request.
     * @param CreditCard  $card        The credit card being used
     * @param ItemBag     $itemBag     The item list.
     *
     * @return array
     * @throws \yii\base\Exception
     */
    protected function createPaymentRequest(Transaction $transaction, $card = null, $itemBag = null): array
    {
        $params = ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash];

        $request = [
            'amount' => $transaction->paymentAmount,
            'currency' => $transaction->paymentCurrency,
            'transactionId' => $transaction->hash,
            'description' => Craft::t('commerce', 'Order').' #'.$transaction->orderId,
            'clientIp' => Craft::$app->getRequest()->userIP,
            'transactionReference' => $transaction->hash,
            'returnUrl' => UrlHelper::actionUrl('commerce/payments/complete-payment', $params),
            'cancelUrl' => UrlHelper::siteUrl($transaction->order->cancelUrl),
        ];

        // Set the webhook url.
        if ($this->supportsWebhooks()) {
            $request['notifyUrl'] = $this->getWebhookUrl($params);
        }

        // Do not use IPv6 loopback
        if ($request['clientIp'] ===  '::1') {
            $request['clientIp'] = '127.0.0.1';
        }

        // custom gateways may wish to access the order directly
        $request['order'] = $transaction->order;
        $request['orderId'] = $transaction->order->id;

        // Stripe only params
        $request['receiptEmail'] = $transaction->order->email;

        // Paypal only params
        $request['noShipping'] = 1;
        $request['allowNote'] = 0;
        $request['addressOverride'] = 1;
        $request['buttonSource'] = 'ccommerce_SP';

        if ($card) {
            $request['card'] = $card;
        }

        if ($itemBag) {
            $request['items'] = $itemBag;
        }

        return $request;
    }


    /**
     * Prepare a request for execution by transaction and a populated payment form.
     *
     * @param Transaction     $transaction
     * @param BasePaymentForm $form        Optional for capture/refund requests.
     *
     * @return mixed
     * @throws \yii\base\Exception
     */
    protected function createRequest(Transaction $transaction, BasePaymentForm $form = null)
    {
        // For authorize and capture we're referring to a transaction that already took place so no card or item shenanigans.
        if (in_array($transaction->type, [TransactionRecord::TYPE_REFUND, TransactionRecord::TYPE_CAPTURE], false)) {
            $request = $this->createPaymentRequest($transaction);
        } else {
            $order = $transaction->getOrder();

            $card = null;

            if ($form) {
                $card = $this->createCard($form, $order);
            }

            $itemBag = $this->getItemBagForOrder($order);

            $request = $this->createPaymentRequest($transaction, $card, $itemBag);
            $this->populateRequest($request, $form);
        }

        return $request;
    }

    /**
     * Extract a card reference from a response
     *
     * @param ResponseInterface $response The response to use
     *
     * @return string
     * @throws PaymentException on failure
     */
    protected function extractCardReference(ResponseInterface $response): string
    {
        if (!$response->isSuccessful()) {
            throw new PaymentException($response->getMessage());
        }

        return (string) $response->getTransactionReference();
    }

    /**
     * Extract a payment source description from a response.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
     protected function extractPaymentSourceDescription(ResponseInterface $response): string
     {
         return 'Payment source';
     }

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
     * Return the gateway class name.
     *
     * @return string|null
     */
    abstract protected function getGatewayClassName();

    /**
     * Return the class name used for item bags by this gateway.
     *
     * @return string
     */
    protected function getItemBagClassName(): string {
        return ItemBag::class;
    }

    /**
     * Get the item bag for the order.
     *
     * @param Order $order
     *
     * @return mixed
     */
    protected function getItemBagForOrder(Order $order)
    {
        $itemBag = $this->createItemBagForOrder($order);

        $event = new ItemBagEvent([
            'items' => $itemBag,
            'order' => $order
        ]);
        $this->trigger(self::EVENT_AFTER_CREATE_ITEM_BAG, $event);

        return $event->items;
    }

    /**
     * Generate the item list for an Order.
     *
     * @param Order $order
     *
     * @return array
     */
    protected function getItemListForOrder(Order $order): array
    {
        $items = [];

        $priceCheck = 0;
        $count = -1;

        /** @var LineItem $item */
        foreach ($order->lineItems as $item) {
            $price = Currency::round($item->salePrice);
            // Can not accept zero amount items. See item (4) here:
            // https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECCustomizing/#setting-order-details-on-the-paypal-review-page

            if ($price !== 0) {
                $count++;
                /** @var Purchasable $purchasable */
                $purchasable = $item->getPurchasable();
                $defaultDescription = Craft::t('commerce', 'Item ID').' '.$item->id;
                $purchasableDescription = $purchasable ? $purchasable->getDescription() : $defaultDescription;
                $description = isset($item->snapshot['description']) ? $item->snapshot['description'] : $purchasableDescription;
                $description = empty($description) ? 'Item '.$count : $description;
                $items[] = [
                    'name' => $description,
                    'description' => $description,
                    'quantity' => $item->qty,
                    'price' => $price,
                ];

                $priceCheck += ($item->qty * $item->salePrice);
            }
        }

        $count = -1;

        /** @var OrderAdjustment $adjustment */
        foreach ($order->adjustments as $adjustment) {
            $price = Currency::round($adjustment->amount);

            // Do not include the 'included' adjustments, and do not send zero value items
            // See item (4) https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECCustomizing/#setting-order-details-on-the-paypal-review-page
            if (($adjustment->included == 0 || $adjustment->included == false) && $price !== 0) {
                $count++;
                $items[] = [
                    'name' => empty($adjustment->name) ? $adjustment->type." ".$count : $adjustment->name,
                    'description' => empty($adjustment->description) ? $adjustment->type.' '.$count : $adjustment->description,
                    'quantity' => 1,
                    'price' => $price,
                ];
                $priceCheck += $adjustment->amount;
            }
        }

        $priceCheck = Currency::round($priceCheck);
        $totalPrice = Currency::round($order->totalPrice);
        $same = (bool)($priceCheck === $totalPrice);

        if (!$same) {
            Craft::error('Item bag total price does not equal the orders totalPrice, some payment gateways will complain.', __METHOD__);
        }

        return $items;
    }

    /**
     * Perform a request and return the response.
     *
     * @param $request
     * @param $transaction
     *
     * @return RequestResponseInterface
     * @throws GatewayRequestCancelledException
     */
    protected function performRequest($request, $transaction): RequestResponseInterface
    {
        //raising event
        $event = new GatewayRequestEvent([
            'type' => $transaction->type,
            'request' => $request,
            'transaction' => $transaction
        ]);

        // Raise 'beforeGatewayRequestSend' event
        $this->trigger(self::EVENT_BEFORE_GATEWAY_REQUEST_SEND, $event);

        $response = $this->sendRequest($request);

        return $this->prepareResponse($response, $transaction);
    }

    /**
     * Prepare an authorization request from request data.
     *
     * @param array $request
     *
     * @return RequestInterface
     */
    protected function prepareAuthorizeRequest(array $request): RequestInterface
    {
        return $this->gateway()->authorize($request);
    }

    /**
     * Prepare a complete authorization request from request data.
     *
     * @param array $request
     *
     * @return RequestInterface
     */
    protected function prepareCompleteAuthorizeRequest($request): RequestInterface
    {
        return $this->gateway()->completeAuthorize($request);
    }

    /**
     * Prepare a complete purchase request from request data.
     *
     * @param array $request
     *
     * @return RequestInterface
     */
    protected function prepareCompletePurchaseRequest($request): RequestInterface
    {
        return $this->gateway()->completePurchase($request);
    }

    /**
     * Prepare a capture request from request data and reference of the transaction being captured.
     *
     * @param array  $request
     * @param string $reference
     *
     * @return RequestInterface
     */
    protected function prepareCaptureRequest($request, string $reference): RequestInterface
    {
        /** @var AbstractRequest $captureRequest */
        $captureRequest = $this->gateway()->capture($request);
        $captureRequest->setTransactionReference($reference);

        return $captureRequest;
    }

    /**
     * Prepare a purchase request from request data.
     *
     * @param array $request
     *
     * @return RequestInterface
     */
    protected function preparePurchaseRequest($request): RequestInterface
    {
        return $this->gateway()->purchase($request);
    }

    /**
     * Prepare a Commerce response from the gateway response and the transaction that was the base for the request.
     *
     * @param ResponseInterface $response
     * @param Transaction $transaction
     *
     * @return RequestResponseInterface
     */
    protected function prepareResponse(ResponseInterface $response, Transaction $transaction): RequestResponseInterface
    {
        /** @var AbstractResponse $response */
        return new RequestResponse($response, $transaction);
    }

    /**
     * Prepare a refund request from request data and reference of the transaction being refunded.
     *
     * @param array  $request
     * @param string $reference
     *
     * @return RequestInterface
     */
    protected function prepareRefundRequest($request, string $reference): RequestInterface
    {
        /** @var AbstractRequest $refundRequest */
        $refundRequest = $this->gateway()->refund($request);
        $refundRequest->setTransactionReference($reference);

        return $refundRequest;

    }

    /**
     * Send a request to the actual gateway.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        $data = $request->getData();

        $event = new SendPaymentRequestEvent([
            'requestData' => $data
        ]);

        // Raise 'beforeSendPaymentRequest' event
        $this->trigger(self::EVENT_BEFORE_SEND_PAYMENT_REQUEST, $event);

        // We can't merge the $data with $modifiedData since the $data is not always an array.
        // For example it could be a XML object, json, or anything else really.
        if ($event->modifiedRequestData !== null) {
            return $request->sendData($event->modifiedRequestData);
        }

        return $request->send();
    }
}
