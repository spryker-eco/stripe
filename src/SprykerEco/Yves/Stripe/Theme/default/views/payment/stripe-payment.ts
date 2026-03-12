import Component from 'ShopUi/models/component';

declare function Stripe(publishableKey: string): StripeInstance;

interface StripeInstance {
    elements(options: { clientSecret: string; appearance: object }): StripeElements;
    confirmPayment(options: {
        elements: StripeElements;
        confirmParams: { return_url: string };
    }): Promise<{ error?: { message: string } }>;
}

interface StripeElements {
    create(type: string): StripeElement;
}

interface StripeElement {
    mount(target: string | HTMLElement): void;
}

export default class StripePayment extends Component {
    protected paymentElementContainer: HTMLElement;
    protected loader: HTMLElement;
    protected messageContainer: HTMLElement;
    protected submitButton: HTMLButtonElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.paymentElementContainer = this.getElementsByClassName(`${this.jsName}__payment-element`)[0] as HTMLElement;
        this.loader = this.getElementsByClassName(`${this.jsName}__loader`)[0] as HTMLElement;
        this.messageContainer = this.getElementsByClassName(`${this.jsName}__message`)[0] as HTMLElement;
        this.submitButton = this.getElementsByClassName(`${this.jsName}__submit`)[0] as HTMLButtonElement;

        const publishableKey = this.dataset.publishableKey ?? '';
        const clientSecret = this.dataset.clientSecret ?? '';
        const checkoutSuccessUrl = this.dataset.checkoutSuccessUrl ?? '';
        const paymentPageUrl = this.dataset.paymentPageUrl ?? '';
        const errorFailed = this.dataset.errorFailed ?? '';

        if (!publishableKey || !clientSecret) {
            console.error('[Stripe] Missing publishableKey or clientSecret.');
            return;
        }

        this.initStripe(publishableKey, clientSecret, checkoutSuccessUrl, paymentPageUrl, errorFailed);
    }

    protected initStripe(
        publishableKey: string,
        clientSecret: string,
        checkoutSuccessUrl: string,
        paymentPageUrl: string,
        errorFailed: string,
    ): void {
        const stripe = Stripe(publishableKey);
        // see https://docs.stripe.com/elements/appearance-api
        const elements = stripe.elements({
            clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#1479bd',
                    colorText: '#333333',
                    colorDanger: '#df1b41',
                    fontFamily: 'Arial, sans-serif',
                    borderRadius: '0px',
                    spacingUnit: '4px',
                },
                rules: {
                    '.Input': {
                        border: '1px solid #dadada',
                        boxShadow: 'none',
                    },
                    '.Input:focus': {
                        border: '1px solid #1479bd',
                        boxShadow: 'none',
                        outline: 'none',
                    },
                    '.Tab': {
                        border: '1px solid #dadada',
                        boxShadow: 'none',
                    },
                    '.Tab--selected': {
                        border: '1px solid #1479bd',
                        color: '#1479bd',
                    },
                },
            },
        });
        const paymentElement = elements.create('payment');
        paymentElement.mount(this.paymentElementContainer);
        this.loader?.remove();

        this.handleRedirectStatus(checkoutSuccessUrl, errorFailed);
        this.mapEvents(stripe, elements, paymentPageUrl);
    }

    protected handleRedirectStatus(checkoutSuccessUrl: string, errorFailed: string): void {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectStatus = urlParams.get('redirect_status');

        if (redirectStatus === 'succeeded') {
            window.location.replace(checkoutSuccessUrl);
            return;
        }

        // After a redirect-based payment (e.g. PayPal) fails, Stripe redirects back to
        // this page. Detect redirect_status=failed and show an error so the customer
        // can retry with a different payment method.
        if (redirectStatus === 'failed') {
            this.messageContainer.textContent = errorFailed;
            this.messageContainer.style.display = 'block';
        }
    }

    protected mapEvents(stripe: StripeInstance, elements: StripeElements, paymentPageUrl: string): void {
        this.submitButton.addEventListener('click', () => {
            this.submitButton.disabled = true;
            this.messageContainer.style.display = 'none';

            stripe.confirmPayment({
                elements,
                // Use the payment page itself as return_url so Stripe redirects back here
                // on both success and failure, allowing us to control the final navigation.
                confirmParams: { return_url: paymentPageUrl },
            }).then(({ error }) => {
                if (!error) {
                    // For redirect-based methods Stripe handles the redirect automatically.
                    return;
                }

                this.messageContainer.textContent = error.message;
                this.messageContainer.style.display = 'block';
                this.submitButton.disabled = false;
            });
        });
    }
}
