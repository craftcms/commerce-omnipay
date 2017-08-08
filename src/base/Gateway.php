<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\events\SendPaymentRequestEvent;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin;
use craft\helpers\UrlHelper;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\CreditCard;
use Omnipay\Common\ItemBag;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;

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

    // Protected Methods
    // =========================================================================

    /**
     * Create a payment card based on Order and Payment form.
     *
     * @param Order           $order The order.
     * @param BasePaymentForm $form The payment form.
     *
     * @return mixed
     */
    abstract public function createCard(Order $order, BasePaymentForm $form): CreditCard;
    
    /**
     * Creates and returns an Omnipay gateway instance based on the stored settings.
     *
     * @return AbstractGateway The actual gateway.
     */
    abstract protected function createGateway();

    /**
     * Create a gateway specific item bag for the order.
     *
     * @param Order $order The order.
     *
     * @return ItemBag
     */
    protected function createItemBagForOrder(Order $order): ItemBag
    {
        if (!$this->canSendCartInfo) {
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
     */
    protected function createPaymentRequest(Transaction $transaction, $card = null, $itemBag = null)
    {
        $request = [
            'amount' => $transaction->paymentAmount,
            'currency' => $transaction->paymentCurrency,
            'transactionId' => $transaction->id,
            'description' => Craft::t('commerce', 'Order').' #'.$transaction->orderId,
            'clientIp' => Craft::$app->getRequest()->userIP,
            'transactionReference' => $transaction->hash,
            'returnUrl' => UrlHelper::actionUrl('commerce/payments/completePayment', ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash]),
            'cancelUrl' => UrlHelper::siteUrl($transaction->order->cancelUrl),
        ];

        // Each gateway adapter needs to know whether to use our acceptNotification handler because most omnipay gateways
        // implement the notification API differently. Hoping Omnipay v3 will improve this.
        // For now, the standard paymentComplete handler is the default unless the gateway has been tested with our acceptNotification handler.
        // TODO: move the handler logic into the gateway adapter itself if the Omnipay v2 interface cannot standardise.
        // TODO: It was moved. What now?
        if ($this->useNotifyUrl()) {
            $request['notifyUrl'] = UrlHelper::actionUrl('commerce/payments/acceptNotification', ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash]);
            unset($request['returnUrl']);
        } else {
            $request['notifyUrl'] = $request['returnUrl'];
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
     * @inheritdoc
     */
    protected function getRequest(Transaction $transaction, BasePaymentForm $form)
    {
        $order = $transaction->getOrder();
        $card = $this->createCard($order, $form);
        $itemBag = $this->getItemBagForOrder($order);

        $request = $this->createPaymentRequest($transaction, $card, $itemBag);
        $this->populateRequest($request, $form);

        return $request;
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
     * Populate the request array before it's dispatched.
     *
     * @param array $request Parameter array by reference.
     * @param BasePaymentForm $form
     *
     * @return void
     */
    abstract public function populateRequest(array &$request, BasePaymentForm $form);

    /**
     * @inheritdoc
     */
    protected function prepareAuthorizeRequest($request): RequestInterface
    {
        return $this->gateway()->authorize($request);
    }
    
    /**
     * @inheritdoc
     */
    protected function prepareResponse($response): RequestResponseInterface
    {
        /** @var AbstractResponse $response */
        return new RequestResponse($response);
    }

    /**
     * @inheritdoc
     */
    protected function preparePurchaseRequest($request): RequestInterface
    {
        return $this->gateway()->purchase($request);
    }

    /**
     * @inheritdoc
     */
    protected function sendRequest($request): ResponseInterface
    {
        /** @var RequestInterface $request */
        $data = $request->getData();

        $event = new SendPaymentRequestEvent([
            'requestData' => $data
        ]);

        // Raise 'beforeSendPaymentRequest' event
        $payments = Plugin::getInstance()->getPayments();
        $payments->trigger($payments::EVENT_BEFORE_SEND_PAYMENT_REQUEST, $event);

        // We can't merge the $data with $modifiedData since the $data is not always an array.
        // For example it could be a XML object, json, or anything else really.
        if ($event->modifiedRequestData !== null) {
            return $request->sendData($event->modifiedRequestData);
        }

        return $request->send();
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
}
