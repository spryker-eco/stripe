<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe\Form\DataProvider;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\Kernel\Transfer\AbstractTransfer;
use SprykerEco\Yves\StepEngine\Dependency\Form\StepEngineFormDataProviderInterface;

class StripeInvoiceDataProvider implements StepEngineFormDataProviderInterface
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
     * @return array<string, mixed>
     */
    public function getOptions(AbstractTransfer $dataTransfer): array
    {
        return [];
    }
}
