<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\PaymentMethods;

use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsResponseTransfer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class PaymentMethodsMapper
{
    public function mapRequest(StripePaymentMethodsRequestTransfer $requestTransfer): string
    {
        return json_encode($requestTransfer->toArray());
    }

    public function mapResponse(ResponseInterface $httpResponse): StripePaymentMethodsResponseTransfer
    {
        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return (new StripePaymentMethodsResponseTransfer())->fromArray($responseBody);
    }

    public function mapErrorResponse(RequestException $exception): StripePaymentMethodsResponseTransfer
    {
        $errorResponseTransfer = (new StripeApiErrorResponseTransfer())
            ->setMessage($exception->getMessage())
            ->setStatusCode($exception->getCode());

        if ($exception->hasResponse()) {
            $errorResponseTransfer
                ->setStatusCode($exception->getResponse()->getStatusCode())
                ->setBody($exception->getResponse()->getBody()->getContents());
        }

        return (new StripePaymentMethodsResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorResponse($errorResponseTransfer);
    }
}
