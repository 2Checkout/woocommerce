(function() {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { createElement, useEffect } = window.wp.element;
	const { __ } = window.wp.i18n;
	const { decodeEntities } = window.wp.htmlEntities;
	const { getSetting } = window.wc.wcSettings;

	const settings = getSetting('twocheckout_inline_data', {});

	const defaultLabel = __('2Checkout Inline Payment', 'woocommerce');
	const label = decodeEntities(settings.title) || defaultLabel;

	/**
	 * Launch the 2Checkout inline checkout
	 */
	const launchInlineCheckout = (payload, onClose) => {
		return new Promise((resolve, reject) => {
			// Check if library is already loaded
			if (window.TwoCoInlineCart) {
				// Library already loaded, just launch checkout
				try {
					// Subscribe to cart closed event if callback provided
					if (onClose && !window.TwoCoInlineCart._cartClosedSubscribed) {
						window.TwoCoInlineCart.events.subscribe('cart:closed', onClose);
						window.TwoCoInlineCart._cartClosedSubscribed = true;
					}

					window.TwoCoInlineCart.cart.setCurrency(payload.currency);
					window.TwoCoInlineCart.cart.setLanguage(payload.language);
					window.TwoCoInlineCart.cart.setReturnMethod(payload['return-method']);
					window.TwoCoInlineCart.cart.setTest(payload.test);
					window.TwoCoInlineCart.cart.setOrderExternalRef(payload['order-ext-ref']);
					window.TwoCoInlineCart.cart.setExternalCustomerReference(payload['customer-ext-ref']);
					window.TwoCoInlineCart.cart.setSource(payload.src);

					window.TwoCoInlineCart.products.removeAll();
					window.TwoCoInlineCart.products.addMany(payload.products);
					window.TwoCoInlineCart.billing.setData(payload.billing_address);
					window.TwoCoInlineCart.billing.setCompanyName(payload.billing_address['company-name']);
					window.TwoCoInlineCart.shipping.setData(payload.shipping_address);
					window.TwoCoInlineCart.cart.setSignature(payload.signature);
					window.TwoCoInlineCart.cart.setAutoAdvance(true);
					window.TwoCoInlineCart.cart.checkout();

					resolve();
				} catch (error) {
					reject(error);
				}
				return;
			}

			// Load the library for the first time
			(function(document, src, libName, config) {
				const script = document.createElement('script');
				script.src = src;
				script.async = true;
				const firstScriptElement = document.getElementsByTagName('script')[0];

				script.onload = function() {
					try {
						for (const namespace in config) {
							if (config.hasOwnProperty(namespace)) {
								window[libName].setup.setConfig(namespace, config[namespace]);
							}
						}
						window[libName].register();
						window[libName].setup.setMerchant(payload.merchant);
						window[libName].setup.setMode(payload.mode);
						window[libName].register();

						// Subscribe to cart closed event if callback provided
						if (onClose) {
							window[libName].events.subscribe('cart:closed', onClose);
							window[libName]._cartClosedSubscribed = true;
						}

						window[libName].cart.setCurrency(payload.currency);
						window[libName].cart.setLanguage(payload.language);
						window[libName].cart.setReturnMethod(payload['return-method']);
						window[libName].cart.setTest(payload.test);
						window[libName].cart.setOrderExternalRef(payload['order-ext-ref']);
						window[libName].cart.setExternalCustomerReference(payload['customer-ext-ref']);
						window[libName].cart.setSource(payload.src);

						window[libName].products.removeAll();
						window[libName].products.addMany(payload.products);
						window[libName].billing.setData(payload.billing_address);
						window[libName].billing.setCompanyName(payload.billing_address['company-name']);
						window[libName].shipping.setData(payload.shipping_address);
						window[libName].cart.setSignature(payload.signature);
						window[libName].cart.setAutoAdvance(true);
						window[libName].cart.checkout();

						resolve();
					} catch (error) {
						reject(error);
					}
				};

				script.onerror = function() {
					reject(new Error(__('Failed to load 2Checkout inline library', 'woocommerce')));
				};

				firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
			})(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart', {
				"app": {"merchant": payload.merchant},
				"cart": {"host": "https://secure.2checkout.com", "customization": payload.customization}
			});
		});
	};

	/**
	 * Content component - displays the payment method description
	 */
	const Content = (props) => {
		const { eventRegistration, emitResponse } = props;
		const { onPaymentSetup } = eventRegistration;
		const onCheckoutSuccess = eventRegistration.onCheckoutSuccess || eventRegistration.onCheckoutAfterProcessingWithSuccess;

		// Register payment setup handler
		useEffect(() => {
			const unsubscribe = onPaymentSetup(async () => {
				// Allow the order to be created
				return {
					type: emitResponse.responseTypes.SUCCESS,
				};
			});

			return () => {
				unsubscribe();
			};
		}, [onPaymentSetup, emitResponse.responseTypes.SUCCESS]);

		// Register after-processing handler to launch inline checkout
		useEffect(() => {
			if (!onCheckoutSuccess) return;

			const unsubscribe = onCheckoutSuccess(async (response) => {
				// Get payment details from the response
				let paymentDetails = null;
				if (response.processingResponse && response.processingResponse.paymentDetails) {
					paymentDetails = response.processingResponse.paymentDetails;
				}

				// Payment details is an object with a 'payload' property
				if (paymentDetails && paymentDetails.payload) {
					try {
						const payload = JSON.parse(paymentDetails.payload);

						// Handler for when the cart is closed without completing payment
						const handleCartClosed = (event) => {
							// Reload the checkout page to reset the form
							// This allows the user to try again
							setTimeout(() => {
								window.location.reload();
							}, 100);
						};

						// Launch the inline checkout and wait for it to initialize
						await launchInlineCheckout(payload, handleCartClosed);

						// The modal is now open, user will complete payment there
						// Don't redirect, let them complete or close the modal
						return {
							type: emitResponse.responseTypes.SUCCESS,
						};
					} catch (error) {
						return {
							type: emitResponse.responseTypes.ERROR,
							message: error.message || __('Failed to launch payment form', 'woocommerce'),
						};
					}
				}

				return {
					type: emitResponse.responseTypes.SUCCESS,
				};
			});

			return () => {
				if (unsubscribe) unsubscribe();
			};
		}, [onCheckoutSuccess, emitResponse.responseTypes]);

		return createElement('div', {
			className: 'twocheckout-inline-description',
			dangerouslySetInnerHTML: { __html: settings.description || '' }
		});
	};

	/**
	 * Label component - displays the payment method label
	 */
	const Label = (props) => {
		return createElement('span', {
			className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon'
		}, label);
	};

	/**
	 * Payment method configuration object
	 */
	const TwocheckoutInlinePaymentMethod = {
		name: 'twocheckout_inline',
		label: createElement(Label),
		content: createElement(Content),
		edit: createElement(Content),
		canMakePayment: () => true,
		ariaLabel: label,
		supports: {
			features: settings.supports || [],
		},
	};

	registerPaymentMethod(TwocheckoutInlinePaymentMethod);
})();
