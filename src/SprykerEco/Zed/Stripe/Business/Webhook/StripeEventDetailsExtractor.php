<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Webhook;

use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Throwable;

class StripeEventDetailsExtractor implements StripeEventDetailsExtractorInterface
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function extractPaymentIntentDetails(PaymentIntent $paymentIntent): array
    {
        $data = [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => $paymentIntent->amount,
            'amount capturable' => $paymentIntent->amount_capturable,
            'amount received' => $paymentIntent->amount_received,
            'currency' => strtoupper((string)$paymentIntent->currency),
            'capture method' => $paymentIntent->capture_method,
            'mode' => $paymentIntent->livemode ? 'live' : 'test',
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
    public function extractChargeDetails(Charge $charge): array
    {
        $data = [
            'id' => $charge->id,
            'status' => $charge->status,
            'amount' => $charge->amount,
            'amount captured' => $charge->amount_captured,
            'amount refunded' => $charge->amount_refunded,
            'currency' => strtoupper((string)$charge->currency),
            'captured' => $charge->captured ? 'yes' : 'no',
            'mode' => $charge->livemode ? 'live' : 'test',
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
    public function extractRefundDetails(Refund $refund): array
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
            'failure reason' => $refund->__isset('failure_reason') ? $refund->failure_reason : null,
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
        } catch (Throwable $exception) {
            $this->getLogger()->warning('WebhookHandler: could not retrieve PaymentMethod details', [
                'paymentMethodId' => $paymentMethodId,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        /** @var array<string, mixed> $cardData */
        $cardData = $paymentMethod->card !== null ? (array)$paymentMethod->card->toArray() : [];
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
}
