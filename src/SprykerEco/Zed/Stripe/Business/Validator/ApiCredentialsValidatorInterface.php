<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Validator;

use Generated\Shared\Transfer\StripeApiCredentialsValidationTransfer;

interface ApiCredentialsValidatorInterface
{
    /**
     * @param array<string, string> $credentialsBySettingKey
     */
    public function validate(array $credentialsBySettingKey): StripeApiCredentialsValidationTransfer;
}
