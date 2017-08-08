<?php

namespace craft\commerce\omnipay\base;

use Craft;
use craft\commerce\base\RequestResponseInterface;
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
    private $_response;

    public function __construct(AbstractResponse $response)
    {
        $this->_response = $response;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful(): bool
    {
        return $this->_response->isSuccessful();
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(): bool
    {
        return $this->_response->isRedirect();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectMethod()
    {
        return $this->_response->getRedirectMethod();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectData()
    {
        return $this->_response->getRedirectData();
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUrl()
    {
        return $this->_response->getRedirectUrl();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference()
    {
        return $this->_response->getTransactionReference();
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->_response->getCode();
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->_response->getMessage();
    }

    /**
     * @inheritdoc
     */
    public function redirect()
    {
        $this->_response->redirect();
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $this->_response->getData();
    }


}
