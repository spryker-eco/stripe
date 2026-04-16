<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\SalesPaymentMerchant;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransmissionResponseCollectionTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesPaymentMerchantExtension\Communication\Dependency\Plugin\MerchantPayoutTransmissionPluginInterface;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 */
class StripePayoutTransmissionPlugin extends AbstractPlugin implements MerchantPayoutTransmissionPluginInterface
{
    /**
     * {@inheritDoc}
     * - Replaces the default HTTP-based PSP App transfer endpoint with direct Stripe API calls.
     * - All merchant payouts and reversals are executed via Stripe Connect transfers instead of the PSP App internal API.
     *
     * @api
     *
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $paymentTransmissionItemTransfers
     */
    public function executePayoutTransmission(
        array $paymentTransmissionItemTransfers,
        OrderTransfer $orderTransfer,
    ): PaymentTransmissionResponseCollectionTransfer {
        return $this->getFacade()->executePayoutTransmission(
            $paymentTransmissionItemTransfers,
            $orderTransfer,
        );
    }
}
