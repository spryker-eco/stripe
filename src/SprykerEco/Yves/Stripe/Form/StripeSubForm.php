<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Form;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\StripeTransfer;
use Spryker\Yves\StepEngine\Dependency\Form\AbstractSubFormType;
use Spryker\Yves\StepEngine\Dependency\Form\SubFormInterface;
use Spryker\Yves\StepEngine\Dependency\Form\SubFormProviderNameInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeSubForm extends AbstractSubFormType implements SubFormInterface, SubFormProviderNameInterface
{
    public function getPropertyPath(): string
    {
        return PaymentTransfer::STRIPE;
    }

    public function getName(): string
    {
        return PaymentTransfer::STRIPE;
    }

    public function getProviderName(): string
    {
        return SharedStripeConfig::PAYMENT_PROVIDER_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StripeTransfer::class,
            SubFormInterface::OPTIONS_FIELD_NAME => [],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Payment details are collected on the dedicated Stripe payment page after order placement.
    }

    protected function getTemplatePath(): string
    {
        return SharedStripeConfig::PAYMENT_PROVIDER_NAME . DIRECTORY_SEPARATOR . 'stripe';
    }
}
