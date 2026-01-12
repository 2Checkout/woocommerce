(function() {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { createElement, useEffect } = window.wp.element;
	const { __ } = window.wp.i18n;
	const { decodeEntities } = window.wp.htmlEntities;
	const { getSetting } = window.wc.wcSettings;

	const settings = getSetting('twocheckout_convert_plus_data', {});

	const defaultLabel = __('2Checkout Convert Plus', 'woocommerce');
	const label = decodeEntities(settings.title) || defaultLabel;

	/**
	 * Content component - displays the payment method description
	 */
	const Content = (props) => {
		const { eventRegistration, emitResponse } = props;
		const { onPaymentSetup } = eventRegistration;

		// Register payment processing handler
		useEffect(() => {
			const unsubscribe = onPaymentSetup(async () => {
				// This payment method redirects to 2Checkout's hosted page
				// The redirect will be handled by the process_payment method
				return {
					type: emitResponse.responseTypes.SUCCESS,
				};
			});

			return () => {
				unsubscribe();
			};
		}, [onPaymentSetup, emitResponse.responseTypes.SUCCESS]);

		return createElement('div', {
			className: 'twocheckout-convert-plus-description',
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
	const TwocheckoutConvertPlusPaymentMethod = {
		name: 'twocheckout_convert_plus',
		label: createElement(Label),
		content: createElement(Content),
		edit: createElement(Content),
		canMakePayment: () => true,
		ariaLabel: label,
		supports: {
			features: settings.supports || [],
		},
	};

	registerPaymentMethod(TwocheckoutConvertPlusPaymentMethod);
})();
