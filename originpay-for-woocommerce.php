<?php
/**
 * Plugin Name: Originpay – Payment Gateway for WooCommerce
 * Plugin URI: https://www.originpay.in
 * Description: Accept payments via UPI, Credit/Debit Cards, NetBanking, and Wallets using Originpay payment gateway.
 * Version: 1.0.0
 * Author: Speedx Innovations Private Limited.
 * Author URI: https://www.originpay.in
 * Text Domain: originpay-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'ORIGINPAY_VERSION', '1.0.0' );
define( 'ORIGINPAY_PLUGIN_FILE', __FILE__ );
define( 'ORIGINPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ORIGINPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function originpay_declare_hpos_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
}
add_action( 'before_woocommerce_init', 'originpay_declare_hpos_compatibility' );

function originpay_load_textdomain() {
    load_plugin_textdomain( 'originpay-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'originpay_load_textdomain' );

function originpay_init_gateway() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'originpay_missing_woocommerce_notice' );
        return;
    }

    require_once ORIGINPAY_PLUGIN_DIR . 'includes/class-wc-originpay-api.php';
    require_once ORIGINPAY_PLUGIN_DIR . 'includes/class-wc-gateway-originpay.php';

    add_filter( 'woocommerce_payment_gateways', 'originpay_add_gateway_class' );
}
add_action( 'plugins_loaded', 'originpay_init_gateway' );

function originpay_missing_woocommerce_notice() {
    echo '<div class="error"><p><strong>' . esc_html__( 'Originpay requires WooCommerce to be installed and active.', 'originpay-for-woocommerce' ) . '</strong></p></div>';
}

function originpay_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Gateway_Originpay';
    return $gateways;
}

function originpay_register_blocks_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once ORIGINPAY_PLUGIN_DIR . 'includes/class-wc-originpay-blocks.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new WC_Originpay_Blocks_Support() );
            }
        );
    }
}
add_action( 'woocommerce_blocks_loaded', 'originpay_register_blocks_support' );

function originpay_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=originpay' ) ) . '">' . esc_html__( 'Settings', 'originpay-for-woocommerce' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'originpay_plugin_action_links' );

function originpay_enqueue_checkout_assets() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        wp_enqueue_style( 'originpay-css', ORIGINPAY_PLUGIN_URL . 'assets/css/originpay.css', array(), ORIGINPAY_VERSION );
    }
}
add_action( 'wp_enqueue_scripts', 'originpay_enqueue_checkout_assets' );

function originpay_rest_prepare_payment_gateway( $response, $gateway ) {
    if ( is_object( $gateway ) && 'originpay' === $gateway->id ) {
        $logo_url = plugins_url( 'assets/images/woo_logo.svg', ORIGINPAY_PLUGIN_FILE );
        $data     = $response->get_data();
        $data['icon']      = $logo_url;
        $data['icon_url']  = $logo_url;
        $data['image_url'] = $logo_url;
        $data['logo_url']  = $logo_url;
        $data['logo']      = $logo_url;
        $response->set_data( $data );
    }
    return $response;
}
add_filter( 'woocommerce_rest_prepare_payment_gateway', 'originpay_rest_prepare_payment_gateway', 10, 2 );
