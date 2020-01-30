<?php
/**
@author: PagoFácil
@plugins_url: https://github.com/PagoFacil/WooComercePlugin
@version: 1.2
*/
class woocommerce_pagofacil_cash extends PagoFacilPaymentGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->id           = 'pagofacil_cash';
        $this->method_title = __( 'PagoFácil Cash', 'woocommerce' );
        $this->icon         = apply_filters( 'woocommerce_pagofacil_cash_icon', '' );
        $this->stores_endpoint = "https://api.pagofacil.tech/cash/Rest_Conveniencestores";
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

        if($this->testmode == 'yes'){
            $this->request_url = 'https://sandbox.pagofacil.tech/cash/charge';
            $this->use_sucursal = $this->sucursal_test;
            $this->use_usuario = $this->usuario_test;
        }else{
            $this->request_url = 'https://api.pagofacil.tech/cash/charge';
            $this->use_sucursal = $this->sucursal;
            $this->use_usuario = $this->usuario;
        }

        add_action( 'woocommerce_thankyou', array( $this, 'receipt_page' ) , 1);
        add_action( 'woocommerce_view_order' , array( $this, 'receipt_page' ), 1 );
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
    }

    /**
     * get_icon function.
     * @return string
     */
    public function get_icon() {
        global $woocommerce;

        $icon = '';
        if ( $this->icon ) {
            $icon = '<img src="' . $this->forceSSL( $this->icon ) . '" alt="' . $this->title . '" />';
        } elseif ( $this->image ) {
            $icon = '<img src="' . $this->forceSSL( $this->image ) . '" alt="' . $this->title . '" />';
        }

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {
        ?>
        <h3><?php _e('PagoFácil Cash', 'pagofacil'); ?></h3>
        <p><?php _e('PagoFácil Pagos en Efectivo', 'pagofacil'); ?></p>
        <table class="form-table">
            <?php
            $this->generate_settings_html();
            ?>
        </table><!--/.form-table-->
        <?php
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $currency_code_options = get_woocommerce_currencies();

        foreach ( $currency_code_options as $code => $name ) {
            $currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Habilitar/Inhabilitar', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Habilitar PagoFácil pagos en efectivo', 'pagofacil' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Título', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Título que el usuario ve durante el proceso de pago.', 'pagofacil' ),
                'default' => __( 'Introduzca los detalles de pago en efectivo.', 'pagofacil' )
            ),
            'showdesc' => array(
                'title' => __( 'Mostrar descripción', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Mostrar descripción?', 'pagofacil' ),
                'default' => 'no'
            ),
            'description' => array(
                'title' => __( 'Descripción', 'pagofacil' ),
                'type' => 'textarea',
                'description' => __( 'Controla la descripción que el usuario ve durante el proceso de pago.', 'pagofacil' ),
                'default' => __("Introduzca los detalles de pago en efectivo a continuación.", 'pagofacil')
            ),
            'image' => array(
                'title' => __( 'Imagen', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Esta imagen aparecerá durante el checkout. Esto es puramente estetico', 'pagofacil' ),
                'default' => plugins_url( 'logo_pagofacil.png' , __FILE__ ),
            ),
            'sucursal' => array(
                'title' => __( 'Sucursal pruducción', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Sucursal; Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
            'usuario' => array(
                'title' => __( 'Usuario pruducción', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Usuario; Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
            'webhook' => array(
                'title' => __( 'Notificaciones Automáticas', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'Si requiere notificaciones automáticas, agrege esta URL dentro de la sección Webhook del Manager PagoFácil: <a href="https://manager.pagofacil.net" target="_blank"> https://manager.pagofacil.net </a>', 'woocommerce' ),
                'default' => plugins_url( 'webhook.php' , __FILE__ ),
            ),
            'concept' => array(
                'title' => __( 'Concepto', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Concepto que aparecera en la referencia de las tiendas de conveniencia.', 'pagofacil' ),
                'default' => get_bloginfo('name')
            ),
            'instructions' => array(
                'title' => __( 'Instrucciones de pago', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Instrucciones que aparecera en la thank you page.', 'pagofacil' ),
                'default' => __('Las instrucciones para realizar tu pago han sido enviadas a tu correo electrónico.', 'pagofacil'),
            ),

            'testmode' => array(
                'title' => __( 'Sandbox', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Habilitar Sandbox', 'pagofacil' ),
                'default' => 'no'
            ),

            'sucursal_test' => array(
                'title' => __( 'Sucursal Sandbox', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Sucursal (Sandbox); Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
            'usuario_test' => array(
                'title' => __( 'Usuario Sandbox', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Por favor ingrese su Usuario (Sandbox); Esto es necesario para generar la orden de pago.', 'pagofacil' ),
                'default' => ''
            ),
        );

    }

    /**
     * There are no payment fields for nmi, but we want to show the description if set.
     **/
    public function payment_fields() {
        if ($this->showdesc == 'yes') {
            echo wpautop(wptexturize($this->description));
        }
        else {
            $this->is_description_empty();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->stores_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $response = json_decode($output);

        if ($info['http_code'] != 200 || !is_object($response) || !property_exists($response, 'records')) {
            $store_codes = array();
        } else {
            $store_codes = $response->records;
        }
        ?>
        <p class="form-row" style="width:230px;">
            <label><?php echo __('Tienda de conveniencia:', 'pagofacil') ?></label>
            <select name="pagofacil_cash_store_code" style="width:210px;">
                <?php
                foreach ($store_codes as $option) {
                    echo '<option value="'.$option->code.'">'
                        .$option->name.
                        '</option>';
                }
                ?>
            </select>
        </p>
        <div class="clear"></div>
        <?php
    }

    public function validate_fields()
    {
        global $woocommerce;
    }

    /**
     * Process the payment and return the result
     *
     * @param $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );

        $transaction = array(
            'branch_key'    => $this->use_sucursal,
            'user_key'      => $this->use_usuario,
            'order_id'      => $order_id,
            'product'       => $this->concept,
            'amount'        => $order->get_total(),
            'store_code'    => $_POST["pagofacil_cash_store_code"],
            'customer'      => $order->billing_first_name . ' ' . $order->billing_last_name ,
            'email'         => $order->billing_email,
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

            if( isset($response['error']) && $response['error'] == 0  && isset($response['charge']) ){
                $woocommerce->cart->empty_cart();
                session_start();
                $_SESSION['order_id'] = $order_id;
                $_SESSION['transaction'] = $response["charge"];
                $order->add_order_note( sprintf( __('Orden generada para pago en %s.', 'pagofacil'), $response["charge"]['convenience_store']) );
                wc_add_notice( $this->instructions , 'success');

                return array(
                    'result'    => 'success',
                    'redirect'  =>  $this->get_return_url($order)
                );
            }else{
                $this->showError(sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['message'] ));
                $order->add_order_note( sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['message'] ) );
            }
        }else{
            $this->showError(__('Gateway Error. Please Notify the Store Owner about this error.', 'pagofacil'));
            $order->add_order_note(__('Gateway Error. Please Notify the Store Owner about this error.', 'pagofacil'));
        }
    }

    public function receipt_page( $order_id ) {
        session_start();
        if( !empty($_SESSION['transaction']) && !empty($_SESSION['order_id']) && $_SESSION['order_id'] == $order_id ){
            include( dirname(__FILE__). '/template/confirm.php' );
        }
    }

    /**
     *
     * Envia mesajes de error al checkout segun la version
     * @author ivelazquex <isai.velazquez@gmail.com>
     * @param $message string
     * @return string
     */
    private function showError($message) {
        global $woocommerce;

        if (function_exists('wc_add_notice')) {
            wc_add_notice($message, 'error');
        } else {
            $woocommerce->add_error($message);
        }
    }

    /**
     *
     * Envia mesajes de error al checkout segun la version
     * @author ivelazquex <isai.velazquez@gmail.com>
     * @param $url string
     * @return string
     */
    private function forceSSL($url) {
        global $woocommerce;

        if (class_exists('WC_HTTPS')) {
            return WC_HTTPS::force_https_url($url);
        } else {
            return $woocommerce->force_ssl($url);
        }
    }
}