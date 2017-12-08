<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
class RequestResponse implements RequestResponseInterface
{
    /**
     * @var AbstractResponse
     */
    protected $response;

    /**
     * @var Transaction
     */
    protected $transaction;

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
        return $this->response->isSuccessful();
    }

    /**
     * @inheritdoc
     */
    public function isProcessing(): bool
    {
        return $this->response->isPending();
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(): bool
    {
        return $this->response->isRedirect();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectMethod(): string
    {
        return (string) $this->response->getRedirectMethod();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectData(): array
    {
        return $this->response->getRedirectData();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUrl(): string
    {
        return (string) $this->response->getRedirectUrl();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference(): string
    {
        return (string) $this->response->getTransactionReference();
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
        return (string) $this->response->getMessage();
    }

    /**
     * @inheritdoc
     */
    public function redirect()
    {
        $this->response->redirect();
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->response->getData();
    }


}
