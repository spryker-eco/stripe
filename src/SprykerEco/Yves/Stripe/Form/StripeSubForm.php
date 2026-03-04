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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeSubForm extends AbstractSubFormType implements SubFormInterface, SubFormProviderNameInterface
{
    protected const string FIELD_TRANSACTION_ID = 'transactionId';

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
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTransactionIdField($builder);
    }

    protected function addTransactionIdField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_TRANSACTION_ID, HiddenType::class, [
            'required' => false,
        ]);

        return $this;
    }

    protected function getTemplatePath(): string
    {
        return SharedStripeConfig::PAYMENT_PROVIDER_NAME . DIRECTORY_SEPARATOR . 'stripe';
    }
}
