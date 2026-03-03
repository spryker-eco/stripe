<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Notification;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;

class NotificationProcessor implements NotificationProcessorInterface
{
    public function __construct(
        protected StripeEntityManagerInterface $entityManager,
    ) {
    }

    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer {
        // TODO: Implement webhook processing logic based on your payment service provider webhook specifications.
        // 1. Validate webhook signature or authentication if required by your payment service provider.
        // 2. Parse the webhook payload to identify the event type (authorization, capture, refund, status change, etc.).
        // 3. Find the related StripeTransfer using PaymentReader::findPaymentByProviderReference().
        // 4. Update payment status using StripeEntityManager::updatePaymentStatus() based on the webhook event.
        // 5. Optionally trigger Order Management System state machine transitions if payment status changed.
        // 6. Set appropriate response status (isSuccess, errorMessage) in StripeWebhookProcessResponseTransfer.
        // The webhook payload is saved to the database for audit and debugging purposes.
        // e.g.
        // $providerReference = $webhookPayloadTransfer->getProviderReference();
        // $stripeTransfer = $this->paymentReader->findPaymentByProviderReference($providerReference);
        // if ($stripeTransfer !== null) {
        //     $this->entityManager->updatePaymentStatus(
        //         $stripeTransfer->getIdStripeOrFail(),
        //         $this->mapWebhookEventToPaymentStatus($webhookPayloadTransfer->getEventType()),
        //     );
        // }

        $this->saveNotification($webhookPayloadTransfer);

        $webhookProcessResponseTransfer = new StripeWebhookProcessResponseTransfer();

        return $webhookProcessResponseTransfer;
    }

    protected function saveNotification(
        StripeWebhookPayloadTransfer $stripeWebhookPayloadTransfer,
    ): void {
        $this->entityManager->saveNotification(
            json_encode($stripeWebhookPayloadTransfer->toArray()),
        );
    }
}
