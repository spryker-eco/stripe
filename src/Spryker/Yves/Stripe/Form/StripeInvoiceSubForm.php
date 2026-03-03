<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe\Form;

use Generated\Shared\Transfer\StripeInvoiceTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use SprykerEco\Yves\StepEngine\Dependency\Form\AbstractSubFormType;
use SprykerEco\Yves\StepEngine\Dependency\Form\SubFormInterface;
use SprykerEco\Yves\StepEngine\Dependency\Form\SubFormProviderNameInterface;
use SprykerEco\Shared\Stripe\StripeConfig;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeInvoiceSubForm extends AbstractSubFormType implements SubFormInterface, SubFormProviderNameInterface
{
    protected const string FIELD_PAYMENT_METHOD_TOKEN = 'paymentMethodToken';

    protected const string INVOICE = 'invoice';

    public function getPropertyPath(): string
    {
        return PaymentTransfer::STRIPE_INVOICE;
    }

    public function getName(): string
    {
        return PaymentTransfer::STRIPE_INVOICE;
    }

    public function getProviderName(): string
    {
        return StripeConfig::PAYMENT_PROVIDER_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StripeInvoiceTransfer::class,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addPaymentMethodTokenField($builder);
    }

    protected function addPaymentMethodTokenField(FormBuilderInterface $builder): self
    {
        $builder->add(
            static::FIELD_PAYMENT_METHOD_TOKEN,
            HiddenType::class,
            [
                'label' => 'Payment Method Token',
                'required' => true,
                'constraints' => [
                    new Blank(),
                ],
                'attr' => [
                    'placeholder' => 'Token from payment provider SDK',
                ],
            ],
        );

        return $this;
    }

    protected function getTemplatePath(): string
    {
        return StripeConfig::PAYMENT_PROVIDER_NAME . DIRECTORY_SEPARATOR . static::INVOICE;
    }
}
