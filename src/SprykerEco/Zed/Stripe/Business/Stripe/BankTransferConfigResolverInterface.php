<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

interface BankTransferConfigResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getConfigForCountry(string $countryCode): array;
}
