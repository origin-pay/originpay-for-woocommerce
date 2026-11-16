<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

final class WC_Originpay_Blocks_Support extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'originpay';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_originpay_settings', array() );
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
    }

    public function is_active() {
        return ! empty( $this->gateway ) && $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $script_path = 'assets/js/originpay-blocks.js';
        $script_url  = plugins_url( $script_path, ORIGINPAY_PLUGIN_FILE );
        $handle      = 'wc-originpay-blocks';

        wp_register_script(
            $handle,
            $script_url,
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ),
            ORIGINPAY_VERSION,
            true
        );

        return array( $handle );
    }

    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting( 'title', 'Originpay' ),
            'description' => $this->get_setting( 'description', 'Pay securely via UPI, Cards, NetBanking, and Wallets using Originpay.' ),
            'icon'        => plugins_url( 'assets/images/logo.svg', ORIGINPAY_PLUGIN_FILE ),
            'supports'    => array( 'products' ),
        );
    }
}
