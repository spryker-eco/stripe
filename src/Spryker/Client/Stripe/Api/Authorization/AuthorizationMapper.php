<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api\Authorization;

use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class AuthorizationMapper
{
    public function mapRequest(StripeAuthorizeRequestTransfer $requestTransfer): string
    {
        return json_encode($requestTransfer->toArray());
    }

    public function mapResponse(ResponseInterface $httpResponse): StripeAuthorizeResponseTransfer
    {
        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return (new StripeAuthorizeResponseTransfer())->fromArray($responseBody);
    }

    public function mapErrorResponse(RequestException $exception): StripeAuthorizeResponseTransfer
    {
        $errorResponseTransfer = (new StripeApiErrorResponseTransfer())
            ->setMessage($exception->getMessage())
            ->setStatusCode($exception->getCode());

        if ($exception->hasResponse()) {
            $errorResponseTransfer
                ->setStatusCode($exception->getResponse()->getStatusCode())
                ->setBody($exception->getResponse()->getBody()->getContents());
        }

        return (new StripeAuthorizeResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorResponse($errorResponseTransfer);
    }
}
