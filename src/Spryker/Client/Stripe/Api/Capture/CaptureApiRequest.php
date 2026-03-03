<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Capture;

use Generated\Shared\Transfer\StripeCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeCaptureResponseTransfer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SprykerEco\Client\Stripe\Api\ApiLogger;
use SprykerEco\Client\Stripe\StripeConfig;

class CaptureApiRequest
{
    public function __construct(
        protected StripeConfig $stripeConfig,
        protected ApiLogger $apiLogger,
        protected Client $guzzleClient,
        protected CaptureMapper $captureMapper,
    ) {
    }

    public function request(StripeCaptureRequestTransfer $stripeCaptureRequestTransfer): StripeCaptureResponseTransfer
    {
        try {
            $this->apiLogger->logRequest($this->getUrl(), $stripeCaptureRequestTransfer);

            $requestBody = $this->captureMapper->mapRequest($stripeCaptureRequestTransfer);
            $httpResponse = $this->sendRequest($requestBody);

            $stripeApiResponseTransfer = $this->captureMapper->mapResponse($httpResponse);
            $this->apiLogger->logResponse($httpResponse, $stripeApiResponseTransfer);

            return $stripeApiResponseTransfer;
        } catch (RequestException $exception) {
            $this->apiLogger->logErrorRequest($exception, $exception->hasResponse() ? $exception->getResponse() : null);

            return $this->captureMapper->mapErrorResponse($exception);
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
        return $this->stripeConfig->getCaptureUrl();
    }
}
