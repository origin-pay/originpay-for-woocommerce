# Originpay – Payment Gateway for WooCommerce

Originpay Payment Gateway plugin for WooCommerce enables merchants to accept online payments securely across India via UPI, Credit/Debit Cards, NetBanking, and Digital Wallets.

## Requirements

- WordPress 5.6 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Store currency configured to Indian Rupee (INR)
- An active merchant account on [Originpay](https://www.originpay.in)

## Features

- **Multiple Payment Modes**: Supports UPI, Cards, NetBanking, and popular Wallets.
- **Hosted Standard Checkout**: Fast and responsive checkout interface hosted by Originpay.
- **Server-to-Server Payment Verification**: Validates transaction status directly with Originpay Command API (`/v2/order/command`).
- **WooCommerce Blocks & HPOS Ready**: Fully compatible with WooCommerce High-Performance Order Storage (HPOS) and the modern Cart & Checkout Blocks.
- **Admin Status Recheck**: Allows store managers to manually verify and sync payment status directly from the WooCommerce Order Edit screen.

## Environments

| Environment | Base URL | Checkout Endpoint | Order Status Endpoint |
|---|---|---|---|
| **Live** | `https://api.originpay.in` | `/v2/checkout/pay` | `/v2/order/command` |
| **Test** | `https://api-uat.originpay.in` | `/v2/checkout/pay` | `/v2/order/command` |

## Installation

1. Upload the `originpay-for-woocommerce` directory to the `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New > Upload Plugin** in the WordPress admin panel.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce > Settings > Payments > Originpay**.
4. Configure your gateway credentials:
   - **Payment Mode**: Choose **Live** or **Test**.
   - **Merchant Id**: Enter your Originpay Merchant ID (Mandatory).
   - **Public API key**: Enter your Originpay Public Key (Mandatory).
   - **Secret API key**: Enter your Originpay Secret Key (Mandatory).
5. Save changes and ensure the gateway is enabled.

## License

GPLv2 or later.
