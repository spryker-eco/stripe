<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\PaymentMethods;

use Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsResponseTransfer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SprykerEco\Client\Stripe\Api\ApiLogger;
use SprykerEco\Client\Stripe\StripeConfig;

class PaymentMethodsApiRequest
{
    public function __construct(
        protected StripeConfig $stripeConfig,
        protected ApiLogger $apiLogger,
        protected Client $guzzleClient,
        protected PaymentMethodsMapper $paymentMethodsMapper,
    ) {
    }

    public function request(
        StripePaymentMethodsRequestTransfer $stripePaymentMethodsRequestTransfer,
    ): StripePaymentMethodsResponseTransfer {
        try {
            $this->apiLogger->logRequest($this->getUrl(), $stripePaymentMethodsRequestTransfer);

            $requestBody = $this->paymentMethodsMapper->mapRequest($stripePaymentMethodsRequestTransfer);
            $httpResponse = $this->sendRequest($requestBody);

            $stripeApiResponseTransfer = $this->paymentMethodsMapper->mapResponse($httpResponse);
            $this->apiLogger->logResponse($httpResponse, $stripeApiResponseTransfer);

            return $stripeApiResponseTransfer;
        } catch (RequestException $exception) {
            $this->apiLogger->logErrorRequest($exception, $exception->hasResponse() ? $exception->getResponse() : null);

            return $this->paymentMethodsMapper->mapErrorResponse($exception);
        }
    }

    protected function sendRequest(string $requestBody): ResponseInterface
    {
        $requestOptions = [
            RequestOptions::HEADERS => $this->stripeConfig->getDefaultHeaders(),
            RequestOptions::TIMEOUT => $this->stripeConfig->getApiTimeout(),
            RequestOptions::BODY => $requestBody,
        ];

        return $this->guzzleClient->request(
            'GET',
            $this->getUrl(),
            $requestOptions,
        );
    }

    protected function getUrl(): string
    {
        return $this->stripeConfig->getPaymentMethodsUrl();
    }
}
