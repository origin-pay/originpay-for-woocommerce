<?php
defined( 'ABSPATH' ) || exit;

class WC_Gateway_Originpay extends WC_Payment_Gateway {

    public $payment_mode;
    public $merchant_id;
    public $public_key;
    public $secret_key;

    public function __construct() {
        $this->id                 = 'originpay';
        $this->has_fields         = false;
        $this->method_title       = esc_html__( 'Originpay', 'originpay-for-woocommerce' );
        $this->method_description = esc_html__( 'Accept payments via UPI, Credit/Debit Cards, NetBanking, and Wallets using Originpay.', 'originpay-for-woocommerce' );
        $this->icon               = plugins_url( 'assets/images/woo_logo.svg', ORIGINPAY_PLUGIN_FILE );
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title', 'Originpay' );
        $this->description  = $this->get_option( 'description', 'Pay securely via UPI, Cards, NetBanking, and Wallets using Originpay.' );
        $this->payment_mode = $this->get_option( 'payment_mode', 'live' );
        $this->merchant_id  = $this->get_option( 'merchant_id' );
        $this->public_key   = $this->get_option( 'public_key' );
        $this->secret_key   = $this->get_option( 'secret_key' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_originpay', array( $this, 'handle_callback' ) );
        add_action( 'woocommerce_order_action_originpay_check_status', array( $this, 'admin_check_payment_status' ) );
        add_filter( 'woocommerce_order_actions', array( $this, 'add_order_actions' ), 10, 2 );
    }

    public function get_icon() {
        $icon_url = plugins_url( 'assets/images/logo.svg', ORIGINPAY_PLUGIN_FILE );
        $icon     = '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $this->get_title() ) . '" class="originpay-gateway-icon" style="max-height: 24px; vertical-align: middle; margin-left: 10px;" />';
        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__( 'Enable/Disable', 'originpay-for-woocommerce' ),
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Enable Originpay Payment Gateway', 'originpay-for-woocommerce' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => esc_html__( 'Title', 'originpay-for-woocommerce' ),
                'type'        => 'text',
                'description' => esc_html__( 'Payment method title that the customer sees during checkout.', 'originpay-for-woocommerce' ),
                'default'     => esc_html__( 'Originpay', 'originpay-for-woocommerce' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => esc_html__( 'Description', 'originpay-for-woocommerce' ),
                'type'        => 'textarea',
                'description' => esc_html__( 'Payment method description that the customer sees during checkout.', 'originpay-for-woocommerce' ),
                'default'     => esc_html__( 'Pay securely via UPI, Cards, NetBanking, and Wallets using Originpay.', 'originpay-for-woocommerce' ),
            ),
            'payment_mode' => array(
                'title'       => esc_html__( 'Payment Mode', 'originpay-for-woocommerce' ),
                'type'        => 'select',
                'description' => esc_html__( 'Select payment environment. Live (https://api.originpay.in) or Test (https://api-uat.originpay.in).', 'originpay-for-woocommerce' ),
                'default'     => 'live',
                'options'     => array(
                    'live' => esc_html__( 'Live', 'originpay-for-woocommerce' ),
                    'test' => esc_html__( 'Test', 'originpay-for-woocommerce' ),
                ),
            ),
            'merchant_id' => array(
                'title'       => esc_html__( 'Merchant Id', 'originpay-for-woocommerce' ),
                'type'        => 'text',
                'description' => esc_html__( 'Mandatory. Enter your Originpay Merchant ID.', 'originpay-for-woocommerce' ),
                'default'     => '',
                'placeholder' => esc_html__( 'e.g. MID123456', 'originpay-for-woocommerce' ),
            ),
            'public_key' => array(
                'title'       => esc_html__( 'Public API key', 'originpay-for-woocommerce' ),
                'type'        => 'text',
                'description' => esc_html__( 'Mandatory. Enter your Originpay Public API Key.', 'originpay-for-woocommerce' ),
                'default'     => '',
            ),
            'secret_key' => array(
                'title'       => esc_html__( 'Secret API key', 'originpay-for-woocommerce' ),
                'type'        => 'password',
                'description' => esc_html__( 'Mandatory. Enter your Originpay Secret API Key.', 'originpay-for-woocommerce' ),
                'default'     => '',
            ),
        );
    }

    public function process_admin_options() {
        $enabled = isset( $_POST['woocommerce_originpay_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['woocommerce_originpay_enabled'] ) );
        if ( $enabled ) {
            $merchant_id = isset( $_POST['woocommerce_originpay_merchant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_originpay_merchant_id'] ) ) : '';
            $public_key  = isset( $_POST['woocommerce_originpay_public_key'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_originpay_public_key'] ) ) : '';
            $secret_key  = isset( $_POST['woocommerce_originpay_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_originpay_secret_key'] ) ) : '';

            if ( empty( $merchant_id ) ) {
                WC_Admin_Settings::add_error( esc_html__( 'Originpay: Merchant Id is mandatory.', 'originpay-for-woocommerce' ) );
            }
            if ( empty( $public_key ) ) {
                WC_Admin_Settings::add_error( esc_html__( 'Originpay: Public API key is mandatory.', 'originpay-for-woocommerce' ) );
            }
            if ( empty( $secret_key ) ) {
                WC_Admin_Settings::add_error( esc_html__( 'Originpay: Secret API key is mandatory.', 'originpay-for-woocommerce' ) );
            }
        }
        return parent::process_admin_options();
    }

    public function is_available() {
        if ( 'yes' !== $this->enabled ) {
            return false;
        }

        if ( 'INR' !== get_woocommerce_currency() ) {
            return false;
        }

        if ( empty( $this->merchant_id ) || empty( $this->public_key ) || empty( $this->secret_key ) ) {
            return false;
        }

        return parent::is_available();
    }

    public function get_api_credentials() {
        return array(
            'merchant_id' => trim( $this->merchant_id ),
            'public_key'  => trim( $this->public_key ),
            'secret_key'  => trim( $this->secret_key ),
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wc_add_notice( esc_html__( 'Order not found.', 'originpay-for-woocommerce' ), 'error' );
            return array( 'result' => 'failure' );
        }

        $amount = (int) round( (float) $order->get_total() * 100 );
        if ( $amount < 100 || $amount > 1000000000 ) {
            wc_add_notice( esc_html__( 'The order amount is outside the allowed limits for Originpay.', 'originpay-for-woocommerce' ), 'error' );
            return array( 'result' => 'failure' );
        }

        $clean_order_id    = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $order_id );
        $merchant_order_id = 'ORD' . $clean_order_id . 'T' . time() . 'R' . wp_rand( 100, 999 );

        $order->update_meta_data( '_originpay_merchant_order_id', $merchant_order_id );
        $order->save();

        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $name       = trim( $first_name . ' ' . $last_name );
        if ( empty( $name ) ) {
            $name = 'Customer';
        }

        $email = $order->get_billing_email();
        if ( empty( $email ) ) {
            $email = 'customer@originpay.in';
        }

        $raw_phone = $order->get_billing_phone();
        $phone     = preg_replace( '/[^0-9]/', '', (string) $raw_phone );
        if ( strlen( $phone ) < 10 ) {
            $phone = '9999999999';
        }

        $redirect_url = add_query_arg(
            array(
                'wc-api'    => 'WC_Gateway_Originpay',
                'order_id'  => $order_id,
                'order_key' => $order->get_order_key(),
            ),
            home_url( '/' )
        );

        $remark = sprintf(
            esc_html__( 'Order #%1$s - %2$s', 'originpay-for-woocommerce' ),
            $order->get_order_number(),
            get_bloginfo( 'name' )
        );

        $payload = array(
            'amount'          => $amount,
            'currency'        => 'INR',
            'merchantOrderId' => $merchant_order_id,
            'expireAfter'     => 600,
            'customer'        => array(
                'name'  => substr( $name, 0, 255 ),
                'email' => substr( $email, 0, 255 ),
                'phone' => substr( $phone, -15 ),
            ),
            'paymentInfo'     => array(
                'methods'     => array( 'upi', 'card', 'netbanking', 'wallet' ),
                'remark'      => substr( $remark, 0, 255 ),
                'redirectUrl' => $redirect_url,
            ),
            'paymentHandler'  => array(
                'type' => 'STANDARD',
            ),
            'udf1'            => substr( (string) $order_id, 0, 25 ),
            'udf2'            => 'WooCommerce',
        );

        $response = WC_Originpay_API::initiate_payment(
            $payload,
            $this->get_api_credentials(),
            $this->payment_mode
        );

        if ( empty( $response['success'] ) || empty( $response['data']['payment_url'] ) ) {
            $error_message = ! empty( $response['message'] ) ? $response['message'] : esc_html__( 'Unable to initiate payment with Originpay.', 'originpay-for-woocommerce' );
            $order->add_order_note( sprintf( esc_html__( 'Originpay initiation failed: %s', 'originpay-for-woocommerce' ), $error_message ) );
            wc_add_notice( $error_message, 'error' );
            return array( 'result' => 'failure' );
        }

        if ( ! empty( $response['data']['order_id'] ) ) {
            $order->update_meta_data( '_originpay_order_id', sanitize_text_field( $response['data']['order_id'] ) );
            $order->save();
        }

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $response['data']['payment_url'] ),
        );
    }

    public function handle_callback() {
        $order_id = 0;
        if ( isset( $_GET['order_id'] ) ) {
            $order_id = absint( $_GET['order_id'] );
        }

        $raw_input = file_get_contents( 'php://input' );
        $json_data = json_decode( $raw_input, true );

        if ( ! $order_id && ! empty( $json_data['merchantOrderId'] ) ) {
            $order_id = $this->find_order_by_merchant_order_id( sanitize_text_field( $json_data['merchantOrderId'] ) );
        }

        if ( ! $order_id && ! empty( $json_data['merchant_order_id'] ) ) {
            $order_id = $this->find_order_by_merchant_order_id( sanitize_text_field( $json_data['merchant_order_id'] ) );
        }

        if ( ! $order_id ) {
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'error', 'message' => 'Order not found' ), 404 );
            }
            wp_die( esc_html__( 'Order reference missing.', 'originpay-for-woocommerce' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'error', 'message' => 'Invalid order' ), 404 );
            }
            wp_die( esc_html__( 'Invalid order.', 'originpay-for-woocommerce' ) );
        }

        if ( isset( $_GET['order_key'] ) ) {
            $order_key = sanitize_text_field( wp_unslash( $_GET['order_key'] ) );
            if ( $order->get_order_key() !== $order_key ) {
                wp_die( esc_html__( 'Security check failed.', 'originpay-for-woocommerce' ) );
            }
        }

        if ( $order->is_paid() ) {
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'success', 'message' => 'Already paid' ), 200 );
            }
            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        $merchant_order_id = $order->get_meta( '_originpay_merchant_order_id' );
        if ( empty( $merchant_order_id ) ) {
            $merchant_order_id = 'ORD' . $order->get_id();
        }

        $amount = (int) round( (float) $order->get_total() * 100 );

        $status_data = WC_Originpay_API::get_order_status(
            $merchant_order_id,
            $amount,
            $this->get_api_credentials(),
            $this->payment_mode
        );

        $txn_status = ! empty( $status_data['txnStatus'] ) ? sanitize_text_field( $status_data['txnStatus'] ) : '';

        if ( 'success' === $txn_status ) {
            $gateway_order_id = ! empty( $status_data['data']['order_id'] ) ? sanitize_text_field( $status_data['data']['order_id'] ) : '';
            $payment_details  = ! empty( $status_data['data']['paymentDetails'][0] ) ? $status_data['data']['paymentDetails'][0] : array();
            $utr              = ! empty( $payment_details['utr'] ) ? sanitize_text_field( $payment_details['utr'] ) : '';
            $bank_ref         = ! empty( $payment_details['bank_reference'] ) ? sanitize_text_field( $payment_details['bank_reference'] ) : '';
            $method           = ! empty( $payment_details['payment_method'] ) ? sanitize_text_field( $payment_details['payment_method'] ) : '';

            $order->update_meta_data( '_originpay_order_id', $gateway_order_id );
            $order->update_meta_data( '_originpay_utr', $utr );
            $order->update_meta_data( '_originpay_bank_ref', $bank_ref );
            $order->update_meta_data( '_originpay_payment_method', $method );
            $order->save();

            $note = sprintf(
                esc_html__( 'Originpay payment successful. Order ID: %1$s, Method: %2$s, UTR: %3$s, Bank Ref: %4$s', 'originpay-for-woocommerce' ),
                $gateway_order_id,
                $method,
                $utr,
                $bank_ref
            );
            $order->add_order_note( $note );
            $order->payment_complete( $gateway_order_id );

            if ( WC()->cart ) {
                WC()->cart->empty_cart();
            }

            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'success' ), 200 );
            }

            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        if ( 'failed' === $txn_status ) {
            $order->update_status( 'failed', esc_html__( 'Originpay payment attempt failed.', 'originpay-for-woocommerce' ) );
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'handled' ), 200 );
            }
            wc_add_notice( esc_html__( 'Payment failed or was declined. Please try again.', 'originpay-for-woocommerce' ), 'error' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }

        if ( 'cancelled' === $txn_status ) {
            $order->update_status( 'cancelled', esc_html__( 'Originpay payment cancelled by customer.', 'originpay-for-woocommerce' ) );
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'handled' ), 200 );
            }
            wc_add_notice( esc_html__( 'Payment cancelled. You can try checkout again.', 'originpay-for-woocommerce' ), 'notice' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }

        if ( 'expired' === $txn_status ) {
            $order->update_status( 'cancelled', esc_html__( 'Originpay payment session expired.', 'originpay-for-woocommerce' ) );
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'handled' ), 200 );
            }
            wc_add_notice( esc_html__( 'Payment session expired. Please try again.', 'originpay-for-woocommerce' ), 'error' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }

        if ( 'pending' === $txn_status || 'created' === $txn_status ) {
            $order->update_status( 'pending', esc_html__( 'Originpay payment is pending confirmation.', 'originpay-for-woocommerce' ) );
            if ( ! empty( $raw_input ) ) {
                wp_send_json( array( 'status' => 'pending' ), 200 );
            }
            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        if ( ! empty( $raw_input ) ) {
            wp_send_json( array( 'status' => 'unhandled', 'txnStatus' => $txn_status ), 200 );
        }

        wp_safe_redirect( $order->get_checkout_order_received_url() );
        exit;
    }

    public function find_order_by_merchant_order_id( $merchant_order_id ) {
        $orders = wc_get_orders(
            array(
                'limit'        => 1,
                'meta_key'     => '_originpay_merchant_order_id',
                'meta_value'   => $merchant_order_id,
                'meta_compare' => '=',
            )
        );

        if ( ! empty( $orders ) ) {
            return $orders[0]->get_id();
        }

        if ( preg_match( '/^ORD(\d+)T/', $merchant_order_id, $matches ) ) {
            return absint( $matches[1] );
        }

        return 0;
    }

    public function add_order_actions( $actions, $order ) {
        if ( $order && $this->id === $order->get_payment_method() ) {
            $actions['originpay_check_status'] = esc_html__( 'Check Originpay Payment Status', 'originpay-for-woocommerce' );
        }
        return $actions;
    }

    public function admin_check_payment_status( $order ) {
        $merchant_order_id = $order->get_meta( '_originpay_merchant_order_id' );
        if ( empty( $merchant_order_id ) ) {
            $merchant_order_id = 'ORD' . $order->get_id();
        }

        $amount      = (int) round( (float) $order->get_total() * 100 );
        $status_data = WC_Originpay_API::get_order_status(
            $merchant_order_id,
            $amount,
            $this->get_api_credentials(),
            $this->payment_mode
        );

        $txn_status = ! empty( $status_data['txnStatus'] ) ? sanitize_text_field( $status_data['txnStatus'] ) : '';

        if ( 'success' === $txn_status ) {
            $gateway_order_id = ! empty( $status_data['data']['order_id'] ) ? sanitize_text_field( $status_data['data']['order_id'] ) : '';
            $payment_details  = ! empty( $status_data['data']['paymentDetails'][0] ) ? $status_data['data']['paymentDetails'][0] : array();
            $utr              = ! empty( $payment_details['utr'] ) ? sanitize_text_field( $payment_details['utr'] ) : '';
            $bank_ref         = ! empty( $payment_details['bank_reference'] ) ? sanitize_text_field( $payment_details['bank_reference'] ) : '';
            $method           = ! empty( $payment_details['payment_method'] ) ? sanitize_text_field( $payment_details['payment_method'] ) : '';

            $order->update_meta_data( '_originpay_order_id', $gateway_order_id );
            $order->update_meta_data( '_originpay_utr', $utr );
            $order->update_meta_data( '_originpay_bank_ref', $bank_ref );
            $order->update_meta_data( '_originpay_payment_method', $method );
            $order->save();

            $order->add_order_note(
                sprintf(
                    esc_html__( 'Admin Status Check: Originpay payment successful. Order ID: %1$s, Method: %2$s, UTR: %3$s, Bank Ref: %4$s', 'originpay-for-woocommerce' ),
                    $gateway_order_id,
                    $method,
                    $utr,
                    $bank_ref
                )
            );
            $order->payment_complete( $gateway_order_id );
        } elseif ( ! empty( $txn_status ) ) {
            $order->add_order_note( sprintf( esc_html__( 'Admin Status Check: Payment status is %s.', 'originpay-for-woocommerce' ), $txn_status ) );
        } else {
            $message = ! empty( $status_data['message'] ) ? $status_data['message'] : esc_html__( 'Unknown error querying Originpay status.', 'originpay-for-woocommerce' );
            $order->add_order_note( sprintf( esc_html__( 'Admin Status Check failed: %s', 'originpay-for-woocommerce' ), $message ) );
        }
    }
}
