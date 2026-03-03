<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Capture;

use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripeCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeCaptureResponseTransfer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CaptureMapper
{
    public function mapRequest(StripeCaptureRequestTransfer $requestTransfer): string
    {
        return json_encode($requestTransfer->toArray());
    }

    public function mapResponse(ResponseInterface $httpResponse): StripeCaptureResponseTransfer
    {
        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return (new StripeCaptureResponseTransfer())->fromArray($responseBody);
    }

    public function mapErrorResponse(RequestException $exception): StripeCaptureResponseTransfer
    {
        $errorResponseTransfer = (new StripeApiErrorResponseTransfer())
            ->setMessage($exception->getMessage())
            ->setStatusCode($exception->getCode());

        if ($exception->hasResponse()) {
            $errorResponseTransfer
                ->setStatusCode($exception->getResponse()->getStatusCode())
                ->setBody($exception->getResponse()->getBody()->getContents());
        }

        return (new StripeCaptureResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorResponse($errorResponseTransfer);
    }
}
