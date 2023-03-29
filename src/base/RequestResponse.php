<?php

namespace craft\commerce\omnipay\base;

use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use Omnipay\Common\Message\AbstractResponse;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
class RequestResponse implements RequestResponseInterface
{
    /**
     * @var AbstractResponse
     */
    protected AbstractResponse $response;

    /**
     * @var Transaction
     */
    protected Transaction $transaction;

    public function __construct(AbstractResponse $response, Transaction $transaction)
    {
        $this->response = $response;
        $this->transaction = $transaction;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful(): bool
    {
        return (bool)$this->response->isSuccessful();
    }

    /**
     * @inheritdoc
     */
    public function isProcessing(): bool
    {
        return (bool)$this->response->isPending();
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(): bool
    {
        return (bool)$this->response->isRedirect();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectMethod(): string
    {
        return (string)$this->response->getRedirectMethod();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectData(): array
    {
        return $this->response->getRedirectData() ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUrl(): string
    {
        return (string)$this->response->getRedirectUrl();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference(): string
    {
        return (string)$this->response->getTransactionReference();
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return (string)$this->response->getCode();
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return (string)$this->response->getMessage();
    }

    /**
     * @inheritdoc
     */
    public function redirect(): void
    {
        $this->response->redirect();
    }

    /**
     * @inheritdoc
     */
    public function getData(): mixed
    {
        return $this->response->getData();
    }
}
