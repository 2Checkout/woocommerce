<?php

function getTwoCheckoutFormFields()
{
    $default_style = "{
                    'margin': '0',
                    'fontFamily': 'Helvetica, sans-serif',
                    'fontSize': '1rem',
                    'fontWeight': '400',
                    'lineHeight': '1.5',
                    'color': '#212529',
                    'textAlign': 'left',
                    'backgroundColor': '#FFFFFF',
                    '*': {
                        'boxSizing': 'border-box'
                    },
                    '.no-gutters': {
                        'marginRight': 0,
                        'marginLeft': 0
                    },
                    '.row': {
                        'display': 'flex',
                        'flexWrap': 'wrap'
                    },
                    '.col': {
                        'flexBasis': '0',
                        'flexGrow': '1',
                        'maxWidth': '100%',
                        'padding': '0',
                        'position': 'relative',
                        'width': '100%'
                    },
                    'div': {
                        'display': 'block'
                    },
                    '.field-container': {
                        'paddingBottom': '14px'
                    },
                    '.field-wrapper': {
                        'paddingRight': '25px'
                    },
                    '.input-wrapper': {
                        'position': 'relative'
                    },
                    'label': {
                        'display': 'inline-block',
                        'marginBottom': '9px',
                        'color': '#313131',
                        'fontSize': '14px',
                        'fontWeight': '300',
                        'lineHeight': '17px'
                    },
                    'input': {
                        'overflow': 'visible',
                        'margin': 0,
                        'fontFamily': 'inherit',
                        'display': 'block',
                        'width': '100%',
                        'height': '42px',
                        'padding': '10px 12px',
                        'fontSize': '18px',
                        'fontWeight': '400',
                        'lineHeight': '22px',
                        'color': '#313131',
                        'backgroundColor': '#FFF',
                        'backgroundClip': 'padding-box',
                        'border': '1px solid #CBCBCB',
                        'borderRadius': '3px',
                        'transition': 'border-color .15s ease-in-out,box-shadow .15s ease-in-out',
                        'outline': 0
                    },
                    'input:focus': {
                        'border': '1px solid #5D5D5D',
                        'backgroundColor': '#FFFDF2'
                    },
                    '.is-error input': {
                        'border': '1px solid #D9534F'
                    },
                    '.is-error input:focus': {
                        'backgroundColor': '#D9534F0B'
                    },
                    '.is-valid input': {
                        'border': '1px solid #1BB43F'
                    },
                    '.is-valid input:focus': {
                        'backgroundColor': '#1BB43F0B'
                    },
                    '.validation-message': {
                        'color': '#D9534F',
                        'fontSize': '10px',
                        'fontStyle': 'italic',
                        'marginTop': '6px',
                        'marginBottom': '-5px',
                        'display': 'block',
                        'lineHeight': '1'
                    },
                    '.card-expiration-date': {
                        'paddingRight': '.5rem'
                    },
                    '.is-empty input': {
                        'color': '#EBEBEB'
                    },
                    '.lock-icon': {
                        'top': 'calc(50% - 7px)',
                        'right': '10px'
                    },
                    '.valid-icon': {
                        'top': 'calc(50% - 8px)',
                        'right': '-25px'
                    },
                    '.error-icon': {
                        'top': 'calc(50% - 8px)',
                        'right': '-25px'
                    },
                    '.card-icon': {
                        'top': 'calc(50% - 10px)',
                        'left': '10px',
                        'display': 'none'
                    },
                    '.is-empty .card-icon': {
                        'display': 'block'
                    },
                    '.is-focused .card-icon': {
                        'display': 'none'
                    },
                    '.card-type-icon': {
                        'right': '30px',
                        'display': 'block'
                    },
                    '.card-type-icon.visa': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.mastercard': {
                        'top': 'calc(50% - 14.5px)'
                    },
                    '.card-type-icon.amex': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.discover': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.jcb': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.dankort': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.cartebleue': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.diners': {
                        'top': 'calc(50% - 14px)'
                    },
                    '.card-type-icon.elo': {
                        'top': 'calc(50% - 14px)'
                    }
                }";

    return [
        'enabled'     => [
            'title'   => __('Enable/Disable', 'woocommerce'),
            'type'    => 'checkbox',
            'label'   => __('Enable 2Checkout', 'woocommerce'),
            'default' => 'yes'
        ],
        'title'       => [
            'title'       => __('Title', 'woocommerce'),
            'type'        => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
            'default'     => __('2Checkout 2Pay Api', 'woocommerce'),
            'desc_tip'    => true,
        ],
        'description' => [
            'title'       => __('Description', 'woocommerce'),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
            'default'     => __('Safe payment solutions by <a href="https://www.2checkout.com/" target="_blank">2Checkout </a>', 'woocommerce')
        ],
        'seller_id'    => [
            'title'       => __('Seller ID', 'woocommerce'),
            'type'        => 'text',
            'description' => __('Please enter your 2Checkout account number; this is needed in order to take payment.', 'woocommerce'),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => ''
        ],
        'secret_key'   => [
            'title'       => __('Secret Key', 'woocommerce'),
            'type'        => 'password',
            'description' => __('Please enter your 2Checkout Secret Key; this is needed in order to take payment.', 'woocommerce'),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => ''
        ],
        'debug'       => [
            'title'       => __('Debug Log', 'woocommerce'),
            'type'        => 'checkbox',
            'label'       => __('Enable logging', 'woocommerce'),
            'default'     => 'no',
            'desc_tip'    => true,
            'description' => sprintf(__('Log 2Checkout events', 'woocommerce'), wc_get_log_file_path('twocheckout'))
        ],
        'demo'        => [
            'title'       => __('Demo order', 'woocommerce'),
            'type'        => 'checkbox',
            'label'       => __('Create test orders', 'woocommerce'),
            'default'     => 'no',
            'desc_tip'    => true,
            'description' => sprintf(__('Not available yet for this method!', 'woocommerce'), wc_get_log_file_path('twocheckout'))
        ],
        'default'     => [
            'title'       => __('Use default style', 'woocommerce'),
            'type'        => 'checkbox',
            'label'       => __('Yes, I like the default style', 'woocommerce'),
            'default'     => 'yes',
            'desc_tip'    => true,
            'description' => sprintf(__('If you uncheck this, the form will use the style from the bellow input!',
                'woocommerce'), wc_get_log_file_path('twocheckout'))
        ],
        'style'       => [
            'title'       => __('Custom style', 'woocommerce'),
            'type'        => 'textarea',
            'description' => __('<i style="color: #e35d5d"><b>IMPORTANT! </b><br /> This is the styling object that styles your form.
                     Do not remove or add new classes. You can modify the existing ones. Use
                      double quotes for all keys and values!  <br /> VALID JSON FORMAT REQUIRED (validate 
                      json before save here: <a href="https://jsonlint.com/" target="_blank">https://jsonlint.com/</a>) </i>. <br >
                      Also you can find more about styling your form <a href="https://knowledgecenter.2checkout.com/API-Integration/2Pay.js-payments-solution/2Pay.js-Payments-Solution-Integration-Guide/How_to_customize_and_style_the_2Pay.js_payment_form"
                       target="_blank">here</a>!', 'woocommerce'),
            'default'     => $default_style
        ],
    ];
}
