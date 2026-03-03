<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\Stripe;

use SprykerEco\Shared\Kernel\AbstractSharedConfig;

class StripeConfig extends AbstractSharedConfig
{
    public const string PAYMENT_METHOD_CREDIT_CARD = 'stripeCreditCard';

    public const string PAYMENT_METHOD_INVOICE = 'stripeInvoice';

    public const string PAYMENT_PROVIDER_NAME = 'Stripe';

    public const string OMS_PROCESS_LOCATION = APPLICATION_ROOT_DIR . '/vendor/spryker-community/stripe/config/Zed/oms';

    /**
     * Gets API key from environment configuration.
     *
     * @api
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->get(StripeConstants::API_KEY, '');
    }

    /**
     * Gets API secret from environment configuration.
     *
     * @api
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->get(StripeConstants::API_SECRET, '');
    }

    /**
     * Gets API base URL from environment configuration.
     *
     * @api
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->get(StripeConstants::API_BASE_URL, '');
    }

    /**
     * Gets API timeout in seconds.
     *
     * @api
     *
     * @return int
     */
    public function getApiTimeout(): int
    {
        return $this->get(StripeConstants::API_TIMEOUT, 30);
    }

    public function getAuthorizePath(): string
    {
        return $this->get(StripeConstants::API_AUTHORIZE_PATH, '/api/v1/authorize');
    }

    public function getCapturePath(): string
    {
        return $this->get(StripeConstants::API_CAPTURE_PATH, '/api/v1/capture');
    }

    public function getCancelPath(): string
    {
        return $this->get(StripeConstants::API_CANCEL_PATH, '/api/v1/cancel');
    }

    public function getPaymentMethodsPath(): string
    {
        return $this->get(StripeConstants::API_PAYMENT_METHODS_PATH, '/api/v1/payment-methods');
    }
}
