<?php
/*
Plugin Name: WooCommerce Multiple Shipping Addresses
Plugin URI: https://github.com/jwhayman/woocommerce-multiple-shipping-addresses
Description: Allow WooCommerce customers to have multiple shipping addresses to choose from
Author: James Whayman
Author URI: https://jameswhayman.com/
Text Domain: woomsa
Version: 0.0.1
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_VERSION' ) ) {
	return;
}

define( 'WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_VERSION', '1.0' );
define( 'WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_FILE', __FILE__ );
define( 'WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_PATH', plugin_dir_path( WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_FILE ) );
define( 'WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_URL', plugin_dir_url( WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_FILE ) );

register_activation_hook( WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_FILE, [
	'WooCommerceMultipleShippingAddresses',
	'activate'
] );

register_deactivation_hook( WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_FILE, [
	'WooCommerceMultipleShippingAddresses',
	'deactivate'
] );

final class WooCommerceMultipleShippingAddresses {
	private static $instance;

	private function __construct() {
		// Load includes
		$this->includes();
	}

	public function includes() {
		include WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_PATH . 'lib/functions.php';
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function activate() {

	}

	public static function deactivate() {

	}
}

function woocommerce_multiple_shipping_addresses_singleton() {
	return WooCommerceMultipleShippingAddresses::get_instance();
}

$GLOBALS['woocommerce_multiple_shipping_addresses'] = woocommerce_multiple_shipping_addresses_singleton();
