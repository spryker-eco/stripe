<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe;

use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use Generated\Shared\Transfer\StripeCancelRequestTransfer;
use Generated\Shared\Transfer\StripeCancelResponseTransfer;
use Generated\Shared\Transfer\StripeCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeCaptureResponseTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsResponseTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use SprykerEco\Client\Kernel\AbstractClient;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerEco\Client\Stripe\StripeFactory getFactory()
 */
class StripeClient extends AbstractClient implements StripeClientInterface
{
    public function authorize(StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer): StripeAuthorizeResponseTransfer
    {
        return $this->getFactory()->createAuthorizeRequest()->request($stripeAuthorizeRequestTransfer);
    }

    public function capture(StripeCaptureRequestTransfer $stripeAuthorizeResponseTransfer): StripeCaptureResponseTransfer
    {
        return $this->getFactory()->createCaptureRequest()->request($stripeAuthorizeResponseTransfer);
    }

    public function cancel(StripeCancelRequestTransfer $stripeCancelRequestTransfer): StripeCancelResponseTransfer
    {
        return $this->getFactory()->createCancelRequest()->request($stripeCancelRequestTransfer);
    }

    public function getPaymentMethods(
        StripePaymentMethodsRequestTransfer $stripePaymentMethodsRequestTransfer,
    ): StripePaymentMethodsResponseTransfer {
        return $this->getFactory()->createPaymentMethodsRequest()->request($stripePaymentMethodsRequestTransfer);
    }

    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer {
        return $this->getFactory()->createZedStub()->processWebhook($webhookPayloadTransfer);
    }
}
