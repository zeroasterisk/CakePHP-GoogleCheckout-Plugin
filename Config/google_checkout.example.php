<?php
/**
 * Copy this file to your app/Config directory without the .example.
 * 
 * cp app/Plugin/GoogleCheckout/Config/google_checkout.example.php app/Config/google_checkout.php
 * 
 * then edit to match your configuration
 * 
 * @link  https://checkout.google.com/sell/settings?section=Integration
 * 
 */
define('RESPONSE_HANDLER_ERROR_LOG_FILE', 	TMP.'logs'.DS.'google_checkout_error.log');
define('RESPONSE_HANDLER_LOG_FILE', 		TMP.'logs'.DS.'google_checkout_message.log');


$sandbox = array(
	'ID' => '123123123123123',
	'key' => '0000000000000000000000',
	);
$production = array(
	'ID' => '890890890890890',
	'key' => '0000000000000000000000',
	);
$server_type = (configure::read('debug') > 0 ? 'sandbox' : 'production');

$config = array('GoogleCheckout' => $$server_type + array(
	'server_type' => $server_type,
	'currency' => 'USD',
	'edit_cart_url' => Router::url('/orders/cart', true),
	'continue_shopping_url' => Router::url('/', true),
	'request_buyers_phone' => true,
	'google_analytics' => 'UA-XXXXXXXX-1', // configure::read('ga')
	// item defaults
	'Item' => array(
		'numeric_weight' => '1', // default weight
		'item_weight' => 'LB' // default weight units
		),
	// shipping
	'Tax' => array(
		'us_rate' => 0.06, // default tax rate for US states
		'us_states' => array('KY'), // taxable states (empty to disable)
		'international_rate' => 0.16, // default tax rate for international
		'international_areas' => array('GB', 'FR', 'DE'), // taxable countries (empty to disable)
		),
	// shipping
	'Shipping' => array(
		// shipping from what location (default value)
		'From' => array(
			'id' => 'Shipping From Name', 
			'city' => 'Louisville',
			'country_code' => 'US',
			'postal_code' => '40206',
			'region' => 'KY',
			),
		// shipping options in your profile
		// shipping package defaults options
		'Package' => array(
			'width' => '12',
			'length' => '15',
			'height' => '2',
			'unit' => 'IN', // IN, CM
			),
		),
	// misc
	'RoundingPolicy' => array(
		'mode' => 'HALF_UP', // one of "UP", "DOWN", "CEILING", "HALF_DOWN" or "HALF_EVEN", described here: {@link http://java.sun.com/j2se/1.5.0/docs/api/java/math/RoundingMode.html}
		'rule' => 'PER_LINE', // one of "PER_LINE", "TOTAL"
		),
));