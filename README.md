# WooCommerce
2Checkout WooCommerce Connector

### _[Signup free with 2Checkout and start selling!](https://www.2checkout.com/signup)_

This repository includes modules for each 2Checkout inteface:
* **twocheckout** : 2PayJS/API
* **twocheckout-inline** : Inline Checkout
* **twocheckout-convert-plus** : Hosted Checkout

### Integrate WooCommerce with 2Checkout
----------------------------------------

### 2Checkout Payment Module Setup

#### 2Checkout Settings

1. Sign in to your 2Checkout account.
2. Navigate to **Dashboard** → **Integrations** → **Webhooks & API section**
3. There you can find the 'Merchant Code', 'Secret key', and the 'Buy link secret word'
4. Navigate to **Dashboard** → **Integrations** → **Ipn Settings**
5. Set the IPN URL which should be https://{your-site-name.com}/?wc-api=2checkout_ipn
6. Enable 'Triggers' in the IPN section. It’s simpler to enable all the triggers. Those who are not required will simply not be used.

#### WooCommerce Settings

1. Copy the directory for the module that you want to install to your WordPress plugins directory under '/wp-content/plugins'.
2. In your WordPress admin, navigate to **Plugins** and install the plugin.
3. Navigate to your WooCommerce settings page, click on **Payments** and click the module link.
4. Check to enable.
5. Enter the payment title and description.
6. Enter your **Seller ID** found in your 2Checkout panel Integrations section.
7. Enter your **Secret Key** found in your 2Checkout panel Integrations section.
8. Enter your **Secret Word** 2Checkout panel Integrations section _(Only used for Inline Checkout and Hosted Checkout modules)_
9. Click **Save Changes**.
