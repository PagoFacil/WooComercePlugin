<?php
    /*
    Plugin Name: PagoFácil Gateway for WooCommerce
    Plugin URI: https://github.com/PagoFacil/WooComercePlugin
    Description: WooCommerce Plugin for accepting payment through PagoFácil gateway.
    Author: PagoFácil
    Author URI: https://pagofacil.net/
    */

add_action('plugins_loaded', 'init_woocommerce_pagofacil_direct', 0);

function init_woocommerce_pagofacil_direct()
{
    if (! class_exists('Woocommerce')) {
        return;
    }

    include 'Gateway/Errors/DomainError.php';
    include 'Gateway/Errors/ClientError.php';
    include 'Gateway/Errors/HttpError.php';
    include 'Gateway/Errors/PaymentError.php';
    include 'Gateway/Abstract/PagoFacilPaymentGateway.php';
    include 'gateway-pagofacil-direct.php';
    include 'gateway-pagofacil-cash.php';
    include "PagoFacil_Descifrado_Descifrar.php";

    /**
     * Add the gateway to WooCommerce
     * @param array $methods
     * @return array
     */
    function add_pagofacil_direct_gateway($methods)
    {
        $methods[] = 'woocommerce_pagofacil_direct';
        $methods[] = 'woocommerce_pagofacil_cash';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_pagofacil_direct_gateway');
}

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('pagofacil', null, $plugin_dir);
