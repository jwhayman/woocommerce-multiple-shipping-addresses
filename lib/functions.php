<?php

namespace WooCommerceMultipleShippingAddresses;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue scripts and styling
function enqueue_scripts() {
	$manifest_path = WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_PATH . 'dist/' . 'mix-manifest.json';
	$manifest      = json_decode( file_get_contents( $manifest_path ) );

	wp_enqueue_style( 'woomsa', WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_URL . 'dist' . $manifest->{'/main.css'} );
	wp_enqueue_script( 'woomsa', WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_URL . 'dist' . $manifest->{'/main.js'}, [
		'jquery',
		'woocommerce',
	], false, true );
	wp_localize_script( 'woomsa', 'ajax_url', admin_url( 'admin-ajax.php' ) );
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

// Filter my account addresses
add_filter( 'woocommerce_my_account_get_addresses', __NAMESPACE__ . '\\filter_get_addresses', 10, 2 );

function filter_get_addresses( $addresses, $customer_id ) {
	$additional_addresses = get_additional_shipping_addresses();
	$addresses            = array_merge( $addresses, $additional_addresses );

	return $addresses;
}

// Get additional shipping address labels
function get_additional_shipping_addresses() {
	global $wpdb;
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user_id = get_current_user_id();

	// Get the addresses
	$query   = "SELECT * FROM {$wpdb->usermeta} WHERE
				{$wpdb->usermeta}.user_id = {$user_id} AND
				{$wpdb->usermeta}.meta_key LIKE \"woomsa-shipping-%\" AND 
				{$wpdb->usermeta}.meta_key LIKE \"%label\"";
	$results = $wpdb->get_results( $query );

	$addresses = [];
	foreach ( $results as $result ) {
		if ( preg_match( '/^(woomsa-shipping-[a-z0-9-]+)/', $result->meta_key, $matches ) ) {
			$addresses[ $matches[1] ] = sprintf( __( 'Shipping address (%s)', 'woomsa' ), $result->meta_value );
		}
	}

	return $addresses;
}

// Get additional shipping address data
function get_additional_shipping_address_data() {
	global $wpdb;
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user_id = get_current_user_id();

	// Get the address data
	$query   = "SELECT * FROM {$wpdb->usermeta} WHERE
				{$wpdb->usermeta}.user_id = {$user_id} AND
				{$wpdb->usermeta}.meta_key LIKE \"woomsa-shipping%\"";
	$results = $wpdb->get_results( $query );

	$addresses = [];
	foreach ( $results as $result ) {
		if ( preg_match( '/^woomsa-(shipping-[a-z0-9-]+)_(.*)$/', $result->meta_key, $matches ) ) {
			$addresses[ $matches[1] ][ $matches[2] ] = $result->meta_value;
		}
	}
}

// Display field to edit addresses
add_action( 'woocommerce_after_edit_account_address_form', __NAMESPACE__ . '\\display_address_editor' );

function display_address_editor() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( is_wc_endpoint_url( 'edit-address' ) ) {
		$address_query = get_query_var( 'edit-address' );
		if ( empty( $address_query ) ) {
			load_template( WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_PATH . 'templates/form-create-shipping-address.php' );
		} else {
			echo '<a href="?action=delete-address">' . __( 'Delete address', 'woomsa' ) . '</a>';
		}
	}
}

// Handle shipping address deletion
add_action( 'template_redirect', __NAMESPACE__ . '\\handle_delete_shipping_address' );

function handle_delete_shipping_address() {
	global $wpdb;
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( is_wc_endpoint_url( 'edit-address' ) ) {
		$address_query = get_query_var( 'edit-address' );
		$action        = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		$user_id       = get_current_user_id();

		if ( ! $address_query || ! $action ) {
			return;
		}

		// Prevent deletion of default addresses
		if ( $address_query === 'shipping' || $address_query === 'billing' ) {
			return;
		}

		if ( strpos( $address_query, 'woomsa' ) === 0 && $action === 'delete-address' ) {
			$query = $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE
				{$wpdb->usermeta}.user_id = %d AND 
				{$wpdb->usermeta}.meta_key LIKE %s",
				$user_id, $address_query . '%' );
			$query = $wpdb->remove_placeholder_escape( $query );
			$wpdb->query( $query );

			wp_redirect( wc_get_endpoint_url( 'edit-address' ) );
			exit();
		}
	}
}

// Handle redirect for shipping address creation
add_action( 'template_redirect', __NAMESPACE__ . '\\handle_create_shipping_address_redirect' );

function handle_create_shipping_address_redirect() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( is_wc_endpoint_url( 'edit-address' ) ) {

		if ( isset( $_GET['woomsa-address-label'] ) ) {
			$label    = filter_input( INPUT_GET, 'woomsa-address-label', FILTER_SANITIZE_STRING );
			$slug     = sanitize_title( $label );
			$redirect = wc_get_endpoint_url( 'edit-address' ) . 'woomsa-shipping-' . $slug;
			$user_id  = get_current_user_id();

			update_user_meta( $user_id, 'woomsa-shipping-' . $slug . '_label', $label );

			wp_redirect( $redirect );
			exit();
		}
	}
}

// Set label
add_filter( 'woocommerce_my_account_edit_address_title', __NAMESPACE__ . '\\set_edit_address_title', 10, 2 );

function set_edit_address_title( $page_title, $load_address ) {
	if ( is_user_logged_in() ) {
		$label = get_user_meta( get_current_user_id(), $load_address . '_label', true );

		if ( $label ) {
			$page_title = $label . ' ' . strtolower( $page_title );
		}
	}

	return $page_title;
}

// Handle address formatting
add_filter( 'woocommerce_my_account_my_address_formatted_address', __NAMESPACE__ . '\\set_formatted_address', 10, 3 );

function set_formatted_address( $address, $customer_id, $address_type ) {

	if ( strpos( $address_type, 'woomsa-shipping', 0 ) !== false ) {
		$address = [
			'address_1'  => get_user_meta( $customer_id, $address_type . '_' . 'address_1', true ),
			'address_2'  => get_user_meta( $customer_id, $address_type . '_' . 'address_2', true ),
			'city'       => get_user_meta( $customer_id, $address_type . '_' . 'city', true ),
			'company'    => get_user_meta( $customer_id, $address_type . '_' . 'company', true ),
			'country'    => get_user_meta( $customer_id, $address_type . '_' . 'country', true ),
			'first_name' => get_user_meta( $customer_id, $address_type . '_' . 'first_name', true ),
			'last_name'  => get_user_meta( $customer_id, $address_type . '_' . 'last_name', true ),
			'postcode'   => get_user_meta( $customer_id, $address_type . '_' . 'postcode', true ),
			'state'      => get_user_meta( $customer_id, $address_type . '_' . 'state', true ),
		];
	}

	return $address;
}

// Display shipping options at checkout
function display_shipping_options_at_checkout( $checkout ) {

	// Check the user has an account
	if ( is_user_logged_in() ) {
		$shipping_addresses = get_additional_shipping_addresses();

		require_once WOOCOMMERCE_MULTIPLE_SHIPPING_ADDRESSES_PATH . 'templates/form-select-shipping-address.php';
	}
}

add_filter( 'woocommerce_before_checkout_shipping_form', __NAMESPACE__ . '\\display_shipping_options_at_checkout', 10, 1 );

// Get shipping address details via ajax
function ajax_get_shipping_address_details() {
	$address     = filter_input( INPUT_POST, 'address', FILTER_SANITIZE_STRING );
	$customer_id = get_current_user_id();

	if ( ! $address ) {
		$address = 'shipping';
	}

	$address = [
		'address_1'  => get_user_meta( $customer_id, $address . '_' . 'address_1', true ),
		'address_2'  => get_user_meta( $customer_id, $address . '_' . 'address_2', true ),
		'city'       => get_user_meta( $customer_id, $address . '_' . 'city', true ),
		'company'    => get_user_meta( $customer_id, $address . '_' . 'company', true ),
		'country'    => get_user_meta( $customer_id, $address . '_' . 'country', true ),
		'first_name' => get_user_meta( $customer_id, $address . '_' . 'first_name', true ),
		'last_name'  => get_user_meta( $customer_id, $address . '_' . 'last_name', true ),
		'postcode'   => get_user_meta( $customer_id, $address . '_' . 'postcode', true ),
		'state'      => get_user_meta( $customer_id, $address . '_' . 'state', true ),
	];

	die( json_encode( $address ) );
}

add_action( 'wp_ajax_woomsa_get_shipping_address_details', __NAMESPACE__ . '\\ajax_get_shipping_address_details' );