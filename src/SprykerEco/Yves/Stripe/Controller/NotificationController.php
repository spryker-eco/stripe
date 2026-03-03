<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Exception;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class NotificationController extends AbstractController
{
    public function notificationAction(Request $request): Response
    {
        $webhookPayloadTransfer = $this->createWebhookPayload($request);

        try {
            $stripeWebhookProcessResponseTransfer = $this->getFactory()
                ->getStripeClient()
                ->processWebhook($webhookPayloadTransfer);

            if (!$stripeWebhookProcessResponseTransfer->getIsSuccessful()) {
                return new Response(
                    'Webhook processing failed',
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            return new Response('OK', Response::HTTP_OK);
        } catch (Exception $exception) {
            return new Response(
                'Webhook processing failed: ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    protected function createWebhookPayload(Request $request): StripeWebhookPayloadTransfer
    {
        return (new StripeWebhookPayloadTransfer())
            ->setRawPayload($request->getContent())
            ->setSignatureHeader((string)$request->headers->get('Stripe-Signature', ''));
    }
}
