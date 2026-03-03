<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Exception;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use SprykerEco\Yves\Kernel\Controller\AbstractController;
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

            if (!$stripeWebhookProcessResponseTransfer->getIsSuccess()) {
                return new Response(
                    'Webhook processing failed',
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            // TODO: Change the default response to respond with the status and content the PSP expects.
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
        // TODO: Extract webhook data from the request and populate the transfer.
        // Parse the request body, headers, and any PSP-specific data.
        // e.g.
        // $webhookPayloadTransfer = new StripeWebhookPayloadTransfer();
        // $webhookPayloadTransfer->setRawPayload($request->getContent());
        // $webhookPayloadTransfer->setHeaders($request->headers->all());
        // $webhookPayloadTransfer->setProviderReference($this->extractProviderReference($request));
        // $webhookPayloadTransfer->setEventType($this->extractEventType($request));
        $webhookPayloadTransfer = new StripeWebhookPayloadTransfer();

        return $webhookPayloadTransfer;
    }
}
