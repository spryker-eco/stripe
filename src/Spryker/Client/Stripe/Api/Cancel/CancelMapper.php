<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Cancel;

use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripeCancelRequestTransfer;
use Generated\Shared\Transfer\StripeCancelResponseTransfer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CancelMapper
{
    public function mapRequest(StripeCancelRequestTransfer $requestTransfer): string
    {
        return json_encode($requestTransfer->toArray());
    }

    public function mapResponse(ResponseInterface $httpResponse): StripeCancelResponseTransfer
    {
        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return (new StripeCancelResponseTransfer())->fromArray($responseBody);
    }

    public function mapErrorResponse(RequestException $exception): StripeCancelResponseTransfer
    {
        $errorResponseTransfer = (new StripeApiErrorResponseTransfer())
            ->setMessage($exception->getMessage())
            ->setStatusCode($exception->getCode());

        if ($exception->hasResponse()) {
            $errorResponseTransfer
                ->setStatusCode($exception->getResponse()->getStatusCode())
                ->setBody($exception->getResponse()->getBody()->getContents());
        }

        return (new StripeCancelResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorResponse($errorResponseTransfer);
    }
}
