<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Webhook;

use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Refund;

interface StripeEventDetailsExtractorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function extractPaymentIntentDetails(PaymentIntent $paymentIntent): array;

    /**
     * @return array<string, mixed>
     */
    public function extractChargeDetails(Charge $charge): array;

    /**
     * @return array<string, mixed>
     */
    public function extractRefundDetails(Refund $refund): array;
}
