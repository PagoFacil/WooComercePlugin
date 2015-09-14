<?php

    /*
    Plugin Name: Pago Facil Direct Gateway for WooCommerce
    Plugin URI: http://www.patsatech.com
    Description: WooCommerce Plugin for accepting payment through Pago Facil Direct Gateway.
    Author: IRMAcreative / PatSaTech / Javolero
    Version: 1.1
    Author URI: http://www.irmacreative.com
    */




add_action('plugins_loaded', 'init_woocommerce_pagofacil_direct', 0);

function init_woocommerce_pagofacil_direct() {


	if ( ! class_exists( 'Woocommerce' ) ) { return; }


	include 'gateway-pagofacil-direct.php';
	include 'gateway-pagofacil-cash.php';

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_pagofacil_direct_gateway( $methods ) {
		$methods[] = 'woocommerce_pagofacil_direct'; 
		$methods[] = 'woocommerce_pagofacil_cash'; 

		return $methods;
	}



	add_filter('woocommerce_payment_gateways', 'add_pagofacil_direct_gateway' );

}

$plugin_dir = basename( dirname( __FILE__ ) );
load_plugin_textdomain( 'pagofacil', null, $plugin_dir );