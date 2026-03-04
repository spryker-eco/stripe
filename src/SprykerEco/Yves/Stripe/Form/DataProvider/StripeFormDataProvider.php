<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Form\DataProvider;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use Spryker\Yves\StepEngine\Dependency\Form\StepEngineFormDataProviderInterface;

class StripeFormDataProvider implements StepEngineFormDataProviderInterface
{
    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $dataTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function getData(AbstractTransfer $dataTransfer): QuoteTransfer
    {
        if ($dataTransfer->getPayment() === null) {
            $dataTransfer->setPayment(new PaymentTransfer());
        }

        return $dataTransfer;
    }

    /**
     * Returns view variables for the stripe.twig sub-form template.
     * `stripePublishableKey` and `stripeClientSecret` are populated by
     * StripeCheckoutPreConditionPlugin (Phase 12) which calls initializePayment()
     * and stores the result in the quote's additionalPaymentData.
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $dataTransfer
     *
     * @return array<string, mixed>
     */
    public function getOptions(AbstractTransfer $dataTransfer): array
    {
        $additionalPaymentData = (array)$dataTransfer->getPayment()?->getAdditionalPaymentData();

        return [
            'stripePublishableKey' => $additionalPaymentData['publishableKey'] ?? '',
            'stripeClientSecret' => $additionalPaymentData['clientSecret'] ?? '',
        ];
    }
}
