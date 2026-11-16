<?php
defined( 'ABSPATH' ) || exit;

class WC_Originpay_API {

    public static function get_base_url( $payment_mode = 'live' ) {
        if ( 'test' === $payment_mode ) {
            $url = 'https://api-uat.originpay.in';
        } else {
            $url = 'https://api.originpay.in';
        }
        return apply_filters( 'woocommerce_originpay_api_base_url', $url, $payment_mode );
    }

    public static function get_headers( $public_key, $secret_key, $merchant_id ) {
        $token = base64_encode( trim( $public_key ) . ':' . trim( $secret_key ) );
        return array(
            'Content-Type'     => 'application/json',
            'Authorization'    => 'Basic ' . $token,
            'X-MERCHANT-ID'    => trim( $merchant_id ),
            'X-SOURCE'         => 'API',
            'X-SOURCE-CHANNEL' => 'web',
        );
    }

    public static function initiate_payment( $body_data, $credentials, $payment_mode = 'live' ) {
        $endpoint = self::get_base_url( $payment_mode ) . '/v2/checkout/pay';
        $headers  = self::get_headers(
            $credentials['public_key'],
            $credentials['secret_key'],
            $credentials['merchant_id']
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'method'  => 'POST',
                'timeout' => 45,
                'headers' => $headers,
                'body'    => wp_json_encode( $body_data ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return array(
                'success' => false,
                'message' => esc_html__( 'Invalid response received from Originpay API.', 'originpay-for-woocommerce' ),
            );
        }

        $data['http_status'] = $status_code;
        return $data;
    }

    public static function get_order_status( $merchant_order_id, $amount, $credentials, $payment_mode = 'live' ) {
        $endpoint = self::get_base_url( $payment_mode ) . '/v2/order/command';
        $headers  = self::get_headers(
            $credentials['public_key'],
            $credentials['secret_key'],
            $credentials['merchant_id']
        );

        $body_data = array(
            'merchantOrderId' => $merchant_order_id,
            'amount'          => (int) $amount,
            'transactionType' => 'STATUS',
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'method'  => 'POST',
                'timeout' => 45,
                'headers' => $headers,
                'body'    => wp_json_encode( $body_data ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
        $data        = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return array(
                'success' => false,
                'message' => esc_html__( 'Invalid status inquiry response from Originpay API.', 'originpay-for-woocommerce' ),
            );
        }

        $data['http_status'] = $status_code;
        return $data;
    }
}
