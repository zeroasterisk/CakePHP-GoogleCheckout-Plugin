# Google Checkout - CakePHP Plugin #

This isn't a project which is supposed to drop into your system and do everything for you, but it should provide some useful tools and hooks to get things going.

    Uses the Google Checkout PHP Code Samples
    http://code.google.com/p/google-checkout-php-sample-code/


* Repository: https://github.com/zeroasterisk/CakePHP-GoogleCheckout-Plugin
* Requirements: 
 * CakePHP 2.1 (might work with 2.0)
 * Google Checkout Merchant Account

## Installation ##

    git submodule add https://github.com/zeroasterisk/CakePHP-GoogleCheckout-Plugin app/Plugin/GoogleCheckout

or download and extract to:

    cd path_to_app
    mv CakePHP-GoogleCheckout-Plugin Plugin/GoogleCheckout

## Configure & Initialize ##

(WIP)

## Basic Usage ##

There is going to be a lot of different things you can do with the Google Checkout API -- so really I've just got things started.  You can extend as you see fit.

### Usage to Create a Cart/Checkout Order ###

In any controller, you can setup a method like this to facilitate the Google Cart Checkout functionality

    <?php /* in a controller */
    /**
    * prep the google checkout data
    */
    private function __googleCart($order=null) {
    if (empty($order)) {
    	return false;
    }
    $this->GoogleCheckoutCart->setup();

    // set custom tracking data, for this order
    $this->GoogleCheckoutCart->cart->SetMerchantPrivateData(new MerchantPrivateData(array(
    	"order_id" => $order['Order']['id'],
    	"created" => $order['Order']['created'],
    	"user_id" => $order['Order']['user_id'],
    	"design_id" => $order['Order']['design_id'],
    	"submitted_from" => Router::url($this->here, true),
    	"admin_url" => Router::url("/admin/orders/view/{$order['Order']['id']}", true),
    	"mod_url" => Router::url("/mod/orders/view/{$order['Order']['id']}", true),
    	)));


    // put in items (assumes you already have them in your $order data)
    foreach ( $order['OrderItem'] as $orderItem ) {
    	$this->GoogleCheckoutCart->item(array(
    		'name' => $orderItem['name'],
    		'description' => OrderItem::desc($orderItem),
    		'quantity' => max($orderItem['qty'], 1),
    		'unit_price' => $orderItem['cost'],
    		'numeric_weight' => max($orderItem['qty'], 1),
    		'item_weight' => 'LB',
    		'sku' => $orderItem['sku'],
    		));
    }

    // setup shipping
    if (count($order['OrderItem']) < 3) {
    	   $shipping = new GoogleFlatRateShipping("USPS Priority Mail", 4.55);
    } else {
    	   $shipping = new GoogleFlatRateShipping("USPS Priority Mail", 6.2);
    }
    $Gfilter = new GoogleShippingFilters();
    $Gfilter->SetAllowedCountryArea('CONTINENTAL_48');
    $shipping->AddShippingRestrictions($Gfilter);
    $this->GoogleCheckoutCart->cart->AddShipping($shipping);

    // setup taxes
    $this->GoogleCheckoutCart->tax();

    //debug($this->GoogleCheckoutCart->cart->GetXML());

    // finalize
    if ($this->request->is('post')) {
    	// checkout hit, send XML data via API and redirect
    	$this->GoogleCheckoutCart->cart->SetAnalyticsData($_POST['analyticsdata']);
    	$order['Order']['status'] = 'checkout';
    	$this->Order->save($order);
    	$this->GoogleCheckoutCart->cart->CheckoutServer2Server();
    	// should redirect on it's own... unless there's an error
    } else {
    	// will setup a button/JS/form to submit back to self, and redirect away to Google Checkout
    	$checkout_button = $this->GoogleCheckoutCart->cart->CheckoutServer2ServerButton($this->here, 'large', true);
    	$this->set(compact('checkout_button'));
    }
    }
    ?>

### Usage to Create a Checkout Order Callback Action ###

(WIP)

## License ##

Copyright 2012, [Zeroasterisk](http://zeroasterisk.com)

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ##

Copyright 2012<br/>
[Zeroasterisk](http://zeroasterisk.com)<br/>
