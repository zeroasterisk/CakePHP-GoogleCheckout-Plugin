<?php

/**
 * Here is a fully functional, but limiting implementation of the Google Merchant XML API
 *
 * If you want to customize this, I recommend you replicate parts of this, in your own model,
 * you will be able to fine-tune the cart to your hearts desire... (you should still be able to use setup())
 *
 */
class GoogleCheckoutCart extends GoogleCheckoutAppModel {

	public $useTable = false;
	public $cart = null;


	/**
	 * helper to initialize the cart and all vendor libraries needed for transactions
	 * @return bool
	 */
	public function setup() {
		configure::load('google_checkout');
		App::import('Vendor', 'GoogleCheckout.GoogleCart', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googlecart.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleItem', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googleitem.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleMerchantCalculations', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googlemerchantcalculations.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleFlatRateShipping', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googleshipping.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleTaxRule', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googletax.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleRequest', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googleresult.php'));
		App::import('Vendor', 'GoogleCheckout.GoogleResponse', array('file' => 'checkout-php-1.3.1'.DS.'library'.DS.'googlerequest.php'));

		// Create a new shopping cart object
		$merchant_id = configure::read('GoogleCheckout.ID');  // Your Merchant ID
		$merchant_key = configure::read('GoogleCheckout.key');  // Your Merchant Key
		$server_type = configure::read('GoogleCheckout.server_type');
		$currency = configure::read('GoogleCheckout.currency');
		$this->cart = new GoogleCart($merchant_id, $merchant_key, $server_type, $currency);

		// Define rounding policy
		extract(configure::read('GoogleCheckout.RoundingPolicy'));
		$this->cart->AddRoundingPolicy($mode, $rule);

		// set other settings
		$this->cart->AddGoogleAnalyticsTracking(configure::read('GoogleCheckout.google_analytics'));
		$this->cart->SetEditCartUrl(configure::read('GoogleCheckout.edit_cart_url'));
		$this->cart->SetContinueShoppingUrl(configure::read('GoogleCheckout.continue_shopping_url'));
		$this->cart->SetRequestBuyerPhone(configure::read('GoogleCheckout.request_buyers_phone'));

		return (!empty($this->cart));
	}

	/**
	 * helper to assign an item to cart
	 * @param array $item
	 * @return void
	 */
	public function item($item=array()) {
		// populate defaults, and verify fields
		$item = array_merge(configure::read('GoogleCheckout.Item'), set::filter($item));
		$item = array_merge(array('name' => null, 'description' => null, 'quantity' => null, 'unit_price' => null, 'item_weight' => null, 'numeric_weight' => null), $item);
		extract($item);
		$item = new GoogleItem($name, $description, $quantity, $unit_price, $item_weight, $numeric_weight);
		$item->SetMerchantItemId($sku);
		$this->cart->AddItem($item);
	}

	/**
	 * helper to assign tax details to cart, usually what's in the config file
	 * @param array $tax_settings
	 * @return void
	 */
	public function tax($tax_settings = array()) {
		// populate defaults, and verify fields
		$tax_settings = array_merge(configure::read('GoogleCheckout.Tax'), set::filter($tax_settings));
		$tax_settings = array_merge(array('us_rate' => null, 'us_states' => null, 'international_rate' => null, 'international_areas' => null), $tax_settings);
		extract($tax_settings);

		// Add US tax rules
		if (!empty($tax_rule_us)) {
			$tax_rule_us = new GoogleDefaultTaxRule($us_rate);
			$tax_rule_us->SetStateAreas($us_states);
			$this->cart->AddDefaultTaxRules($tax_rule_us);
		}

		// Add International tax rules
		if (!empty($international_areas)) {
			$tax_rule_international = new GoogleDefaultTaxRule($international_rate);
			foreach ( $international_areas as $area ) {
				$tax_rule_international->AddPostalArea($area);
			}
			$this->cart->AddDefaultTaxRules($tax_rule_international);
		}
	}

	/**
	 * helper to setup shipping details for cart
	 * this returns the GoogleCarrierCalculatedShipping object, 
	 * which you will want to add your own options to...
	 * 
	 * @param array $package
	 * @param array $shippingFrom // often just from config
	 * @return object GoogleCarrierCalculatedShipping
	 */
	public function shipping_setup($package=array(), $shippingFrom=array()) {
		if (!is_array($shippingFrom) || empty($shippingFrom)) {
			$shippingFrom = array();
		}
		// populate defaults, and verify fields
		$shippingFrom = array_merge(configure::read('GoogleCheckout.Shipping.From'), set::filter($shippingFrom));
		$shippingFrom = array_merge(array('id' => null, 'city' => null, 'country_code' => null, 'postal_code' => null, 'region' => null), $shippingFrom);
		extract($shippingFrom);
		$ship_from = new GoogleShipFrom($id, $city, $country_code, $postal_code, $region);
		// populate defaults, and verify fields
		$package = array_merge(configure::read('GoogleCheckout.Shipping.From'), set::filter($package));
		$package = array_merge(array('width' => null, 'length' => null, 'height' => null, 'unit' => null, 'delivery_address_category' => null), $package);
		extract($package);
		$GSPackage = new GoogleShippingPackage($ship_from, $width, $length, $height, $unit, $delivery_address_category);
		$Gshipping = new GoogleCarrierCalculatedShipping('Carrier_shipping');
		$Gshipping->addShippingPackage($GSPackage);
		return $Gshipping;
	}

	/**
	 * this helper could setup some shipping options for cart
	 * but it's more likely you'll want to setup your own shipping options
	 * 
	 * @param object $Gshipping GoogleCarrierCalculatedShipping
	 * @return void
	 */
	public function shipping_options($Gshipping=null) {
		if (empty($Gshipping)) {
			$Gshipping = $this->shipping();
		}
		$CCSoption = new GoogleCarrierCalculatedShippingOption("10.99", "FedEx", "Ground", "0.99");
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("22.99", "FedEx", "Express Saver");
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("24.99", "FedEx", "2Day", "0", "10", 'REGULAR_PICKUP');
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);

		$CCSoption = new GoogleCarrierCalculatedShippingOption("11.99", "UPS", "Ground", "0.99", "5", 'REGULAR_PICKUP');
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("18.99", "UPS", "3 Day Select");
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("20.99", "UPS", "Next Day Air", "0", "10", 'REGULAR_PICKUP');
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);

		$CCSoption = new GoogleCarrierCalculatedShippingOption("9.99", "USPS", "Media Mail", "0", "2", 'REGULAR_PICKUP');
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("15.99", "USPS", "Parcel Post");
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);
		$CCSoption = new GoogleCarrierCalculatedShippingOption("18.99", "USPS", "Express Mail", "2.99", "10", 'REGULAR_PICKUP');
		$Gshipping->addCarrierCalculatedShippingOptions($CCSoption);

		$this->cart->AddShipping($Gshipping);
	}
}
