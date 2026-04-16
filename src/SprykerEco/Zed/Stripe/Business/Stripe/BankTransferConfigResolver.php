<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use SprykerEco\Zed\Stripe\Business\Stripe\Exception\UnsupportedCountryException;

class BankTransferConfigResolver implements BankTransferConfigResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getConfigForCountry(string $countryCode): array
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

    protected function getEUSupportedCountryForLocalizedIBAN(string $countryCode): string
    {
        return match ($countryCode) {
            'BE', 'DE', 'ES', 'FR', 'IE', 'NL' => $countryCode,
            default => 'DE',
        };
    }
}
