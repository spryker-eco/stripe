<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business;

use SprykerEco\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Zed\Stripe\Business\Notification\NotificationProcessor;
use SprykerEco\Zed\Stripe\Business\Notification\NotificationProcessorInterface;
use SprykerEco\Zed\Stripe\Business\Oms\Command\OmsCommandHandler;
use SprykerEco\Zed\Stripe\Business\Oms\Command\OmsCommandHandlerInterface;
use SprykerEco\Zed\Stripe\Business\Oms\Condition\OmsConditionChecker;
use SprykerEco\Zed\Stripe\Business\Oms\Condition\OmsConditionCheckerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentMethodFilter;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentMethodFilterInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReader;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentSaver;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentSaverInterface;
use SprykerEco\Zed\Stripe\StripeDependencyProvider;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeBusinessFactory extends AbstractBusinessFactory
{
    public function createPaymentSaver(): PaymentSaverInterface
    {
        return new PaymentSaver(
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createPaymentAuthorizer(): PaymentAuthorizerInterface
    {
        return new PaymentAuthorizer(
            $this->getStripeClient(),
            $this->createPaymentReader(),
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createPaymentMethodFilter(): PaymentMethodFilterInterface
    {
        return new PaymentMethodFilter(
            $this->getStripeClient(),
            $this->getConfig(),
        );
    }

    public function createPaymentReader(): PaymentReaderInterface
    {
        return new PaymentReader(
            $this->getRepository(),
        );
    }

    public function createOmsCommandHandler(): OmsCommandHandlerInterface
    {
        return new OmsCommandHandler(
            $this->getStripeClient(),
            $this->createPaymentReader(),
            $this->getEntityManager(),
        );
    }

    public function createOmsConditionChecker(): OmsConditionCheckerInterface
    {
        return new OmsConditionChecker(
            $this->createPaymentReader(),
            $this->getConfig(),
        );
    }

    public function createNotificationProcessor(): NotificationProcessorInterface
    {
        return new NotificationProcessor(
            $this->getEntityManager(),
        );
    }

    public function getStripeClient(): StripeClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_STRIPE);
    }
}
