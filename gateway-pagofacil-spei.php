<?php

class woocommerce_pagofacil_spei extends WC_Payment_Gateway {

    var $transaction = null;

    public function __construct() {
        global $woocommerce;

        $this->id           = 'pagofacil_spei';
        $this->method_title = __( 'PagoFácil Spei', 'woocommerce' );
        $this->icon         = apply_filters( 'woocommerce_pagofacil_cash_icon', '' );
        $this->has_fields   = TRUE;


        // Load the form fields.
        $this->init_form_fields();

        // Load the settings. 
        $this->init_settings();

        $this->is_description_empty();
 
        // Define user set variables
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );

        $this->image    = $this->get_option( 'image' );

        $this->sucursal     = $this->get_option( 'sucursal' );
        $this->usuario      = $this->get_option( 'usuario' );

        $this->sucursal_test    = $this->get_option( 'sucursal_test' );
        $this->usuario_test     = $this->get_option( 'usuario_test' );

        $this->testmode     = $this->get_option( 'testmode' );
        $this->showdesc     = $this->get_option( 'showdesc' );

        $this->concept      = $this->get_option( 'concept' );

        $this->instructions     = $this->get_option( 'instructions' );

        $this->webhook     = $this->get_option( 'webhook' );
        $this->webhook_url = 'https://api.pagofacil.tech/Stp/webhookspei/crear';

        if($this->testmode == 'yes'){
            $this->request_url = 'https://sandbox.pagofacil.tech/Stp/cuentaclave/crear';
            $this->use_sucursal = $this->sucursal_test;
            $this->use_usuario = $this->usuario_test;
        }else{
            $this->request_url = 'https://api.pagofacil.tech/Stp/cuentaclave/crear';
            $this->use_sucursal = $this->sucursal;
            $this->use_usuario = $this->usuario;
        }

        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'receipt_page' ) , 1);
        add_action( 'woocommerce_view_order_'. $this->id, array( $this, 'receipt_page' ), 1 );

        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    function get_icon() {
        global $woocommerce;

        $icon = '';
        if ( $this->icon ) {
            // default behavior
            $icon = '<img src="' . $this->forceSSL( $this->icon ) . '" alt="' . $this->title . '" />';
        } elseif ( $this->image ) {


            $icon = '<img src="' . $this->forceSSL( $this->image ) . '" alt="' . $this->title . '" />';

        }

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }

    /**
     * To Check if Description is Empty
     */
    function is_description_empty() {

        $showdesc = '';

        return($showdesc);
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {

        ?>
        <h3><?php _e('PagoFácil SPEI', 'pagofacil'); ?></h3>
        <p><?php _e('PagoFácil Pagos SPEI', 'pagofacil'); ?></p>
        <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
        </table><!--/.form-table-->
        <?php
    } // End admin_options()

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

        $currency_code_options = get_woocommerce_currencies();

        //unset($currency_code_options['MXN']);

        foreach ( $currency_code_options as $code => $name ) {
            $currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Habilitar/Inhabilitar', 'pagofacil SPEI' ),
                'type' => 'checkbox',
                'label' => __( 'Habilitar PagoFácil pagos SPEI', 'pagofacil' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Título', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Título que el usuario ve durante el proceso de pago.', 'pagofacil' ),
                'default' => __( 'Realizar el pago con SPEI.', 'pagofacil' )
            ),
            /*'showdesc' => array(
                'title' => __( 'Mostrar descripción', 'pagofacil SPEI' ),
                'type' => 'checkbox',
                'label' => __( 'Mostrar descripción?', 'pagofacil SPEI' ),
                'default' => 'no'
            ),
            'description' => array(
                'title' => __( 'Descripción', 'pagofacil SPEI' ),
                'type' => 'textarea',
                'description' => __( 'Controla la descripción que el usuario ve durante el proceso de pago.', 'pagofacil' ),
                'default' => __("Realizar el pago SPEI.", 'pagofacil')
            ),*/
            /*'image' => array(
                'title' => __( 'Imagen', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Esta imagen aparecerá durante el checkout. Esto es puramente estetico', 'pagofacil' ),
                'default' => plugins_url( 'logo_pagofacil.png' , __FILE__ ),
            ),*/

            'sucursal' => array(
                'title' => __( 'Sucursal pruducción', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Sucursal; Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
            
            'usuario' => array(
                'title' => __( 'Usuario pruducción', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Usuario; Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),

            'webhook' => array(
                'title' => __( 'Notificaciones Automáticas', 'woocommerce SPEI' ),
                'type' => 'textarea',
                'description' => __( 'Si requiere notificaciones automáticas, agrege esta URL dentro de la sección Webhook del Manager PagoFácil: <a href="https://manager.pagofacil.net/configuraciones/webhooks/" target="_blank"> https://manager.pagofacil.net/configuraciones/webhooks/ </a>', 'woocommerce' ),
                'default' => plugins_url( 'webhooks.php' , __FILE__ ),
            ),

            'concept' => array(
                'title' => __( 'Concepto', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Concepto que aparecera en la referencia de las tiendas de conveniencia.', 'pagofacil' ),
                'default' => get_bloginfo('name')
            ),

            'instructions' => array(
                'title' => __( 'Instrucciones de pago', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Instrucciones que aparecera en la thank you page.', 'pagofacil' ),
                'default' => __('Las instrucciones para realizar tu pago han sido enviadas a tu correo electrónico.', 'pagofacil'),
            ),

            'testmode' => array(
                'title' => __( 'Sandbox', 'pagofacil SPEI' ),
                'type' => 'checkbox',
                'label' => __( 'Habilitar Sandbox', 'pagofacil SPEI' ),
                'default' => 'no'
            ),

            'sucursal_test' => array(
                'title' => __( 'Sucursal Sandbox', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Sucursal (Sandbox); Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
            'usuario_test' => array(
                'title' => __( 'Usuario Sandbox', 'pagofacil SPEI' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Usuario (Sandbox); Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
        );

    } // End init_form_fields()

    public function validate_fields()
    {
        global $woocommerce;
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment( $order_id ) {
        global $woocommerce;

        $hook = array(
            'idSucursal'        => $this->use_sucursal,
            'idUsuario'         => $this->use_usuario,
            'webhook'         => $this->webhook,
        );

        $response_hook  = wp_remote_post(
            $this->webhook_url,
            array(
                'method' => 'POST',
                'body' => $hook,
                'timeout' => 120,
                'httpversion' => '1.0',
                'sslverify' => false
            )
        );


        $order = new WC_Order( $order_id );

        $transaction = array(

            'idSucursal'        => $this->use_sucursal,
            'idUsuario'         => $this->use_usuario,
            'id_pedido'         => $order_id,
            'concepto'          => $this->concept .$order_id,
            'monto'             => $order->get_total(),
            'customer'          => $order->billing_first_name . ' ' . $order->billing_last_name ,
            'email'             => $order->billing_email,
            'fecha_expiracion'  => '',
            'stp_c_origin_id'   => 1,
        );

        $response = wp_remote_post(
            $this->request_url,
            array(
                'method' => 'POST',
                'body' => $transaction,
                'timeout' => 120,
                'httpversion' => '1.0',
                'sslverify' => false
            )
        );
        
        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {

            error_log(date("Y-m-d H:i:s")." -- Response: ".print_r($response, true)."\n", 3, "./logs/cash.log");
            $response = json_decode($response['body'],true);

            //die(print_r($response, true));

            if( $response['error'] == "" && !empty($response['cuenta_clabe']) ) {

                $woocommerce->cart->empty_cart();

                session_start();

                $_SESSION['order_id'] = $order_id;
                $_SESSION['stp_cuenta'] = $response['cuenta_clabe'];
                $_SESSION['stp_monto'] = $response['monto'];

                $order->add_order_note( sprintf( __('Orden generada para pago en %s.', 'pagofacil'), $response['cuenta_clabe']) );

                wc_add_notice( $this->instructions , 'success');

                return array(
                    'result'    => 'success',
                    'redirect'  =>  $this->get_return_url($order)
                );

            }else{
                $this->showError(sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['body']['msgError'] ));
                $order->add_order_note( sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['body']['msgError'] ) );
            }

        }else{
            $this->showError(__('Gateway Error. Please Notify the Store Owner about this error.', 'pagofacil'));
            $response = json_decode($response['body'],true);
            foreach ( $response['errors'] as $code => $name ) {
                $this->showError(sprintf( __('Transaction Failed. %s', 'pagofacil'),   print_r($name, true)  ));
            }

            $order->add_order_note(__('Gateway Error. Please Notify the Store Owner about this error.', 'pagofacil'));
        }
    }

    public function receipt_page( $order_id ) {
        session_start();
        if( !empty($_SESSION['order_id']) && $_SESSION['order_id'] == $order_id ){
            include( dirname(__FILE__). '/template/confirmspei.php' );
        }else{
            echo "";
        }
    }

    /**
     *
     * Envia mesajes de error al checkout segun la version
     * @param $message string
     * @return string
     */
    private function showError($message) {
        global $woocommerce;

        if (function_exists('wc_add_notice')) { // version >= 2.3
            wc_add_notice($message, 'error');
        } else { // version < 2.3
            $woocommerce->add_error($message);
        }
    }

    /**
     *
     * Envia mesajes de error al checkout segun la version
     * @param $url string
     * @return string
     */
    private function forceSSL($url) {
        global $woocommerce;

        if (class_exists('WC_HTTPS')) { // version >= 2.3
            return WC_HTTPS::force_https_url($url);
        } else { // version < 2.3
            return $woocommerce->force_ssl($url);
        }
    }


}