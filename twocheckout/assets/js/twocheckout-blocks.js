(function() {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { createElement, useState, useEffect, useRef } = window.wp.element;
	const { __ } = window.wp.i18n;
	const { decodeEntities } = window.wp.htmlEntities;
	const { getSetting } = window.wc.wcSettings;

	const settings = getSetting('twocheckout_data', {});

	const defaultLabel = __('2Checkout', 'woocommerce');
	const label = decodeEntities(settings.title) || defaultLabel;

	/**
	 * Content component - displays the payment form with card element
	 */
	const Content = (props) => {
		const { eventRegistration, emitResponse, billing } = props;
		const { onPaymentSetup } = eventRegistration;
		const [errorMessage, setErrorMessage] = useState('');
		const [tcoReady, setTcoReady] = useState(false);
		const [cardholderName, setCardholderName] = useState('');
		const [cardElementId] = useState('twocheckout-card-element-' + Math.random().toString(36).substr(2, 9));
		const twoPayClientRef = useRef(null);
		const componentRef = useRef(null);
		const mountedRef = useRef(false);

		// Initialize cardholder name from billing data
		useEffect(() => {
			if (billing && billing.billingData) {
				const firstName = billing.billingData.first_name || '';
				const lastName = billing.billingData.last_name || '';
				setCardholderName(`${firstName} ${lastName}`.trim());
			}
		}, [billing]);

		// Initialize 2PayJs library and component
		useEffect(() => {
			mountedRef.current = true;

			// Load 2PayJs library dynamically if not already loaded
			const loadTwoPayJs = () => {
				if (typeof window.TwoPayClient !== 'undefined') {
					initializeTwoPayJs();
					return;
				}

				// Check if script is already in the page
				const existingScript = document.querySelector('script[src*="2pay.js"]');
				if (existingScript) {
					// Wait for it to load
					const checkInterval = setInterval(() => {
						if (typeof window.TwoPayClient !== 'undefined') {
							clearInterval(checkInterval);
							initializeTwoPayJs();
						}
					}, 100);

					setTimeout(() => {
						clearInterval(checkInterval);
						if (typeof window.TwoPayClient === 'undefined') {
							setErrorMessage(__('Failed to load 2Checkout payment library. Please refresh the page.', 'woocommerce'));
						}
					}, 10000);
					return;
				}

				// Load the script
				const script = document.createElement('script');
				script.src = 'https://2pay-js.2checkout.com/v1/2pay.js';
				script.async = true;
				script.onload = () => {
					setTimeout(() => {
						if (typeof window.TwoPayClient !== 'undefined') {
							initializeTwoPayJs();
						} else {
							setErrorMessage(__('Failed to initialize 2Checkout payment library.', 'woocommerce'));
						}
					}, 500);
				};
				script.onerror = () => {
					setErrorMessage(__('Failed to load 2Checkout payment library. Please check your connection.', 'woocommerce'));
				};
				document.head.appendChild(script);
			};

			const initializeTwoPayJs = () => {
				try {
					if (!settings.seller_id) {
						setErrorMessage(__('2Checkout seller ID not configured.', 'woocommerce'));
						return;
					}


					// Wait for the element to be in the DOM
					let attempts = 0;
					const maxAttempts = 50;
					const checkElement = setInterval(() => {
						attempts++;
						const element = document.getElementById(cardElementId);

						if (element) {
							clearInterval(checkElement);

							try {
								// Initialize TwoPayClient
								twoPayClientRef.current = new window.TwoPayClient(settings.seller_id);

								// Create card component with optional custom styling
								if (settings.default_style === 'yes') {
									// Use default 2Checkout styling
									componentRef.current = twoPayClientRef.current.components.create('card');
								} else if (settings.custom_style) {
									// Use merchant's custom styling
									try {
										const styleString = settings.custom_style.replace(/'/g, '"');
										const styleConfig = JSON.parse(styleString);
										componentRef.current = twoPayClientRef.current.components.create('card', styleConfig);
									} catch (styleError) {
										// If custom style parsing fails, use default
										componentRef.current = twoPayClientRef.current.components.create('card');
									}
								} else {
									// Fallback to default styling
									componentRef.current = twoPayClientRef.current.components.create('card');
								}

								// Mount the component using CSS selector
								componentRef.current.mount('#' + cardElementId);

								setTcoReady(true);
							} catch (mountError) {
								setErrorMessage(__('Failed to mount payment form: ', 'woocommerce') + mountError.message);
							}
						} else if (attempts >= maxAttempts) {
							clearInterval(checkElement);
							setErrorMessage(__('Payment form element not found. Please refresh the page.', 'woocommerce'));
						}
					}, 100);
				} catch (error) {
					setErrorMessage(__('Failed to initialize payment form: ', 'woocommerce') + error.message);
				}
			};

			loadTwoPayJs();

			// Cleanup
			return () => {
				mountedRef.current = false;
				if (componentRef.current && componentRef.current.unmount) {
					try {
						componentRef.current.unmount();
					} catch (e) {
						// Ignore unmount errors
					}
				}
			};
		}, [cardElementId]);

		// Register payment processing handler
		useEffect(() => {
			const unsubscribe = onPaymentSetup(async () => {
				setErrorMessage('');

				// Check if TwoPayClient is ready
				if (!tcoReady || !twoPayClientRef.current || !componentRef.current) {
					setErrorMessage(__('Payment library not ready. Please wait or refresh the page.', 'woocommerce'));
					return {
						type: emitResponse.responseTypes.ERROR,
						message: __('Payment library not ready. Please wait or refresh the page.', 'woocommerce'),
					};
				}

				// Validate cardholder name (automatically populated from billing information)
				if (!cardholderName || cardholderName.trim() === '') {
					setErrorMessage(__('Billing name is required. Please check your billing information.', 'woocommerce'));
					return {
						type: emitResponse.responseTypes.ERROR,
						message: __('Billing name is required. Please check your billing information.', 'woocommerce'),
					};
				}

				// Generate token using TwoPayClient
				try {
					const response = await twoPayClientRef.current.tokens.generate(componentRef.current, {
						name: cardholderName
					});

					if (response.token) {
						return {
							type: emitResponse.responseTypes.SUCCESS,
							meta: {
								paymentMethodData: {
									ess_token: response.token,
								},
							},
						};
					} else {
						setErrorMessage(__('Failed to generate payment token. Please check your card details.', 'woocommerce'));
						return {
							type: emitResponse.responseTypes.ERROR,
							message: __('Failed to generate payment token. Please check your card details.', 'woocommerce'),
						};
					}
				} catch (error) {
					const errorMsg = error.message || __('Payment processing failed. Please try again.', 'woocommerce');
					setErrorMessage(errorMsg);
					return {
						type: emitResponse.responseTypes.ERROR,
						message: errorMsg,
					};
				}
			});

			return () => {
				unsubscribe();
			};
		}, [
			onPaymentSetup,
			emitResponse.responseTypes,
			tcoReady,
			cardholderName
		]);

		return createElement('div', { className: 'twocheckout-payment-form', style: { width: '100%' } }, [
			settings.description && createElement('p', {
				key: 'description',
				dangerouslySetInnerHTML: { __html: decodeEntities(settings.description) },
				style: { marginBottom: '1em' }
			}),
			errorMessage && createElement('div', {
				key: 'error',
				className: 'wc-block-components-validation-error',
				role: 'alert',
				style: { marginBottom: '1em' }
			}, errorMessage),
			!tcoReady && createElement('div', {
				key: 'loading',
				className: 'wc-block-components-notice',
				style: { marginBottom: '1em' }
			}, __('Loading payment form...', 'woocommerce')),
			createElement('div', {
				key: 'card-element',
				id: cardElementId,
				style: {
					height: 'auto',
					marginTop: '20px',
					marginBottom: '1em',
					width: '100%',
					clear: 'both'
				}
			})
		]);
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
	const TwocheckoutPaymentMethod = {
		name: 'twocheckout',
		label: createElement(Label),
		content: createElement(Content),
		edit: createElement(Content),
		canMakePayment: () => true,
		ariaLabel: label,
		supports: {
			features: settings.supports || [],
		},
	};

	registerPaymentMethod(TwocheckoutPaymentMethod);
})();
