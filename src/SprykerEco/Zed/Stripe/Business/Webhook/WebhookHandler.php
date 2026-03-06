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
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use Spryker\Zed\SalesPaymentDetail\Business\SalesPaymentDetailFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandler;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReader;
use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\Charge;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Webhook;

class WebhookHandler implements WebhookHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeConfig $config,
        protected PaymentAppFacadeInterface $paymentAppFacade,
        protected PaymentReader $paymentReader,
        protected MerchantOnboardingHandler $merchantOnboardingHandler,
        protected SalesPaymentDetailFacadeInterface $salesPaymentDetailFacade,
        protected StripeClientFactory $stripeClientFactory,
    ) {
    }

    public function processWebhook(StripeWebhookPayloadTransfer $webhookPayloadTransfer): StripeWebhookProcessResponseTransfer
    {
        $response = new StripeWebhookProcessResponseTransfer();
        $response->setIsSuccessful(false);

        try {
            $rawPayload = $webhookPayloadTransfer->getRawPayloadOrFail();
            $webhookSecret = $this->resolveWebhookSecret($rawPayload);
            $signatureHeader = $webhookPayloadTransfer->getSignatureHeaderOrFail();

            $event = $this->constructEvent($rawPayload, $signatureHeader, $webhookSecret);
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

        $this->salesPaymentDetailFacade->handlePaymentCreated(
            (new PaymentCreatedTransfer())
                ->setEntityReference($orderReference)
                ->setPaymentReference($paymentIntent->id)
                ->setDetails((string)json_encode($this->extractPaymentIntentDetails($paymentIntent))),
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

        $this->salesPaymentDetailFacade->handlePaymentUpdated(
            (new PaymentUpdatedTransfer())
                ->setEntityReference($orderReference)
                ->setPaymentReference($paymentIntent->id)
                ->setDetails((string)json_encode($this->extractPaymentIntentDetails($paymentIntent))),
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

        $this->salesPaymentDetailFacade->handlePaymentUpdated(
            (new PaymentUpdatedTransfer())
                ->setEntityReference($orderReference)
                ->setPaymentReference($paymentIntent->id)
                ->setDetails((string)json_encode($this->extractPaymentIntentDetails($paymentIntent))),
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

        $this->salesPaymentDetailFacade->handlePaymentUpdated(
            (new PaymentUpdatedTransfer())
                ->setEntityReference($stripePaymentTransfer->getOrderReference())
                ->setPaymentReference($paymentIntentId)
                ->setDetails((string)json_encode($this->extractChargeDetails($charge))),
        );

        return $response->setIsSuccessful(true);
    }

    protected function handleRefundUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        $refund = $event->data->offsetGet('object');
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

        // PaymentMessageMapper does not support refund statuses — OMS refund state is managed
        // via the refundPayment() command flow, not through savePaymentAppPaymentStatus().
        // We only persist the updated refund details for audit/reference.
        if ($paymentIntentId !== null) {
            $this->salesPaymentDetailFacade->handlePaymentUpdated(
                (new PaymentUpdatedTransfer())
                    ->setEntityReference($orderReference)
                    ->setPaymentReference($paymentIntentId)
                    ->setDetails((string)json_encode($this->extractRefundDetails($refund))),
            );
        }

        return $response->setIsSuccessful(true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractPaymentIntentDetails(PaymentIntent $paymentIntent): array
    {
        $data = [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => $paymentIntent->amount,
            'amount capturable' => $paymentIntent->amount_capturable,
            'amount received' => $paymentIntent->amount_received,
            'currency' => strtoupper((string)$paymentIntent->currency),
            'capture method' => $paymentIntent->capture_method,
            'live mode' => $paymentIntent->livemode ? 'yes' : 'no',
            'created' => $paymentIntent->created ? date('Y-m-d H:i:s', $paymentIntent->created) : null,
            'customer' => is_string($paymentIntent->customer) ? $paymentIntent->customer : $paymentIntent->customer?->id,
            'payment method' => is_string($paymentIntent->payment_method) ? $paymentIntent->payment_method : $paymentIntent->payment_method?->id,
            'latest charge' => is_string($paymentIntent->latest_charge) ? $paymentIntent->latest_charge : $paymentIntent->latest_charge?->id,
            'description' => $paymentIntent->description,
        ];

        $paymentMethodId = is_string($paymentIntent->payment_method)
            ? $paymentIntent->payment_method
            : $paymentIntent->payment_method?->id;

        if ($paymentMethodId) {
            $paymentMethodDetails = $this->extractPaymentMethodDetails($paymentMethodId);
            if ($paymentMethodDetails) {
                $data['payment method details'] = $paymentMethodDetails;
            }
        }

        $lastError = $paymentIntent->last_payment_error;
        if ($lastError) {
            $data['last payment error'] = array_filter([
                'code' => $lastError->code ?? null,
                'decline code' => $lastError->decline_code ?? null,
                'message' => $lastError->message ?? null,
                'type' => $lastError->type ?? null,
            ], fn ($v): bool => $v !== null && $v !== '');
        }

        if ($paymentIntent->cancellation_reason) {
            $data['cancellation reason'] = $paymentIntent->cancellation_reason;
        }

        if ($paymentIntent->canceled_at) {
            $data['canceled at'] = date('Y-m-d H:i:s', $paymentIntent->canceled_at);
        }

        return array_filter($data, fn ($v): bool => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractChargeDetails(Charge $charge): array
    {
        $data = [
            'id' => $charge->id,
            'status' => $charge->status,
            'amount' => $charge->amount,
            'amount captured' => $charge->amount_captured,
            'amount refunded' => $charge->amount_refunded,
            'currency' => strtoupper((string)$charge->currency),
            'captured' => $charge->captured ? 'yes' : 'no',
            'live mode' => $charge->livemode ? 'yes' : 'no',
            'created' => $charge->created ? date('Y-m-d H:i:s', $charge->created) : null,
            'customer' => is_string($charge->customer) ? $charge->customer : $charge->customer?->id,
            'payment intent' => is_string($charge->payment_intent) ? $charge->payment_intent : $charge->payment_intent?->id,
            'payment method' => $charge->payment_method,
            'description' => $charge->description,
            'failure code' => $charge->failure_code,
            'failure message' => $charge->failure_message,
        ];

        $metadata = $charge->metadata->toArray();
        if ($metadata) {
            $data['metadata'] = $metadata;
        }

        /** @var array<string, mixed> $cardData */
        $cardData = (array)(($charge->payment_method_details?->toArray() ?? [])['card'] ?? []);
        if ($cardData) {
            /** @var array<string, mixed> $tds */
            $tds = (array)($cardData['three_d_secure'] ?? []);
            /** @var array<string, mixed> $checks */
            $checks = (array)($cardData['checks'] ?? []);
            $data['card'] = array_filter([
                'brand' => $cardData['brand'] ?? null,
                'last4' => $cardData['last4'] ?? null,
                'expires' => isset($cardData['exp_month'], $cardData['exp_year'])
                    ? sprintf('%02d / %d', $cardData['exp_month'], $cardData['exp_year'])
                    : null,
                'funding' => $cardData['funding'] ?? null,
                'country' => $cardData['country'] ?? null,
                'cvc check' => $checks['cvc_check'] ?? null,
                '3ds version' => $tds['version'] ?? null,
                '3ds result' => $tds['result'] ?? null,
                '3ds authenticated' => isset($tds['authenticated'])
                    ? ($tds['authenticated'] ? 'yes' : 'no')
                    : null,
            ], fn ($v): bool => $v !== null && $v !== '');
        }

        $outcome = $charge->outcome;
        if ($outcome) {
            $data['outcome'] = array_filter([
                'network status' => $outcome->network_status ?? null,
                'reason' => $outcome->reason ?? null,
                'risk level' => $outcome->risk_level ?? null,
                'risk score' => isset($outcome->risk_score) ? (string)$outcome->risk_score : null,
                'seller message' => $outcome->seller_message ?? null,
                'type' => $outcome->type ?? null,
            ], fn ($v): bool => $v !== null && $v !== '');
        }

        return array_filter($data, fn ($v): bool => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractRefundDetails(Refund $refund): array
    {
        $data = [
            'type' => 'refund',
            'id' => $refund->id,
            'status' => $refund->status,
            'amount' => $refund->amount,
            'currency' => strtoupper((string)$refund->currency),
            'charge' => is_string($refund->charge) ? $refund->charge : $refund->charge?->id,
            'payment intent' => is_string($refund->payment_intent) ? $refund->payment_intent : $refund->payment_intent?->id,
            'reason' => $refund->reason,
            'failure reason' => $refund->failure_reason,
            'created' => $refund->created ? date('Y-m-d H:i:s', $refund->created) : null,
        ];

        return array_filter($data, fn ($v): bool => $v !== null && $v !== '');
    }

    /**
     * Fetches full PaymentMethod from Stripe and returns card/billing details useful for admin.
     *
     * @return array<string, mixed>
     */
    protected function extractPaymentMethodDetails(string $paymentMethodId): array
    {
        try {
            $paymentMethod = $this->stripeClientFactory->create()->paymentMethods->retrieve($paymentMethodId);
        } catch (\Throwable $exception) {
            $this->getLogger()->warning('WebhookHandler: could not retrieve PaymentMethod details', [
                'paymentMethodId' => $paymentMethodId,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        /** @var array<string, mixed> $cardData */
        $cardData = (array)($paymentMethod->card?->toArray() ?? []);
        if (!$cardData) {
            // Non-card payment method — return type only
            return ['type' => $paymentMethod->type];
        }

        /** @var array<string, mixed> $checks */
        $checks = (array)($cardData['checks'] ?? []);
        /** @var array<string, mixed> $tdsUsage */
        $tdsUsage = (array)($cardData['three_d_secure_usage'] ?? []);
        /** @var array<string, mixed> $billingAddress */
        $billingAddress = (array)($paymentMethod->billing_details->toArray()['address'] ?? []);

        return array_filter([
            'type' => isset($cardData['brand'], $cardData['funding'])
                ? ucfirst((string)$cardData['brand']) . ' ' . $cardData['funding'] . ' card'
                : $paymentMethod->type,
            'last4' => $cardData['last4'] ?? null,
            'expires' => isset($cardData['exp_month'], $cardData['exp_year'])
                ? sprintf('%02d / %d', $cardData['exp_month'], $cardData['exp_year'])
                : null,
            'country' => $cardData['country'] ?? null,
            'cvc check' => $checks['cvc_check'] ?? null,
            '3ds supported' => isset($tdsUsage['supported'])
                ? ($tdsUsage['supported'] ? 'yes' : 'no')
                : null,
            'billing country' => $billingAddress['country'] ?? null,
        ], fn ($v): bool => $v !== null && $v !== '');
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
     * Construct and verify a Stripe Event from the raw payload.
     * When no webhook secret is configured (e.g. local development), skip signature
     * verification and parse the payload directly — never skip in production.
     *
     * @throws \Exception
     */
    protected function constructEvent(string $rawPayload, string $signatureHeader, string $webhookSecret): Event
    {
        if ($webhookSecret === '') {
            $this->getLogger()->warning('Stripe webhook secret is not configured — skipping signature verification. Do NOT use this in production.');

            /** @var \Stripe\Event $event */
            $event = Event::constructFrom((array)json_decode($rawPayload, true));

            return $event;
        }

        try {
            return Webhook::constructEvent($rawPayload, $signatureHeader, $webhookSecret);
        } catch (SignatureVerificationException $exception) {
            throw new Exception(sprintf('Webhook signature verification failed: %s', $exception->getMessage()), 0, $exception);
        }
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
