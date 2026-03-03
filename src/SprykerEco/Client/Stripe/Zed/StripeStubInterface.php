<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe\Zed;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface StripeStubInterface
{
    public function processWebhook(
        StripeWebhookPayloadTransfer $stripeWebhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer;
}
