<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeCustomerRequestTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureResponseTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\ExceptionInterface;

class StripeIntents implements StripeIntentsInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeClientFactory $stripeClientFactory,
        protected StripeCustomersInterface $stripeCustomers,
        protected PaymentIntentParamsBuilderInterface $paymentIntentParamsBuilder,
        protected PaymentIntentCancellationGuardInterface $paymentIntentCancellationGuard,
    ) {
    }

    public function create(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer
    {
        $quoteTransfer = $stripeIntentRequestTransfer->getQuoteOrFail();
        $stripeIntentResponseTransfer = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(false);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $stripeCustomerRequestTransfer = (new StripeCustomerRequestTransfer())->setQuote($quoteTransfer);
            $stripeCustomerResponseTransfer = $this->stripeCustomers->searchOrCreate($stripeCustomerRequestTransfer);

            $paymentIntentParams = $this->paymentIntentParamsBuilder->build(
                $quoteTransfer,
                $stripeCustomerResponseTransfer,
                $stripeIntentRequestTransfer,
            );

            $paymentIntent = $stripeClient->paymentIntents->create($paymentIntentParams);

            if (!$paymentIntent->__isset('id')) {
                return $stripeIntentResponseTransfer
                    ->setMessage('Payment Intent creation failed: ID is missing in the response.');
            }

            if (!$paymentIntent->__isset('client_secret')) {
                return $stripeIntentResponseTransfer
                    ->setMessage('Payment Intent creation failed: ClientSecret is missing in the response.');
            }

            $stripeIntentResponseTransfer
                ->setIsSuccessful(true)
                ->setTransactionId($paymentIntent->id)
                ->setClientSecret($paymentIntent->client_secret);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                QuoteTransfer::ORDER_REFERENCE => $quoteTransfer->getOrderReference(),
                StripeIntentRequestTransfer::TRANSACTION_ID => $stripeIntentRequestTransfer->getTransactionId(),
            ]);

            $stripeIntentResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeIntentResponseTransfer;
    }

    public function get(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer
    {
        $stripeIntentResponseTransfer = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(false);

        $transactionId = $stripeIntentRequestTransfer->getTransactionIdOrFail();

        try {
            $stripeClient = $this->stripeClientFactory->create();
            $paymentIntent = $stripeClient->paymentIntents->retrieve($transactionId);

            $stripeIntentResponseTransfer
                ->setIsSuccessful(true)
                ->setClientSecret($paymentIntent->client_secret)
                ->setGrandTotal($paymentIntent->amount)
                ->setCurrencyCode($paymentIntent->currency)
                ->setStatus($paymentIntent->status);

            $latestCharge = $paymentIntent->offsetExists('latest_charge') ? $paymentIntent->latest_charge : null;

            if ($latestCharge !== null) {
                $stripeIntentResponseTransfer
                    ->setLatestChargeId(is_string($latestCharge) ? $latestCharge : $latestCharge->id);
            }
        } catch (ExceptionInterface $exception) {
            $this->getLogger()->error($exception, [
                StripeIntentRequestTransfer::TRANSACTION_ID => $transactionId,
            ]);
        }

        return $stripeIntentResponseTransfer;
    }

    public function capture(StripeIntentCaptureRequestTransfer $stripeIntentCaptureRequestTransfer): StripeIntentCaptureResponseTransfer
    {
        $stripeIntentCaptureResponseTransfer = (new StripeIntentCaptureResponseTransfer())
            ->setIsSuccessful(false);

        $transactionId = $stripeIntentCaptureRequestTransfer->getTransactionIdOrFail();

        try {
            $stripeClient = $this->stripeClientFactory->create();
            $paymentIntent = $stripeClient->paymentIntents->retrieve($transactionId);

            // Already succeeded (e.g. Bank Account Payment — auto-captured)
            if ($paymentIntent->status === SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED) {
                return $this->handleAlreadySucceededCapture(
                    $stripeIntentCaptureResponseTransfer,
                    $stripeIntentCaptureRequestTransfer,
                    $paymentIntent->amount_received,
                );
            }

            // Only capture when in requires_capture state
            if ($paymentIntent->status !== SharedStripeConfig::PAYMENT_STATUS_REQUIRES_CAPTURE) {
                $stripeIntentCaptureResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_FAILED);

                $this->getLogger()->info(
                    sprintf('Payment Intent is not in a state that allows capture. Current status: `%s`', $paymentIntent->status),
                    [StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $transactionId],
                );

                return $stripeIntentCaptureResponseTransfer;
            }

            $captureParams = $stripeIntentCaptureRequestTransfer->getAmount()
                ? ['amount_to_capture' => $stripeIntentCaptureRequestTransfer->getAmount()]
                : null;

            $capturePaymentIntent = $stripeClient->paymentIntents->capture($transactionId, $captureParams);

            if (!$capturePaymentIntent->__isset('status') || $capturePaymentIntent->status !== SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED) {
                $stripeIntentCaptureResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_FAILED);

                $this->getLogger()->warning('Payment Intent capture failed.', [
                    StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $transactionId,
                ]);

                return $stripeIntentCaptureResponseTransfer;
            }

            // Capture accepted — final status will arrive via payment_intent.succeeded webhook
            $stripeIntentCaptureResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_REQUESTED);
            $stripeIntentCaptureResponseTransfer->setIsSuccessful(true);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $transactionId,
            ]);

            $stripeIntentCaptureResponseTransfer->setMessage($apiErrorException->getMessage());
            $stripeIntentCaptureResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_FAILED);
        }

        return $stripeIntentCaptureResponseTransfer;
    }

    public function cancel(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer
    {
        $stripeIntentResponseTransfer = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(false);

        $transactionId = $stripeIntentRequestTransfer->getTransactionIdOrFail();

        try {
            $stripeClient = $this->stripeClientFactory->create();
            $paymentIntent = $stripeClient->paymentIntents->retrieve($transactionId, ['expand' => ['payment_method']]);

            if ($paymentIntent->status === SharedStripeConfig::PAYMENT_STATUS_CANCELED) {
                $stripeIntentResponseTransfer->setIsSuccessful(true)
                    ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELED);

                $this->getLogger()->info('Payment Intent already canceled.', [
                    StripeIntentRequestTransfer::TRANSACTION_ID => $transactionId,
                ]);

                return $stripeIntentResponseTransfer;
            }

            if (!$this->paymentIntentCancellationGuard->canBeCanceled($paymentIntent)) {
                $stripeIntentResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELLATION_FAILED);

                $this->getLogger()->info(
                    sprintf('Payment Intent is not in a state that allows cancellation. Current status: `%s`', $paymentIntent->status),
                    [StripeIntentRequestTransfer::TRANSACTION_ID => $transactionId],
                );

                return $stripeIntentResponseTransfer;
            }

            $cancelPaymentIntent = $stripeClient->paymentIntents->cancel($transactionId);

            if (!$cancelPaymentIntent->__isset('status') || $cancelPaymentIntent->status !== SharedStripeConfig::PAYMENT_STATUS_CANCELED) {
                $stripeIntentResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELLATION_FAILED);

                $this->getLogger()->warning('Payment Intent cancellation failed.', [
                    StripeIntentRequestTransfer::TRANSACTION_ID => $transactionId,
                ]);

                return $stripeIntentResponseTransfer;
            }

            $stripeIntentResponseTransfer->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELED)
                ->setIsSuccessful(true);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                StripeIntentRequestTransfer::TRANSACTION_ID => $transactionId,
            ]);

            $stripeIntentResponseTransfer->setMessage($apiErrorException->getMessage())
                ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELLATION_FAILED);
        }

        return $stripeIntentResponseTransfer;
    }

    protected function handleAlreadySucceededCapture(
        StripeIntentCaptureResponseTransfer $stripeIntentCaptureResponseTransfer,
        StripeIntentCaptureRequestTransfer $stripeIntentCaptureRequestTransfer,
        int $amountReceived,
    ): StripeIntentCaptureResponseTransfer {
        // Guard against partial-capture race: if a prior capture already settled the PI
        // for less than the current item's requested amount, this item was never captured.
        // Returning success here would let it proceed to payout, causing a transfer failure.
        $requestedAmount = $stripeIntentCaptureRequestTransfer->getAmount();

        if ($requestedAmount !== null && $amountReceived < $requestedAmount) {
            $this->getLogger()->error('Payment Intent already succeeded with partial capture — requested amount not captured.', [
                StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $stripeIntentCaptureRequestTransfer->getTransactionId(),
                'amount_received' => $amountReceived,
                'requested_amount' => $requestedAmount,
            ]);

            return $stripeIntentCaptureResponseTransfer
                ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_FAILED)
                ->setMessage(sprintf(
                    'Partial capture: PaymentIntent already settled for %d, requested %d was not captured.',
                    $amountReceived,
                    $requestedAmount,
                ));
        }

        $this->getLogger()->info('Payment Intent already succeeded, capture is not applicable.');

        return $stripeIntentCaptureResponseTransfer
            ->setIsSuccessful(true)
            ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURED);
    }
}
