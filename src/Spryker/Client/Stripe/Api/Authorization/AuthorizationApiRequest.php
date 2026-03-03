<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Authorization;

use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SprykerEco\Client\Stripe\Api\ApiLogger;
use SprykerEco\Client\Stripe\StripeConfig;

class AuthorizationApiRequest
{
    public function __construct(
        protected StripeConfig $stripeConfig,
        protected ApiLogger $apiLogger,
        protected Client $guzzleClient,
        protected AuthorizationMapper $authorizeMapper,
    ) {
    }

    public function request(StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer): StripeAuthorizeResponseTransfer
    {
        try {
            $this->apiLogger->logRequest($this->getUrl(), $stripeAuthorizeRequestTransfer);

            $requestBody = $this->authorizeMapper->mapRequest($stripeAuthorizeRequestTransfer);
            $httpResponse = $this->sendRequest($requestBody);

            $stripeApiResponseTransfer = $this->authorizeMapper->mapResponse($httpResponse);
            $this->apiLogger->logResponse($httpResponse, $stripeApiResponseTransfer);

            return $stripeApiResponseTransfer;
        } catch (RequestException $exception) {
            $this->apiLogger->logErrorRequest($exception, $exception->hasResponse() ? $exception->getResponse() : null);

            return $this->authorizeMapper->mapErrorResponse($exception);
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
            'POST',
            $this->getUrl(),
            $requestOptions,
        );
    }

    protected function getUrl(): string
    {
        return $this->stripeConfig->getAuthorizeUrl();
    }
}
