<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureResponseTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;

interface StripeIntentsInterface
{
    public function create(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer;

    public function get(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer;

    public function capture(StripeIntentCaptureRequestTransfer $stripeIntentCaptureRequestTransfer): StripeIntentCaptureResponseTransfer;

    public function cancel(StripeIntentRequestTransfer $stripeIntentRequestTransfer): StripeIntentResponseTransfer;
}
