import register from 'ShopUi/app/registry';

function loadStripeJs(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (document.querySelector('script[src="https://js.stripe.com/v3/"]')) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve();
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

export default register(
    'stripe-payment',
    () =>
        loadStripeJs().then(() =>
            import(
                /* webpackMode: "lazy" */
                /* webpackChunkName: "stripe-payment" */
                './stripe-payment'
            ),
        ),
);
