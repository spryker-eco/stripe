<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe;

use GuzzleHttp\Client;
use SprykerEco\Client\Kernel\AbstractFactory;
use SprykerEco\Client\Stripe\Api\ApiLogger;
use SprykerEco\Client\Stripe\Api\Authorization\AuthorizationApiRequest;
use SprykerEco\Client\Stripe\Api\Authorization\AuthorizationMapper;
use SprykerEco\Client\Stripe\Api\Cancel\CancelApiRequest;
use SprykerEco\Client\Stripe\Api\Cancel\CancelMapper;
use SprykerEco\Client\Stripe\Api\Capture\CaptureApiRequest;
use SprykerEco\Client\Stripe\Api\Capture\CaptureMapper;
use SprykerEco\Client\Stripe\Api\PaymentMethods\PaymentMethodsApiRequest;
use SprykerEco\Client\Stripe\Api\PaymentMethods\PaymentMethodsMapper;
use SprykerEco\Client\Stripe\Zed\StripeStub;
use SprykerEco\Client\Stripe\Zed\StripeStubInterface;
use SprykerEco\Client\ZedRequest\ZedRequestClientInterface;

/**
 * @method \SprykerEco\Client\Stripe\StripeConfig getConfig()
 */
class StripeFactory extends AbstractFactory
{
    protected function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_ZED_REQUEST);
    }

    protected function createApiLogger(): ApiLogger
    {
        return new ApiLogger();
    }

    protected function createGuzzleClient(): Client
    {
        return new Client();
    }

    public function createAuthorizeRequest(): AuthorizationApiRequest
    {
        return new AuthorizationApiRequest(
            $this->getConfig(),
            $this->createApiLogger(),
            $this->createGuzzleClient(),
            $this->createAuthorizationMapper(),
        );
    }

    public function createAuthorizationMapper(): AuthorizationMapper
    {
        return new AuthorizationMapper();
    }

    public function createCaptureRequest(): CaptureApiRequest
    {
        return new CaptureApiRequest(
            $this->getConfig(),
            $this->createApiLogger(),
            $this->createGuzzleClient(),
            $this->createCaptureMapper(),
        );
    }

    public function createCaptureMapper(): CaptureMapper
    {
        return new CaptureMapper();
    }

    public function createCancelRequest(): CancelApiRequest
    {
        return new CancelApiRequest(
            $this->getConfig(),
            $this->createApiLogger(),
            $this->createGuzzleClient(),
            $this->createCancelMapper(),
        );
    }

    public function createCancelMapper(): CancelMapper
    {
        return new CancelMapper();
    }

    public function createPaymentMethodsRequest(): PaymentMethodsApiRequest
    {
        return new PaymentMethodsApiRequest(
            $this->getConfig(),
            $this->createApiLogger(),
            $this->createGuzzleClient(),
            $this->createPaymentMethodsMapper(),
        );
    }

    public function createPaymentMethodsMapper(): PaymentMethodsMapper
    {
        return new PaymentMethodsMapper();
    }

    public function createZedStub(): StripeStubInterface
    {
        return new StripeStub($this->getZedRequestClient());
    }
}
