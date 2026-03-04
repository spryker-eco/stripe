<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandler;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingUrlGenerator;
use SprykerEco\Zed\Stripe\Business\Oms\Command\OmsCommandHandler;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCanceller;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCapturer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentInitializer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentMethodFilter;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReader;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentRefunder;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentSaver;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingRegistrar;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentFundsTransfer;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccountLinks;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccounts;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeCustomers;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeRefunds;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfers;
use SprykerEco\Zed\Stripe\Business\Webhook\WebhookHandler;
use SprykerEco\Zed\Stripe\StripeDependencyProvider;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeBusinessFactory extends AbstractBusinessFactory
{
    public function createWebhookHandler(): WebhookHandler
    {
        return new WebhookHandler(
            $this->getConfig(),
            $this->getPaymentAppFacade(),
            $this->createPaymentReader(),
            $this->createMerchantOnboardingHandler(),
        );
    }

    public function createPaymentInitializer(): PaymentInitializer
    {
        return new PaymentInitializer(
            $this->createStripeIntents(),
        );
    }

    public function createPaymentSaver(): PaymentSaver
    {
        return new PaymentSaver(
            $this->getEntityManager(),
            $this->getConfig(),
        );
    }

    public function createOmsCommandHandler(): OmsCommandHandler
    {
        return new OmsCommandHandler(
            $this->createPaymentAuthorizer(),
            $this->createPaymentCapturer(),
            $this->createPaymentCanceller(),
            $this->createPaymentRefunder(),
        );
    }

    public function createPaymentMethodFilter(): PaymentMethodFilter
    {
        return new PaymentMethodFilter();
    }

    public function createMerchantOnboardingUrlGenerator(): MerchantOnboardingUrlGenerator
    {
        return new MerchantOnboardingUrlGenerator(
            $this->createStripeAccounts(),
            $this->createStripeAccountLinks(),
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createPaymentReader(): PaymentReader
    {
        return new PaymentReader(
            $this->getRepository(),
        );
    }

    protected function createPaymentAuthorizer(): PaymentAuthorizer
    {
        return new PaymentAuthorizer(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
        );
    }

    protected function createPaymentCapturer(): PaymentCapturer
    {
        return new PaymentCapturer(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
        );
    }

    protected function createPaymentCanceller(): PaymentCanceller
    {
        return new PaymentCanceller(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
        );
    }

    protected function createPaymentRefunder(): PaymentRefunder
    {
        return new PaymentRefunder(
            $this->createStripeRefunds(),
            $this->createPaymentReader(),
        );
    }

    protected function createMerchantOnboardingHandler(): MerchantOnboardingHandler
    {
        return new MerchantOnboardingHandler(
            $this->getMerchantAppFacade(),
            $this->getEntityManager(),
        );
    }

    protected function createStripeIntents(): StripeIntents
    {
        return new StripeIntents(
            $this->createStripeClientFactory(),
            $this->createStripeCustomers(),
            $this->getConfig(),
        );
    }

    protected function createStripeRefunds(): StripeRefunds
    {
        return new StripeRefunds(
            $this->createStripeClientFactory(),
        );
    }

    protected function createStripeCustomers(): StripeCustomers
    {
        return new StripeCustomers(
            $this->createStripeClientFactory(),
        );
    }

    protected function createStripeAccounts(): StripeAccounts
    {
        return new StripeAccounts(
            $this->createStripeClientFactory(),
        );
    }

    protected function createStripeAccountLinks(): StripeAccountLinks
    {
        return new StripeAccountLinks(
            $this->createStripeClientFactory(),
        );
    }

    protected function createStripeClientFactory(): StripeClientFactory
    {
        return new StripeClientFactory(
            $this->getConfig(),
        );
    }

    public function getPaymentAppFacade(): PaymentAppFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_PAYMENT_APP);
    }

    public function createMerchantOnboardingRegistrar(): MerchantOnboardingRegistrar
    {
        return new MerchantOnboardingRegistrar(
            $this->getMerchantAppFacade(),
            $this->getConfig(),
        );
    }

    public function createPaymentFundsTransfer(): PaymentFundsTransfer
    {
        return new PaymentFundsTransfer(
            $this->createStripeTransfers(),
            $this->createPaymentReader(),
            $this->getRepository(),
        );
    }

    protected function createStripeTransfers(): StripeTransfers
    {
        return new StripeTransfers(
            $this->createStripeClientFactory(),
        );
    }

    public function getMerchantAppFacade(): MerchantAppFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_MERCHANT_APP);
    }
}
