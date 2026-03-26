<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeCustomerRequestTransfer;
use Generated\Shared\Transfer\StripeCustomerResponseTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureResponseTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Stripe\Exception\UnsupportedCountryException;
use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\ExceptionInterface;
use Stripe\PaymentIntent;

class StripeIntents implements StripeIntentsInterface
{
    use LoggerTrait;

    protected const string PAYMENT_METHOD_TYPE_US_BANK_ACCOUNT = 'us_bank_account';

    public function __construct(
        protected StripeClientFactory $stripeClientFactory,
        protected StripeCustomersInterface $stripeCustomers,
        protected StripeConfig $config,
        protected SharedStripeConfig $sharedConfig,
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

            $paymentIntentParams = $this->createPaymentIntentParams($quoteTransfer, $stripeCustomerResponseTransfer, $stripeIntentRequestTransfer);
            $paymentIntentParams = $this->addMetadata($quoteTransfer, $paymentIntentParams);

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

    /**
     * {@inheritDoc}
     */
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

            if (!$this->canPaymentIntentBeCanceled($paymentIntent)) {
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

    protected function canPaymentIntentBeCanceled(PaymentIntent $paymentIntent): bool
    {
        if (in_array($paymentIntent->status, $this->sharedConfig->getPaymentIntentNonCancellableStatuses(), true)) {
            return false;
        }

        if (in_array($paymentIntent->status, $this->sharedConfig->getPaymentIntentCancellableStatuses(), true)) {
            return true;
        }

        // ACH (us_bank_account) PaymentIntents in processing state can still be canceled
        return $paymentIntent->status === SharedStripeConfig::PAYMENT_STATUS_PROCESSING
            && $this->isUsBankAccountPaymentMethod($paymentIntent);
    }

    protected function isUsBankAccountPaymentMethod(PaymentIntent $paymentIntent): bool
    {
        if (!$paymentIntent->__isset('payment_method')) {
            return false;
        }

        $paymentMethod = $paymentIntent->payment_method;

        return is_object($paymentMethod) && $paymentMethod->type === static::PAYMENT_METHOD_TYPE_US_BANK_ACCOUNT;
    }

    /**
     * @param array<string, mixed> $paymentIntentParams
     *
     * @return array<string, mixed>
     */
    protected function addMetadata(QuoteTransfer $quoteTransfer, array $paymentIntentParams): array
    {
        $paymentIntentParams['metadata'] = [];

        if ($quoteTransfer->getOrderReference()) {
            $paymentIntentParams['metadata'][StripeConfig::METADATA_ORDER_REFERENCE] = $quoteTransfer->getOrderReference();
        }

        $additionalPaymentData = $quoteTransfer->getPayment()?->getAdditionalPaymentData();
        if ($additionalPaymentData) {
            $paymentIntentParams['metadata'] = array_merge(
                $paymentIntentParams['metadata'],
                $this->truncateMetadata($additionalPaymentData),
            );
        }

        return $paymentIntentParams;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createPaymentIntentParams(
        QuoteTransfer $quoteTransfer,
        StripeCustomerResponseTransfer $stripeCustomerResponseTransfer,
        StripeIntentRequestTransfer $stripeIntentRequestTransfer
    ): array {
        $description = $quoteTransfer->getOrderReference()
            ? sprintf('Order Reference: %s', $quoteTransfer->getOrderReference())
            : 'Pre-Order Payment';

        $config = [
            'amount' => $quoteTransfer->getTotals()?->getGrandTotal(),
            'currency' => $quoteTransfer->getCurrency()?->getCode(),
            'description' => $description,
            'automatic_payment_methods' => ['enabled' => true],
            'capture_method' => 'automatic',
        ];

        $shippingAddress = $quoteTransfer->getShippingAddress();
        $billingAddress = $quoteTransfer->getBillingAddress();
        $zip = $shippingAddress?->getZipCode() ?? $billingAddress?->getZipCode();
        $city = $shippingAddress?->getCity() ?? $billingAddress?->getCity();
        $address1 = $shippingAddress?->getAddress1() ?? $billingAddress?->getAddress1();

        if ($zip !== null && $city !== null && $address1 !== null) {
            $firstName = $shippingAddress?->getFirstName() ?? $billingAddress?->getFirstName() ?? $quoteTransfer->getCustomer()?->getFirstName();
            $lastName = $shippingAddress?->getLastName() ?? $billingAddress?->getLastName() ?? $quoteTransfer->getCustomer()?->getLastName();
            $countryIso = $shippingAddress?->getCountry()?->getIso2Code() ?? $billingAddress?->getCountry()?->getIso2Code();

            $config['shipping'] = [
                'name' => trim(($firstName ?? '') . ' ' . ($lastName ?? '')),
                'address' => [
                    'city' => $city,
                    'country' => $countryIso,
                    'line1' => $address1,
                    'line2' => $shippingAddress?->getAddress2() ?? $billingAddress?->getAddress2(),
                    'postal_code' => $zip,
                    'state' => $shippingAddress?->getState() ?? $billingAddress?->getState(),
                ],
                'phone' => $shippingAddress?->getPhone() ?? $billingAddress?->getPhone(),
            ];
        }

        if ($stripeCustomerResponseTransfer->getIsSuccessful() && $stripeCustomerResponseTransfer->getCustomerId()) {
            $config['customer'] = $stripeCustomerResponseTransfer->getCustomerId();
        }

        $config['payment_method_options'] = [
            'affirm' => ['capture_method' => 'manual'],
            'afterpay_clearpay' => ['capture_method' => 'manual'],
            'amazon_pay' => ['capture_method' => 'manual'],
            'card' => ['capture_method' => 'manual'],
            'card_present' => ['capture_method' => 'manual_preferred'],
            'cashapp' => ['capture_method' => 'manual'],
            'klarna' => ['capture_method' => 'manual'],
            'link' => ['capture_method' => 'manual'],
            'mobilepay' => ['capture_method' => 'manual'],
            'paypal' => ['capture_method' => 'manual'],
            'revolut_pay' => ['capture_method' => 'manual'],
        ];

        $currencyCode = $quoteTransfer->getCurrency()?->getCode();
        $countryCode = $shippingAddress?->getCountry()?->getIso2Code() ?? $billingAddress?->getCountry()?->getIso2Code();

        // Bank transfer only supported for specific currencies
        if (!in_array($currencyCode, ['EUR', 'USD', 'GBP', 'MXN', 'JPY'])) {
            return $config;
        }

        try {
            $bankTransferConfig = $this->getBankTransferConfigurationForRegion($countryCode ?? '');

            $supportedCurrencies = [
                'gb_bank_transfer' => ['GBP'],
                'eu_bank_transfer' => ['EUR'],
                'us_bank_transfer' => ['USD'],
                'jp_bank_transfer' => ['JPY'],
                'mx_bank_transfer' => ['MXN'],
            ];

            if (!in_array(strtoupper($currencyCode ?? ''), $supportedCurrencies[$bankTransferConfig['type']], true)) {
                return $config;
            }

            $config['payment_method_options']['customer_balance'] = [
                'funding_type' => 'bank_transfer',
                'bank_transfer' => $bankTransferConfig,
            ];
        } catch (UnsupportedCountryException) {
            $this->getLogger()->error('Unsupported country for bank transfer payment method.', [
                StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $stripeIntentRequestTransfer->getTransactionId(),
                'country_code' => $countryCode,
            ]);
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getBankTransferConfigurationForRegion(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $region = $this->getRegionFromCountry($countryCode);

        return match ($region) {
            'gb', 'us', 'mx', 'jp' => ['type' => sprintf('%s_bank_transfer', $region)],
            default => [
                'type' => 'eu_bank_transfer',
                'eu_bank_transfer' => ['country' => $this->getEUSupportedCountryForLocalizedIBAN($countryCode)],
            ],
        };
    }

    /**
     * @throws \SprykerEco\Zed\Stripe\Business\Stripe\Exception\UnsupportedCountryException
     */
    protected function getRegionFromCountry(string $countryCode): string
    {
        return match ($countryCode) {
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'CH' => 'eu',
            'GB' => 'gb',
            'US' => 'us',
            'MX' => 'mx',
            'JP' => 'jp',
            default => throw new UnsupportedCountryException(sprintf('Country code not supported: %s', $countryCode)),
        };
    }

    /**
     * IBAN is localized to one of BE, DE, ES, FR, IE or NL.
     *
     * @see https://stripe.com/docs/payments/bank-transfers/accept-a-payment?platform=web&country=eu&invoices=without#element-create-payment-intent
     */
    protected function getEUSupportedCountryForLocalizedIBAN(string $countryCode): string
    {
        return match ($countryCode) {
            'BE', 'DE', 'ES', 'FR', 'IE', 'NL' => $countryCode,
            default => 'DE',
        };
    }

    /**
     * @param array<string|int, mixed> $additionalPaymentData
     *
     * @return array<string|int, mixed>
     */
    protected function truncateMetadata(array $additionalPaymentData): array
    {
        $truncatedMetadata = [];
        $additionalPaymentData = array_slice($additionalPaymentData, 0, 50);

        foreach ($additionalPaymentData as $key => $value) {
            if (is_string($key)) {
                if (mb_strlen($key) > 40) {
                    $key = mb_substr($key, 0, 40);
                }
                $key = str_replace(['[', ']'], '', $key);
            }

            if (is_string($value) && mb_strlen($value) > 500) {
                $value = mb_substr($value, 0, 500);
            }

            $truncatedMetadata[$key] = $value;
        }

        return $truncatedMetadata;
    }
}
