<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe;

use SprykerEco\Client\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerEco\Shared\Stripe\StripeConfig getSharedConfig()
 */
class StripeConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->getSharedConfig()->getApiKey();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->getSharedConfig()->getApiSecret();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->getSharedConfig()->getApiBaseUrl();
    }

    /**
     * @api
     *
     * @return int
     */
    public function getApiTimeout(): int
    {
        return $this->getSharedConfig()->getApiTimeout();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getAuthorizeUrl(): string
    {
        return $this->getApiBaseUrl() . $this->getSharedConfig()->getAuthorizePath();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getCaptureUrl(): string
    {
        return $this->getApiBaseUrl() . $this->getSharedConfig()->getCapturePath();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getCancelUrl(): string
    {
        return $this->getApiBaseUrl() . $this->getSharedConfig()->getCancelPath();
    }

    /**
     * @api
     *
     * @return string
     */
    public function getPaymentMethodsUrl(): string
    {
        return $this->getApiBaseUrl() . $this->getSharedConfig()->getPaymentMethodsPath();
    }

    /**
     * @api
     *
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array
    {
        return [
            'X-API-Key' => $this->getApiKey(),
            'Content-Type' => 'application/json',
        ];
    }
}
