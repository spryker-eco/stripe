<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Webhook;

use Exception;
use Generated\Shared\Transfer\PaymentAppPaymentStatusCriteriaTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandler;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReader;
use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\Charge;
use Stripe\Event;
use Stripe\Webhook;

class WebhookHandler implements WebhookHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeConfig $config,
        protected PaymentAppFacadeInterface $paymentAppFacade,
        protected PaymentReader $paymentReader,
        protected MerchantOnboardingHandler $merchantOnboardingHandler,
    ) {
    }

    public function processWebhook(StripeWebhookPayloadTransfer $webhookPayloadTransfer): StripeWebhookProcessResponseTransfer
    {
        $response = new StripeWebhookProcessResponseTransfer();
        $response->setIsSuccessful(false);

        try {
            $webhookSecret = $this->resolveWebhookSecret($webhookPayloadTransfer->getRawPayloadOrFail());

            $event = Webhook::constructEvent(
                $webhookPayloadTransfer->getRawPayloadOrFail(),
                $webhookPayloadTransfer->getSignatureHeaderOrFail(),
                $webhookSecret,
            );
        } catch (Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
            $response->setMessage($exception->getMessage());

            return $response;
        }

        if ($event->object !== 'event') {
            return $response->setMessage('Webhook request must be a Stripe event.');
        }

        return match ($event->type) {
            Event::PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED => $this->handleAmountCapturableUpdated($response, $event),
            Event::PAYMENT_INTENT_SUCCEEDED => $this->handlePaymentSucceeded($response, $event),
            Event::PAYMENT_INTENT_PAYMENT_FAILED => $this->handlePaymentFailed($response, $event),
            Event::CHARGE_FAILED => $this->handleChargeFailed($response, $event),
            Event::CHARGE_REFUND_UPDATED => $this->handleRefundUpdated($response, $event),
            Event::ACCOUNT_UPDATED => $this->merchantOnboardingHandler->handleAccountUpdated($response, $event),
            default => $response->setIsSuccessful(true),
        };
    }

    protected function handleAmountCapturableUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        /** @var \Stripe\PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->offsetGet('object');

        if ($paymentIntent->status !== 'requires_capture') {
            return $response->setIsSuccessful(true);
        }

        $orderReference = $paymentIntent->metadata[StripeConfig::METADATA_ORDER_REFERENCE] ?? null;

        if ($orderReference === null) {
            $this->getLogger()->warning('WebhookHandler: orderReference missing from PaymentIntent metadata', [
                'paymentIntentId' => $paymentIntent->id,
            ]);

            return $response->setIsSuccessful(true);
        }

        $this->paymentAppFacade->savePaymentAppPaymentStatus(
            (new PaymentAuthorizedTransfer())->setOrderReference($orderReference),
        );

        return $response->setIsSuccessful(true);
    }

    protected function handlePaymentSucceeded(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        /** @var \Stripe\PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->offsetGet('object');
        $orderReference = $paymentIntent->metadata[StripeConfig::METADATA_ORDER_REFERENCE] ?? null;

        if ($orderReference === null) {
            $this->getLogger()->warning('WebhookHandler: orderReference missing from PaymentIntent metadata', [
                'paymentIntentId' => $paymentIntent->id,
            ]);

            return $response->setIsSuccessful(true);
        }

        $this->paymentAppFacade->savePaymentAppPaymentStatus(
            (new PaymentCapturedTransfer())->setOrderReference($orderReference),
        );

        return $response->setIsSuccessful(true);
    }

    protected function handlePaymentFailed(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        /** @var \Stripe\PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->offsetGet('object');
        $orderReference = $paymentIntent->metadata[StripeConfig::METADATA_ORDER_REFERENCE] ?? null;

        if ($orderReference === null) {
            $this->getLogger()->warning('WebhookHandler: orderReference missing from PaymentIntent metadata', [
                'paymentIntentId' => $paymentIntent->id,
            ]);

            return $response->setIsSuccessful(true);
        }

        // 3DS retry: if payment is still in NEW state, do not update status — customer can retry.
        if ($this->isPaymentInNewState($orderReference)) {
            return $response->setIsSuccessful(true);
        }

        $this->paymentAppFacade->savePaymentAppPaymentStatus(
            (new PaymentCaptureFailedTransfer())->setOrderReference($orderReference),
        );

        return $response->setIsSuccessful(true);
    }

    protected function handleChargeFailed(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        $charge = $event->data->offsetGet('object');

        if (!($charge instanceof Charge) || !$charge->captured) {
            // Charge failed before capture — treat as authorization failure handled by payment_intent.payment_failed
            return $response->setIsSuccessful(true);
        }

        $paymentIntentId = is_string($charge->payment_intent) ? $charge->payment_intent : $charge->payment_intent?->id;

        if ($paymentIntentId === null) {
            return $response->setIsSuccessful(true);
        }

        $stripePaymentTransfer = $this->paymentReader->getPaymentByTransactionId($paymentIntentId);

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getOrderReference() === null) {
            $this->getLogger()->warning('WebhookHandler: no payment found for charge.failed', [
                'paymentIntentId' => $paymentIntentId,
            ]);

            return $response->setIsSuccessful(true);
        }

        $this->paymentAppFacade->savePaymentAppPaymentStatus(
            (new PaymentCaptureFailedTransfer())->setOrderReference($stripePaymentTransfer->getOrderReference()),
        );

        return $response->setIsSuccessful(true);
    }

    protected function handleRefundUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        $refund = $event->data->offsetGet('object');
        $refundStatus = $refund->status ?? null;

        $chargeId = is_string($refund->charge) ? $refund->charge : $refund->charge?->id;
        $paymentIntentId = is_string($refund->payment_intent ?? null)
            ? $refund->payment_intent
            : $refund->payment_intent?->id;

        $orderReference = $this->resolveOrderReferenceFromPaymentIntentId($paymentIntentId);

        if ($orderReference === null && $chargeId !== null) {
            // Fall back to charge-based lookup if needed
            $this->getLogger()->warning('WebhookHandler: could not resolve orderReference for charge.refund.updated', [
                'chargeId' => $chargeId,
                'paymentIntentId' => $paymentIntentId,
            ]);

            return $response->setIsSuccessful(true);
        }

        if ($orderReference === null) {
            return $response->setIsSuccessful(true);
        }

        if ($refundStatus === 'succeeded') {
            $this->paymentAppFacade->savePaymentAppPaymentStatus(
                (new PaymentRefundedTransfer())->setOrderReference($orderReference),
            );
        } elseif ($refundStatus === 'failed') {
            $this->paymentAppFacade->savePaymentAppPaymentStatus(
                (new PaymentRefundFailedTransfer())->setOrderReference($orderReference),
            );
        }

        return $response->setIsSuccessful(true);
    }

    protected function resolveOrderReferenceFromPaymentIntentId(?string $paymentIntentId): ?string
    {
        if ($paymentIntentId === null) {
            return null;
        }

        $stripePaymentTransfer = $this->paymentReader->getPaymentByTransactionId($paymentIntentId);

        return $stripePaymentTransfer?->getOrderReference();
    }

    /**
     * Check if the current payment status is NEW (i.e. payment not yet authorized).
     * Used to detect 3DS retry scenarios where we should NOT update the status to capture_failed.
     */
    protected function isPaymentInNewState(string $orderReference): bool
    {
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($stripePaymentTransfer === null) {
            return false;
        }

        // If there's no status recorded yet in PaymentApp, it's still new
        $statusCollection = $this->paymentAppFacade->getPaymentAppPaymentStatusCollection(
            (new PaymentAppPaymentStatusCriteriaTransfer())
                ->setOrderReferences([$orderReference]),
        );

        foreach ($statusCollection->getPaymentAppPaymentStates() as $statusTransfer) {
            if ($statusTransfer->getStatus() !== SharedStripeConfig::PAYMENT_STATUS_NEW) {
                return false;
            }
        }

        return true;
    }

    /**
     * Select the appropriate webhook secret depending on business model and event type.
     * For marketplace + account.updated: use connected account secret.
     * Otherwise: use the standard webhook secret.
     */
    protected function resolveWebhookSecret(string $rawPayload): string
    {
        if ($this->config->getBusinessModel() === SharedStripeConfig::BUSINESS_MODEL_MARKETPLACE) {
            $content = (array)json_decode($rawPayload, true);
            $eventType = $content['type'] ?? '';

            if ($eventType === Event::ACCOUNT_UPDATED) {
                return $this->config->getConnectedAccountWebhookSecret();
            }
        }

        return $this->config->getWebhookSecret();
    }
}
