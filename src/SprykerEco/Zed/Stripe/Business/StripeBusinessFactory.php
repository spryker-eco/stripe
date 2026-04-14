<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use Spryker\Zed\SalesPaymentDetail\Business\SalesPaymentDetailFacadeInterface;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Dashboard\DashboardUrlGenerator;
use SprykerEco\Zed\Stripe\Business\Executor\CheckoutPostSaveExecutor;
use SprykerEco\Zed\Stripe\Business\Executor\CheckoutPostSaveExecutorInterface;
use SprykerEco\Zed\Stripe\Business\Handler\CredentialsPreSaveHandler;
use SprykerEco\Zed\Stripe\Business\Handler\CredentialsPreSaveHandlerInterface;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandler;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingRegistrator;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingUrlGenerator;
use SprykerEco\Zed\Stripe\Business\Oms\Command\OmsCommandHandler;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCanceller;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCapturer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentDetailsResolver;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentInitializer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReader;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentRefunder;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentSaver;
use SprykerEco\Zed\Stripe\Business\Payment\PayoutTransmissionExecutor;
use SprykerEco\Zed\Stripe\Business\Payment\PayoutTransmissionExecutorInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\BankTransferConfigResolver;
use SprykerEco\Zed\Stripe\Business\Stripe\PaymentIntentCancellationGuard;
use SprykerEco\Zed\Stripe\Business\Stripe\PaymentIntentParamsBuilder;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccountLinks;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccounts;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeCustomers;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeLoginLinks;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeRefunds;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfers;
use SprykerEco\Zed\Stripe\Business\Validator\ApiCredentialsValidator;
use SprykerEco\Zed\Stripe\Business\Validator\ApiCredentialsValidatorInterface;
use SprykerEco\Zed\Stripe\Business\Validator\StripeConnectionChecker;
use SprykerEco\Zed\Stripe\Business\Validator\StripeConnectionCheckerInterface;
use SprykerEco\Zed\Stripe\Business\Webhook\StripeEventDetailsExtractor;
use SprykerEco\Zed\Stripe\Business\Webhook\WebhookHandler;
use SprykerEco\Zed\Stripe\StripeDependencyProvider;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeBusinessFactory extends AbstractBusinessFactory
{
    public function createCredentialsPreSaveHandler(): CredentialsPreSaveHandlerInterface
    {
        return new CredentialsPreSaveHandler(
            $this->getConfig(),
            $this->createApiCredentialsValidator(),
        );
    }

    public function createApiCredentialsValidator(): ApiCredentialsValidatorInterface
    {
        return new ApiCredentialsValidator(
            $this->createStripeConnectionChecker(),
        );
    }

    public function createStripeConnectionChecker(): StripeConnectionCheckerInterface
    {
        return new StripeConnectionChecker(
            $this->createStripeClientFactory(),
        );
    }

    public function createWebhookHandler(): WebhookHandler
    {
        return new WebhookHandler(
            $this->getConfig(),
            $this->getPaymentAppFacade(),
            $this->createPaymentReader(),
            $this->createMerchantOnboardingHandler(),
            $this->getSalesPaymentDetailFacade(),
            $this->createStripeEventDetailsExtractor(),
        );
    }

    public function createPaymentInitializer(): PaymentInitializer
    {
        return new PaymentInitializer(
            $this->createStripeIntents(),
            $this->getConfig(),
        );
    }

    public function createPaymentSaver(): PaymentSaver
    {
        return new PaymentSaver(
            $this->getEntityManager(),
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

    public function createPaymentDetailsResolver(): PaymentDetailsResolver
    {
        return new PaymentDetailsResolver(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
            $this->getConfig(),
        );
    }

    public function createPaymentAuthorizer(): PaymentAuthorizer
    {
        return new PaymentAuthorizer(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
        );
    }

    public function createPaymentCapturer(): PaymentCapturer
    {
        return new PaymentCapturer(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
            $this->getPaymentAppFacade(),
        );
    }

    public function createPaymentCanceller(): PaymentCanceller
    {
        return new PaymentCanceller(
            $this->createStripeIntents(),
            $this->createPaymentReader(),
            $this->getPaymentAppFacade(),
            $this->getSalesPaymentDetailFacade(),
        );
    }

    public function createPaymentRefunder(): PaymentRefunder
    {
        return new PaymentRefunder(
            $this->createStripeRefunds(),
            $this->createPaymentReader(),
        );
    }

    public function createMerchantOnboardingHandler(): MerchantOnboardingHandler
    {
        return new MerchantOnboardingHandler(
            $this->getMerchantAppFacade(),
            $this->getEntityManager(),
        );
    }

    public function createStripeIntents(): StripeIntents
    {
        return new StripeIntents(
            $this->createStripeClientFactory(),
            $this->createStripeCustomers(),
            $this->createPaymentIntentParamsBuilder(),
            $this->createPaymentIntentCancellationGuard(),
        );
    }

    public function createPaymentIntentParamsBuilder(): PaymentIntentParamsBuilder
    {
        return new PaymentIntentParamsBuilder(
            $this->getConfig(),
            $this->createBankTransferConfigResolver(),
        );
    }

    public function createBankTransferConfigResolver(): BankTransferConfigResolver
    {
        return new BankTransferConfigResolver();
    }

    public function createPaymentIntentCancellationGuard(): PaymentIntentCancellationGuard
    {
        return new PaymentIntentCancellationGuard();
    }

    public function createStripeRefunds(): StripeRefunds
    {
        return new StripeRefunds(
            $this->createStripeClientFactory(),
            $this->getUtilEncodingService(),
        );
    }

    public function createStripeCustomers(): StripeCustomers
    {
        return new StripeCustomers(
            $this->createStripeClientFactory(),
        );
    }

    public function createStripeAccounts(): StripeAccounts
    {
        return new StripeAccounts(
            $this->createStripeClientFactory(),
        );
    }

    public function createStripeAccountLinks(): StripeAccountLinks
    {
        return new StripeAccountLinks(
            $this->createStripeClientFactory(),
        );
    }

    public function createStripeEventDetailsExtractor(): StripeEventDetailsExtractor
    {
        return new StripeEventDetailsExtractor(
            $this->createStripeClientFactory(),
        );
    }

    public function createStripeClientFactory(): StripeClientFactory
    {
        return new StripeClientFactory(
            $this->getConfig(),
        );
    }

    public function getPaymentAppFacade(): PaymentAppFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_PAYMENT_APP);
    }

    public function createMerchantOnboardingRegistrator(): MerchantOnboardingRegistrator
    {
        return new MerchantOnboardingRegistrator(
            $this->getMerchantAppFacade(),
            $this->getConfig(),
        );
    }

    public function createPayoutTransmissionExecutor(): PayoutTransmissionExecutorInterface
    {
        return new PayoutTransmissionExecutor(
            $this->createStripeTransfers(),
            $this->createStripeIntents(),
            $this->createPaymentReader(),
            $this->getRepository(),
        );
    }

    public function createStripeTransfers(): StripeTransfers
    {
        return new StripeTransfers(
            $this->createStripeClientFactory(),
        );
    }

    public function createDashboardUrlGenerator(): DashboardUrlGenerator
    {
        return new DashboardUrlGenerator(
            $this->getRepository(),
            $this->createStripeLoginLinks(),
        );
    }

    public function createStripeLoginLinks(): StripeLoginLinks
    {
        return new StripeLoginLinks(
            $this->createStripeClientFactory(),
        );
    }

    public function createCheckoutPostSaveExecutor(): CheckoutPostSaveExecutorInterface
    {
        return new CheckoutPostSaveExecutor(
            $this->createPaymentInitializer(),
            $this->createPaymentSaver(),
            $this->getConfig(),
        );
    }

    public function getMerchantAppFacade(): MerchantAppFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_MERCHANT_APP);
    }

    public function getSalesPaymentDetailFacade(): SalesPaymentDetailFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_SALES_PAYMENT_DETAIL);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
