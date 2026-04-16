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
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Stripe\Transfer;
use Stripe\TransferReversal;

class StripeTransfers implements StripeTransfersInterface
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
            'transfer_group' => $stripeTransmissionRequestTransfer->getTransferGroupOrFail(),
            'metadata' => $stripeTransmissionRequestTransfer->getMetadata(),
        ];

        if ($stripeTransmissionRequestTransfer->getSourceTransaction() !== null) {
            $transferData['source_transaction'] = $stripeTransmissionRequestTransfer->getSourceTransaction();
        }

        if ((int)$transferData['amount'] >= 0) {
            try {
                return $stripeClient->transfers->create($transferData);
            } catch (InvalidRequestException $invalidRequestException) {
                if ($invalidRequestException->getStripeParam() !== 'source_transaction') {
                    throw $invalidRequestException;
                }

                // source_transaction currency must match the charge's balance_transaction currency (e.g. EUR for a
                // German platform account), but the transfer currency is the order currency (e.g. CHF). Stripe will
                // use the platform's available balance and convert to the destination currency instead.
                $this->getLogger()->warning(sprintf(
                    'Transfer with source_transaction failed due to currency mismatch, retrying without source_transaction: %s',
                    $invalidRequestException->getMessage(),
                ));

                unset($transferData['source_transaction']);

                return $stripeClient->transfers->create($transferData);
            }
        }

        if ($stripeTransmissionRequestTransfer->getTransferId() === null || $stripeTransmissionRequestTransfer->getTransferId() === '' || $stripeTransmissionRequestTransfer->getTransferId() === '0') {
            throw new ReverseTransferWithoutPreviousMadeTransferException(MessageBuilder::transferReversalDoesNotHaveAPreviousMadeTransfer());
        }

        $amount = abs((int)$transferData['amount']);

        return $stripeClient->transfers->createReversal(
            $stripeTransmissionRequestTransfer->getTransferIdOrFail(),
            ['amount' => $amount],
        );
    }
}
