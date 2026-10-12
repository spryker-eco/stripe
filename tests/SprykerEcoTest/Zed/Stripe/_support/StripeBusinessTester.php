<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe;

use Codeception\Actor;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManager;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(\Codeception\SuiteManager::PHPMD)
 */
class StripeBusinessTester extends Actor
{
    use _generated\StripeBusinessTesterActions;

    public function haveStripePayment(SaveOrderTransfer $saveOrderTransfer, string $transactionId): StripePaymentTransfer
    {
        return (new StripeEntityManager())->createPayment(
            (new StripePaymentTransfer())
                ->setFkSalesOrder($saveOrderTransfer->getIdSalesOrderOrFail())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setTransactionId($transactionId),
        );
    }
}
