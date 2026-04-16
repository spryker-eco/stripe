<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeCustomerResponseTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\Exception\UnsupportedCountryException;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentIntentParamsBuilder implements PaymentIntentParamsBuilderInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeConfig $config,
        protected BankTransferConfigResolverInterface $bankTransferConfigResolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(
        QuoteTransfer $quoteTransfer,
        StripeCustomerResponseTransfer $stripeCustomerResponseTransfer,
        StripeIntentRequestTransfer $stripeIntentRequestTransfer,
    ): array {
        $params = $this->buildBaseParams($quoteTransfer, $stripeCustomerResponseTransfer);
        $params = $this->addBankTransferOption($quoteTransfer, $stripeIntentRequestTransfer, $params);

        return $this->addMetadata($quoteTransfer, $params);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildBaseParams(
        QuoteTransfer $quoteTransfer,
        StripeCustomerResponseTransfer $stripeCustomerResponseTransfer,
    ): array {
        $description = $quoteTransfer->getOrderReference()
            ? sprintf('Order Reference: %s', $quoteTransfer->getOrderReference())
            : 'Pre-Order Payment';

        $params = [
            'amount' => $quoteTransfer->getTotals()?->getGrandTotal(),
            'currency' => $quoteTransfer->getCurrency()?->getCode(),
            'description' => $description,
            'automatic_payment_methods' => ['enabled' => true],
            'capture_method' => 'automatic',
        ];

        $params = $this->addShippingAddress($quoteTransfer, $params);

        if ($stripeCustomerResponseTransfer->getIsSuccessful() && $stripeCustomerResponseTransfer->getCustomerId()) {
            $params['customer'] = $stripeCustomerResponseTransfer->getCustomerId();
        }

        $params['payment_method_options'] = [
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

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function addShippingAddress(QuoteTransfer $quoteTransfer, array $params): array
    {
        $shippingAddress = $quoteTransfer->getShippingAddress();
        $billingAddress = $quoteTransfer->getBillingAddress();
        $zip = $shippingAddress?->getZipCode() ?? $billingAddress?->getZipCode();
        $city = $shippingAddress?->getCity() ?? $billingAddress?->getCity();
        $address1 = $shippingAddress?->getAddress1() ?? $billingAddress?->getAddress1();

        if ($zip === null || $city === null || $address1 === null) {
            return $params;
        }

        $firstName = $shippingAddress?->getFirstName() ?? $billingAddress?->getFirstName() ?? $quoteTransfer->getCustomer()?->getFirstName();
        $lastName = $shippingAddress?->getLastName() ?? $billingAddress?->getLastName() ?? $quoteTransfer->getCustomer()?->getLastName();
        $countryIso = $shippingAddress?->getCountry()?->getIso2Code() ?? $billingAddress?->getCountry()?->getIso2Code();

        $params['shipping'] = [
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

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function addBankTransferOption(
        QuoteTransfer $quoteTransfer,
        StripeIntentRequestTransfer $stripeIntentRequestTransfer,
        array $params,
    ): array {
        $currencyCode = $quoteTransfer->getCurrency()?->getCode();
        $shippingAddress = $quoteTransfer->getShippingAddress();
        $billingAddress = $quoteTransfer->getBillingAddress();
        $countryCode = $shippingAddress?->getCountry()?->getIso2Code() ?? $billingAddress?->getCountry()?->getIso2Code();

        // Bank transfer only supported for specific currencies
        if (!in_array($currencyCode, ['EUR', 'USD', 'GBP', 'MXN', 'JPY'])) {
            return $params;
        }

        try {
            $bankTransferConfig = $this->bankTransferConfigResolver->getConfigForCountry($countryCode ?? '');

            $supportedCurrencies = [
                'gb_bank_transfer' => ['GBP'],
                'eu_bank_transfer' => ['EUR'],
                'us_bank_transfer' => ['USD'],
                'jp_bank_transfer' => ['JPY'],
                'mx_bank_transfer' => ['MXN'],
            ];

            if (!in_array(strtoupper($currencyCode ?? ''), $supportedCurrencies[$bankTransferConfig['type']], true)) {
                return $params;
            }

            $params['payment_method_options']['customer_balance'] = [
                'funding_type' => 'bank_transfer',
                'bank_transfer' => $bankTransferConfig,
            ];
        } catch (UnsupportedCountryException) {
            $this->getLogger()->error('Unsupported country for bank transfer payment method.', [
                StripeIntentCaptureRequestTransfer::TRANSACTION_ID => $stripeIntentRequestTransfer->getTransactionId(),
                'country_code' => $countryCode,
            ]);
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function addMetadata(QuoteTransfer $quoteTransfer, array $params): array
    {
        $params['metadata'] = [];

        if ($quoteTransfer->getOrderReference()) {
            $params['metadata'][StripeConfig::METADATA_ORDER_REFERENCE] = $quoteTransfer->getOrderReference();
        }

        $additionalPaymentData = $quoteTransfer->getPayment()?->getAdditionalPaymentData();
        if ($additionalPaymentData) {
            $params['metadata'] = array_merge(
                $params['metadata'],
                $this->truncateMetadata($additionalPaymentData),
            );
        }

        return $params;
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
