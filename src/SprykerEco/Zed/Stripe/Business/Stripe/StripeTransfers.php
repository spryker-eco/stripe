<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeTransmissionRequestTransfer;
use Generated\Shared\Transfer\StripeTransmissionResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Exception\ReverseTransferWithoutPreviousMadeTransferException;
use SprykerEco\Zed\Stripe\Business\Exception\StripeException;
use SprykerEco\Zed\Stripe\Business\Message\MessageBuilder;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Transfer;
use Stripe\TransferReversal;

class StripeTransfers
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    public function transfer(StripeTransmissionRequestTransfer $stripeTransmissionRequestTransfer): StripeTransmissionResponseTransfer
    {
        $stripeTransmissionResponseTransfer = new StripeTransmissionResponseTransfer();
        $stripeTransmissionResponseTransfer->setIsSuccessful(false);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $stripeTransfer = $this->makeTransfer($stripeTransmissionRequestTransfer, $stripeClient);

            $stripeTransmissionResponseTransfer->setIsSuccessful(true);
            $stripeTransmissionResponseTransfer->setTransferId($stripeTransfer->id);

            return $stripeTransmissionResponseTransfer;
        } catch (ApiErrorException | StripeException $exception) {
            $this->getLogger()->error($exception);

            $stripeTransmissionResponseTransfer->setMessage($exception->getMessage());
        }

        return $stripeTransmissionResponseTransfer;
    }

    /**
     * @throws \SprykerEco\Zed\Stripe\Business\Exception\ReverseTransferWithoutPreviousMadeTransferException
     */
    protected function makeTransfer(
        StripeTransmissionRequestTransfer $stripeTransmissionRequestTransfer,
        StripeClient $stripeClient,
    ): Transfer|TransferReversal {
        $transferData = [
            'amount' => $stripeTransmissionRequestTransfer->getAmountOrFail(),
            'currency' => $stripeTransmissionRequestTransfer->getCurrencyOrFail()->getCodeOrFail(),
            'destination' => $stripeTransmissionRequestTransfer->getDestinationOrFail(),
            'description' => $stripeTransmissionRequestTransfer->getDescriptionOrFail(),
            'source_transaction' => $stripeTransmissionRequestTransfer->getSourceTransactionOrFail(),
            'transfer_group' => $stripeTransmissionRequestTransfer->getTransferGroupOrFail(),
            'metadata' => $stripeTransmissionRequestTransfer->getMetadata(),
        ];

        if ($transferData['amount'] >= 0) {
            return $stripeClient->transfers->create($transferData);
        }

        if ($stripeTransmissionRequestTransfer->getTransferId() === null || $stripeTransmissionRequestTransfer->getTransferId() === '' || $stripeTransmissionRequestTransfer->getTransferId() === '0') {
            throw new ReverseTransferWithoutPreviousMadeTransferException(MessageBuilder::transferReversalDoesNotHaveAPreviousMadeTransfer());
        }

        $amount = abs($transferData['amount']);

        return $stripeClient->transfers->createReversal(
            $stripeTransmissionRequestTransfer->getTransferIdOrFail(),
            ['amount' => $amount],
        );
    }
}
