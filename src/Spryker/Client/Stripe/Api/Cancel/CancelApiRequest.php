<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Cancel;

use Generated\Shared\Transfer\StripeCancelRequestTransfer;
use Generated\Shared\Transfer\StripeCancelResponseTransfer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SprykerEco\Client\Stripe\Api\ApiLogger;
use SprykerEco\Client\Stripe\StripeConfig;

class CancelApiRequest
{
    public function __construct(
        protected StripeConfig $stripeConfig,
        protected ApiLogger $apiLogger,
        protected Client $guzzleClient,
        protected CancelMapper $cancelMapper,
    ) {
    }

    public function request(StripeCancelRequestTransfer $stripeCancelRequestTransfer): StripeCancelResponseTransfer
    {
        try {
            $this->apiLogger->logRequest($this->getUrl(), $stripeCancelRequestTransfer);

            $requestBody = $this->cancelMapper->mapRequest($stripeCancelRequestTransfer);
            $httpResponse = $this->sendRequest($requestBody);

            $stripeCancelResponseTransfer = $this->cancelMapper->mapResponse($httpResponse);
            $this->apiLogger->logResponse($httpResponse, $stripeCancelResponseTransfer);

            return $stripeCancelResponseTransfer;
        } catch (RequestException $exception) {
            $this->apiLogger->logErrorRequest($exception, $exception->hasResponse() ? $exception->getResponse() : null);

            return $this->cancelMapper->mapErrorResponse($exception);
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
        return $this->stripeConfig->getCancelUrl();
    }
}
