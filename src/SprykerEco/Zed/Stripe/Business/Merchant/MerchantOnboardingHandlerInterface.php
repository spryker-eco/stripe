<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Stripe\Event;

interface MerchantOnboardingHandlerInterface
{
    public function handleAccountUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer;
}
