<?php
/*
 * Plugin Name: IOSTPAY
 * Plugin URI: https://github.com/metanyx-official/iostpay-woocommerce
 * Description: Pay with IOST
 * Author: Metanyx
 * Author URI: https://metanyx.com/
 * Version: 1.0.1
 *

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'iostpay_add_gateway_class' );
function iostpay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_iostpay_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'iostpay_init_gateway_class' );
function iostpay_init_gateway_class() {
	class WC_Iostpay_Gateway extends WC_Payment_Gateway {

		/**
		 * Class constructor, more about it in Step 3
		 */
		public function __construct() {
			$this->id                 = 'iostpay'; // payment gateway plugin ID
			$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields         = false; // in case you need a custom credit card form
			$this->method_title       = 'IOSTPay';
			$this->method_description = 'IOSTPay'; // will be displayed on the options page
			$this->keyname            = ''; // will be displayed on the options page

			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this tutorial we begin with simple payments
			$this->supports = array(
				'products',
			);

			// Method with all the options fields
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			$this->title = $this->get_option( 'title' );

			$this->description = $this->get_option( 'description' );

			$this->enabled = $this->get_option( 'enabled' );

			$this->iostpay_account_id = $this->get_option( 'iostpay_account_id' );
			$this->cmc_api_key        = $this->get_option( 'cmc_api_key' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_custom', array( $this, 'thankyou_page' ) );

			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		}

		/**
		 * Plugin options, we deal with it in Step 3 too
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable IOSTPay',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'              => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Credit Card',
					'desc_tip'    => true,
				),
				'description'        => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => '',
				),
				'iostpay_account_id' => array(
					'title'       => 'IOSTPay Account Id',
					'type'        => 'text',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => '',
				),
				'cmc_api_key'        => array(
					'title'       => 'CMC API KEY',
					'type'        => 'text',
					'description' => 'This controls the description which the user sees during
				checkout.',
					'default'     => '',
				),

			);
		}

		/*
		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
			// if( empty( $_POST[ 'billing_first_name' ]) ) {
			// wc_add_notice(  'First name is required!', 'error' );
			// return false;
			// }

			return true;

		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
		public function payment_scripts() {
		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */

		public function process_payment( $order_id ) {
			global $woocommerce;
			// $order_status =        $_POST['iostpay_status'] ;

			$order = wc_get_order( $order_id );

			// $status = 'wc-'.$order_status ;

			// $status = 'wc-completed' ;

			// $order->add_order_note( json_encode( $_POST ) );

			// update_post_meta( $order_id, '_iostpay_txnid_field', $_POST[ 'iostpay_txnid' ] );
			// Set order status
			// $order->update_status( $status, __( 'Checkout with IOSTPay.', $this->domain ) );

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();

			// Return thankyou redirect

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */

		public function webhook() {
			$order = wc_get_order( $_GET['id'] );
			$order->payment_complete();
			$order->reduce_order_stock();

			update_option( 'webhook_debug', $_GET );
		}

	}
}

function convertPriceToIOST( $invoice_amount ) {
	$symbol = get_option( 'woocommerce_currency' );

	$myPluginGateway = new WC_Iostpay_Gateway();

	// $iostpay_account_id = $myPluginGateway->get_option('iostpay_account_id');

	$cmc_api_key = $myPluginGateway->get_option( 'cmc_api_key' );

	$url = 'https://pro-api.coinmarketcap.com/v1/tools/price-conversion';

	$parameters = array(
		'symbol'  => $symbol,
		'amount'  => $invoice_amount,
		'convert' => 'IOST',
	);

	$CMC_KEY = $cmc_api_key;
	$headers = array(
		'Accepts: application/json',
		'X-CMC_PRO_API_KEY: ' . $CMC_KEY,
	);

	$qs      = http_build_query( $parameters ); // query string encode the parameters
	$request = "{$url}?{$qs}"; // create the request URL

	$curl = curl_init(); // Get cURL resource
	// Set cURL options
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_URL            => $request, // set the request URL
			CURLOPT_HTTPHEADER     => $headers, // set the headers
			CURLOPT_RETURNTRANSFER => 1, // ask for raw response instead of bool
		)
	);

	$response = curl_exec( $curl ); // Send the request, save the response
	$response = json_decode( $response, true );

	if ( isset( $response ) ) {

		$iostPrice = $response['data']['quote']['IOST']['price'];

		$iost_amount = number_format( (float) $iostPrice, 2, '.', '' );
		return $iost_amount;
	}

}

// Adding Meta container admin shop_order pages
add_action( 'add_meta_boxes', 'iostpay_add_meta_boxes' );
if ( ! function_exists( 'iostpay_add_meta_boxes' ) ) {
	function iostpay_add_meta_boxes() {
		 add_meta_box( 'iostpay_txnid', __( 'IOSTPay TxnId', 'woocommerce' ), 'iostpay_add_other_fields_for_packaging', 'shop_order', 'side', 'core' );
	}
}

// Adding Meta field in the meta container admin shop_order pages
if ( ! function_exists( 'iostpay_add_other_fields_for_packaging' ) ) {
	function iostpay_add_other_fields_for_packaging() {
		 global $post;

		$meta_field_data = get_post_meta( $post->ID, '_iostpay_txnid_field', true ) ? get_post_meta( $post->ID, '_iostpay_txnid_field', true ) : '';

		echo '<input type="hidden" name="iostpay_other_meta_field_nonce" value="' . wp_create_nonce() . '"> <p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
						<input type="text" style="width:250px;";" name="iostpay_txnid" placeholder="' . $meta_field_data . '" value="' . $meta_field_data . '"></p>';

	}
}

add_action( 'woocommerce_thankyou', 'bbloomer_redirectcustom' );

function bbloomer_redirectcustom( $order_id ) {
	$order = wc_get_order( $order_id );
	$url   = plugin_dir_url( __FILE__ ) . 'paybyiost.php?order=' . $order_id;

	if ( $order->get_payment_method() == 'iostpay' && $order->has_status( 'pending' ) ) {
		wp_safe_redirect( $url );
		exit;
	}
}
