<?php
/*
@author: PagoFácil
@plugins_url: https://github.com/PagoFacil/WooComercePlugin
@version: 1.2
*/
class woocommerce_pagofacil_direct extends PagoFacilPaymentGateway {
    /** @var int $idServ3ds */
    private $idServ3ds;
    /** @var string $pf_sandbox_service */
    private $pf_sandbox_service = 'https://sandbox.pagofacil.tech/Wsrtransaccion/index/format/json/?method=transaccion';
    /** @var string $pf_sandbox_3ds_service */
    private $pf_sandbox_3ds_service = 'https://sandbox.pagofacil.tech/Woocommerce3ds/Form';
    /** @var string $pf_production_service */
    private $pf_production_service = 'https://api.pagofacil.tech/Wsrtransaccion/index/format/json/?method=transaccion';
    /** @var string $pf_production_3ds_service */
    private $pf_production_3ds_service = 'https://api.pagofacil.tech/Woocommerce3ds/Form';
    /** @var string $title_radioBtn */
    private $title_radioBtn = 'Credit Card';

    public function __construct()
    {
        parent::__construct();
        $this->idServApi = 3;
        $this->idServ3ds = 3;
        $this->id			= 'pagofacil_direct';
        $this->method_title = __( 'PagoFácil Direct', 'woocommerce' );
        $this->icon     	= apply_filters( 'woocommerce_pagofacil_direct_icon', '' );

        $default_card_type_options = array(
            'VISA' 	=> 'Visa',
            'MC'   	=> 'MasterCard',
            'AMEX' 	=> 'American Express',
            'DISC' 	=> 'Discover',
            'JCB'	=> 'JCB',
            'DIN'=> 'DINERS'
        );
        $this->card_type_options = apply_filters( 'woocommerce_pagofacil_direct_card_types', $default_card_type_options );

        $default_msi_options = array(
            'all' => 'All Options',
            '03_MasterCard/Visa' => '03 Months - MasterCard/Visa',
            '06_MasterCard/Visa' => '06 Months - MasterCard/Visa',
            '09_MasterCard/Visa' => '09 Months - MasterCard/Visa',
            '12_MasterCard/Visa' => '12 Months - MasterCard/Visa',
            '03_American Express' => '03 Months - American Express',
            '06_American Express' => '06 Months - American Express',
            '09_American Express' => '09 Months - American Express',
            '12_American Express' => '12 Months - American Express',
        );

        $this->msi_options = apply_filters('woocommerce_pagofacil_direct_msi_options', $default_msi_options);
        $this->title		= $this->get_option( 'title' );
        $this->description	= $this->get_option( 'description' );
        $this->sucursal 	= $this->get_option( 'sucursal' );
        $this->usuario		= $this->get_option( 'usuario' );
        $this->cipherKey	= $this->get_option( 'cipherKey' );
        $this->testmode		= $this->get_option( 'testmode' );
        $this->tdsecure		= $this->get_option( 'tdsecure' );
        $this->sendemail	= $this->get_option( 'sendemail' );
        $this->cardtypes	= $this->get_option( 'cardtypes' );
        $this->showdesc		= $this->get_option( 'showdesc' );
        $this->msi              = $this->get_option( 'msi' );
        $this->msioptions       = $this->get_option('msioptions');

        $this->request_url = $this->getUrlEnvironment();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_pagofacil_3ds', array( $this, 'pagofacilWebhook' ) );
    }

    /**
     * @return string
     */
    public function getTitleRadioBtn()
    {
        return $this->title_radioBtn;
    }

    /**
     * @param string $title_radioBtn
     */
    public function setTitleRadioBtn($title_radioBtn)
    {
        $this->title_radioBtn = $title_radioBtn;
    }

    /**
     * get_icon function.
     * @return string
     */
    public function get_icon() {
        global $woocommerce;

        $icon = '';
        if ( $this->icon ) {
            // default behavior
            $icon = '<img src="' . $this->forceSSL( $this->icon ) . '" alt="' . $this->title . '" />';
        } elseif ( $this->cardtypes ) {
            // display icons for the selected card types
            $icon = '';
            foreach ( $this->cardtypes as $cardtype ) {
                if ( file_exists( plugin_dir_path( __FILE__ ) . '/images/card-' . strtolower( $cardtype ) . '.png' ) ) {
                    $icon .= '<img src="' . $this->forceSSL( plugins_url( '/images/card-' . strtolower( $cardtype ) . '.png', __FILE__ ) ) . '" alt="' . strtolower( $cardtype ) . '" />';
                }
            }
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
        <h3><?php _e('PagoFácil Direct', 'pagofacil'); ?></h3>
        <p><?php _e('PagoFácil Gateway works by charging the customers Credit Card on site.', 'pagofacil'); ?></p>
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
    public function init_form_fields() {

        $currency_code_options = get_woocommerce_currencies();

        unset($currency_code_options['MXN']);

        foreach ( $currency_code_options as $code => $name ) {
            $currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Enable PagoFácil Gateway', 'pagofacil' ),
                'default' => 'yes'
            ),
            'testmode' => array(
                'title' => __( 'Sandbox', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Sandbox', 'pagofacil' ),
                'default' => 'no'
            ),
            'tdsecure' => array(
                'title' => __( '3DS', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Enable all transactions via 3DS', 'pagofacil' ),
                'default' => 'no',
                'description' => __( 'All transaction will be processing by 3D Secure.', 'pagofacil' ),
                'desc_tip'    => true
            ),
            'title' => array(
                'title' => __( 'Title', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'pagofacil' ),
                'default' => __( $this->getTitleRadioBtn(), 'pagofacil' )
            ),
            'showdesc' => array(
                'title' => __( 'Show Description', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'To Show Description', 'pagofacil' ),
                'default' => 'no'
            ),
            'description' => array(
                'title' => __( 'Description', 'pagofacil' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'pagofacil' ),
                'default' => __("Enter your Credit Card Details below.", 'pagofacil')
            ),
            'sucursal' => array(
                'title' => __( 'Sucursal', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Please enter your Sucursal; this is needed in order to take payment.', 'pagofacil' ),
                'default' => ''
            ),
            'usuario' => array(
                'title' => __( 'Usuario', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Please enter your Usuario; this is needed in order to take payment.', 'pagofacil' ),
                'default' => ''
            ),
            'cipherKey' => array(
                'title' => __( 'Cipher key', 'pagofacil' ),
                'type' => 'text',
                'description' => __( 'Please enter your cipher key; this is needed just only if you use 3DS to take payment.', 'pagofacil' ),
                'default' => ''
            ),
            'sendemail' => array(
                'title' => __( 'Enable PagoFacil Notifiaction Emails', 'pagofacil' ),
                'type' => 'checkbox',
                'label' => __( 'Allow PagoFacil to Send Notification Emails.', 'pagofacil' ),
                'default' => 'no'
            ),
            'cardtypes'	=> array(
                'title' => __( 'Accepted Card Logos', 'pagofacil' ),
                'type' => 'multiselect',
                'description' => __( 'Select which card types you accept to display the logos for on your checkout page.  This is purely cosmetic and optional, and will have no impact on the cards actually accepted by your account.', 'pagofacil' ),
                'default' => '',
                'options' => $this->card_type_options,
            )
        ,'msi' => array(
                'title' => __('Installments', 'pagofacil')
            ,'label' => __( 'Enable Installments', 'pagofacil' )
            ,'type' => 'checkbox'
            ,'default' => 'no'
            ),
            'msioptions' => array(
                'title' => __('Installments Options', 'pagofacil'),
                'label' => __('Installments Options', 'pagofacil'),
                'type' => 'multiselect',
                'default' => array('all'),
                'options' => $this->msi_options,
            ),
        );

    }

    /**
     * There are no payment fields for nmi, but we want to show the description if set.
     **/
    public function payment_fields() {
        if ($this->showdesc == 'yes') {
            echo wpautop(wptexturize($this->description));
        } else {
            $this->is_description_empty();
        }
        if($this->tdsecure != 'yes') {
            ?>
            <p class="form-row" style="width:200px;">
                <label>Card Number <span class="required">*</span></label>
                <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16"
                       name="pagofacil_direct_creditcard"/>
            </p>
            <div class="clear"></div>
            <p class="form-row form-row-first" style="width:230px;">
                <label>Expiration Month <span class="required">*</span></label>
                <select name="pagofacil_direct_expdatemonth">
                    <option value=01> 1 - January</option>
                    <option value=02> 2 - February</option>
                    <option value=03> 3 - March</option>
                    <option value=04> 4 - April</option>
                    <option value=05> 5 - May</option>
                    <option value=06> 6 - June</option>
                    <option value=07> 7 - July</option>
                    <option value=08> 8 - August</option>
                    <option value=09> 9 - September</option>
                    <option value=10>10 - October</option>
                    <option value=11>11 - November</option>
                    <option value=12>12 - December</option>
                </select>
            </p>
            <p class="form-row form-row-second" style="width:150px;">
                <label>Expiration Year <span class="required">*</span></label>
                <select name="pagofacil_direct_expdateyear">
                    <?php
                    $today = (int)date('y', time());
                    $today1 = (int)date('Y', time());
                    for ($i = 0; $i < 8; $i++) {
                        ?>
                        <option value="<?php echo $today; ?>"><?php echo $today1; ?></option>
                        <?php
                        $today++;
                        $today1++;
                    }
                    ?>
                </select>
            </p>
            <div class="clear"></div>
            <p class="form-row" style="width:200px;">
                <label>Card CVV <span class="required">*</span></label>

                <input class="input-text" style="width:100px;" type="text" size="5" maxlength="5"
                       name="pagofacil_direct_cvv"/>
            </p>
            <div class="clear"></div>
            <?php
            if ($this->msi == 'yes') {
                $msi_options = array();
                $msi_label_options = array(
                    '03' => '3 Meses',
                    '06' => '6 Meses',
                    '09' => '9 Meses',
                    '12' => '12 Meses',
                );

                if (is_array($this->msioptions)) {
                    $msi_options = array(
                        'MasterCard/Visa' => array('03', '06', '09', '12'),
                        'American Express' => array('03', '06', '09', '12'),
                    );
                    if (!in_array('all', $this->msioptions)) {
                        $msi_options = array(
                            'MasterCard/Visa' => array(),
                            'American Express' => array(),
                        );
                        foreach ($this->msioptions as $option) {
                            $keyValue = explode('_', $option);
                            array_push($msi_options[$keyValue[1]], $keyValue[0]);
                        }
                    }
                }
                ?>
                <p class="form-row" style="width:230px;">
                    <label>Installments</label>
                    <select name="pagofacil_direct_msi" style="width:210px;">
                        <option value="00">Pago en una sola exhibicion</option>
                        <?php
                        foreach ($msi_options as $group => $option) {
                            if (count($option) == 0) {
                                continue;
                            }
                            echo '<optgroup label="' . $group . '"></optgroup>';
                            foreach ($option as $value) {
                                echo '<option value="' . $value . '">'
                                    . $msi_label_options[$value] .
                                    '</option>';
                            }
                        }
                        ?>
                    </select>
                </p>
                <div class="clear"></div>
                <?php
            }
        }
    }

    public function validate_fields()
    {
        if($this->tdsecure != 'yes') {
            global $woocommerce;

            if (!$this->isCreditCardNumber($_POST['pagofacil_direct_creditcard']))
                $this->showError(__('(Credit Card Number) is not valid.', 'pagofacil'));

            if (!$this->isCorrectExpireDate($_POST['pagofacil_direct_expdatemonth'], $_POST['pagofacil_direct_expdateyear']))
                $this->showError(__('(Card Expire Date) is not valid.', 'pagofacil'));

            if (!$_POST['pagofacil_direct_cvv'])
                $this->showError(__('(Card CVV) is not entered.', 'pagofacil'));
        }
    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment( $order_id ) {
        if($this->tdsecure == 'yes'){
            return $this->process_payment_3ds($order_id);
        }
        else{
            return $this->process_payment_without_3ds($order_id);
        }
    }

    /**
     * Obtiene la ip real del comprador
     * @author ivelazquex <isai.velazquez@gmail.com>
     * @return string
     */
    private function getIpBuyer()
    {
        if(isset($_SERVER["HTTP_CLIENT_IP"]))
        {
            if (!empty($_SERVER["HTTP_CLIENT_IP"]))
            {
                if (strtolower($_SERVER["HTTP_CLIENT_IP"]) != "unknown")
                {
                    $ip = $_SERVER["HTTP_CLIENT_IP"];
                    if (strpos($ip, ",") !== FALSE)
                    {
                        $ip = substr($ip, 0, strpos($ip, ","));
                    }
                    return  trim($ip);
                }
            }
        }

        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
        {
            if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
            {
                if (strtolower($_SERVER["HTTP_X_FORWARDED_FOR"]) != "unknown")
                {
                    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                    if (strpos($ip, ",") !== FALSE)
                    {
                        $ip = substr($ip, 0, strpos($ip, ","));
                    }
                    return  trim($ip);
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (strpos($ip, ",") !== FALSE)
        {
            $ip = substr($ip, 0, strpos($ip, ","));
        }
        return  trim($ip);
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

    private function isCreditCardNumber($toCheck)
    {
        if (!is_numeric($toCheck))
            return false;

        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false;

        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            }
            else
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }

        if ($sum > 0 AND $sum % 10 == 0)
            return true;

        return false;
    }

    private function isCorrectExpireDate($month, $year)
    {
        $now       = time();
        $result    = false;
        $thisYear  = (int)date('y', $now);
        $thisMonth = (int)date('m', $now);

        if (is_numeric($year) && is_numeric($month))
        {
            if($thisYear == (int)$year)
            {
                $result = (int)$month >= $thisMonth;
            }
            else if($thisYear < (int)$year)
            {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Se define la URL/endpoint a utilizar para realizar las transacciones
     * @author Johnatan Ayala L.
     * @return string
     */
    private function getUrlEnvironment()
    {
        if($this->testmode == 'yes'){
            $url = $this->pf_sandbox_service;
            if($this->tdsecure == 'yes'){
                $url = $this->pf_sandbox_3ds_service;
                $this->setTitleRadioBtn('Credit Card-3DS');
            }
        }
        else{
            $url = $this->pf_production_service;
            if($this->tdsecure == 'yes'){
                $url = $this->pf_production_3ds_service;
                $this->setTitleRadioBtn('Credit Card-3DS');
            }
        }
        return $url;
    }

    /**
     * Este metodo solo se ejecutara cuando asi se defina en la configuracion
     * y todas las transacciones con TC yTD seran realizadas con 3DS
     * @author Johnatan Ayala
     * @param $order_id
     * @return array
     */
    public function process_payment_3ds( $order_id ) {
        $order = new WC_Order( $order_id );

        $dataTransac = array(
            'idServicio' => $this->idServ3ds,
            "idPedido" => $order_id,
            "nombre" => $order->get_billing_first_name(),
            "apellidos" => $order->get_billing_last_name(),
            "email" => $order->get_billing_email(),
            "calleyNumero"  => $order->get_billing_address_1(),
            "cp" => $order->get_billing_postcode(),
            "colonia" => $order->get_billing_address_2(),
            "municipio" => $order->get_billing_city(),
            "estado" => $order->get_billing_city(),
            "pais" => $order->get_billing_country(),
            "telefono" => $order->get_billing_phone(),
            "celular" => $order->get_billing_phone(),
            "monto" => $order->get_total(),
            "idSucursal" => $this->sucursal,
            "response_redirect" => home_url().'/wc-api/pagofacil_3ds',
            "param1" => $order->get_order_number(),
            "param2" => $order->order_key
        );

        $urlData = _http_build_query($dataTransac, '', '&');
        $urlData = base64_encode($urlData);
        //Redirect es al servico de pagofacil.net
        $this->request_url .= '?pf_user='.$this->usuario.'&data='.$urlData;

        return array(
            'result' => 'success',
            'redirect' => $this->request_url
        );
    }

    /**
     * metodo original para transacciones sin 3DS, de acuedo a la
     * configuracion
     * @param $order_id
     * @return array
     */
    public function process_payment_without_3ds( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );
        $order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->billing_phone );

        $transaction = array(
            'idServicio'        => urlencode($this->idServApi),
            'idSucursal'        => urlencode($this->sucursal),
            'idUsuario'         => urlencode($this->usuario),
            'nombre'            => urlencode($order->billing_first_name),
            'apellidos'         => urlencode($order->billing_last_name),
            'numeroTarjeta'     => urlencode($_POST["pagofacil_direct_creditcard"]),
            'cvt'               => urlencode($_POST["pagofacil_direct_cvv"]),
            'cp'                => urlencode($order->billing_postcode),
            'mesExpiracion'     => urlencode($_POST["pagofacil_direct_expdatemonth"]),
            'anyoExpiracion'    => urlencode($_POST["pagofacil_direct_expdateyear"]),
            'monto'             => urlencode($order->get_total()),//formato 1000.00
            'email'             => urlencode($order->billing_email),
            'telefono'          => urlencode($order->billing_phone), // son 10 digitos
            'celular'           => urlencode($order->billing_phone), // son 10 digitos
            'calleyNumero'      => urlencode($order->billing_address_1),
            'colonia'           => urlencode("N/A"),
            'municipio'         => urlencode($order->billing_city),
            'estado'            => urlencode( ($order->billing_state == '' ? "N/A" : $order->billing_state ) ),
            'pais'              => urlencode($woocommerce->countries->countries[ $order->billing_country ]),
            'idPedido'          => urlencode($order_id),
            'param1'            => urlencode(ltrim($order->get_order_number(), '#')),
            'param2'            => urlencode($order->order_key),
            'param3'            => urlencode(""),
            'param4'            => urlencode(""),
            'param5'            => urlencode(""),
            'ip'                => urlencode($this->getIpBuyer()),
            'httpUserAgent'     => urlencode($_SERVER['HTTP_USER_AGENT'])
        );

        if($this->sendemail != 'yes'){
            $transaction = array_merge( $transaction, array( 'noMail' => urlencode( '1' ) ) );
        }

        if ($this->msi == 'yes')
        {
            if (trim($_POST["pagofacil_direct_msi"]) != '00')
            {
                $transaction = array_merge(
                    $transaction, array(
                        'plan' => urlencode('MSI')
                    ,'mensualidades' => urlencode(trim($_POST["pagofacil_direct_msi"]))
                    )
                );
            }
        }

        $data='';
        foreach ($transaction as $key => $value){
            $data.="&data[$key]=$value";
        }

        $response = wp_remote_post(
            $this->request_url.$data,
            array(
                'method' => 'POST',
                'timeout' => 120,
                'httpversion' => '1.0',
                'sslverify' => false
            )
        );

        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
            $response = json_decode($response['body'], true);
            $response = $response['WebServices_Transacciones']['transaccion'];

            if($response["autorizado"] == "1" && strtolower($response['status']) == 'success') {
                // Payment completed
                $order->add_order_note( sprintf( __('PagoFácil %s. The PagoFácil Transaction ID %s and Authorization ID %s.', 'pagofacil'), $response["texto"], $response["transaccion"], $response["autorizacion"] ) );
                $order->payment_complete();

                return array(
                    'result' 	=> 'success',
                    'redirect'	=>  $this->get_return_url($order)
                );
            }
            else{
                if(isset($response['texto'])){
                    $message = sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['texto'] ).'<br>';
                    foreach( $response['error'] as $k => $v ){
                        $message .= $v.'<br>';
                    }
                    $this->showError($message);
                    $order->add_order_note( $message );
                }else{
                    $this->showError(sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['response']['message'] ));
                    $order->add_order_note( sprintf( __('Transaction Failed. %s', 'pagofacil'), $response['response']['message'] ) );
                }
            }

        }else{
            $error ="Gateway Error.". $response->get_error_message();
            $this->showError(__($error, 'pagofacil'));
            $order->add_order_note(__($error, 'pagofacil'));
        }
    }

    /**
     * metodo/webhook que recibe la respuesta del servicio de 3DS de pagofacil
     * @author Johnatan Ayala
     * @param $_POST
     * @return void
     */
    public function pagofacilWebhook(){
        try {
            $this->validateResponse();
            $cipherKey = $this->get_option('cipherKey');
            $objPF = new PagoFacil_Descifrado_Descifrar();
            $dataResponse = $objPF->desencriptar_php72($_POST['response'], $cipherKey);
            $order = new WC_Order($dataResponse->data->idPedido);
            $order = $this->proccesingOrder($order, $dataResponse);
            $url = $this->get_return_url($order);
            wp_safe_redirect($url);
        } catch (HttpError $exception) {
            error_log($exception->getMessage());
            error_log($exception->getTraceAsString());
        } catch (PaymentError $exception) {
            $this->showError($exception->getMessage());
            $order->add_order_note($exception->getNote());
            error_log($exception->getMessage());
            error_log($exception->getTraceAsString());
            $url = $this->get_return_url($order);
            wp_safe_redirect($url);
        } catch (Exception $exception) {
            error_log($exception->getMessage());
            error_log($exception->getTraceAsString());
        }
    }

    /**
     * @param WC_Order $order
     * @return WC_Order
     */
    private function completeOrder(WC_Order $order, $dataResponse)
    {
        $order->add_order_note( sprintf( __('PagoFácil %s. The PagoFácil Transaction ID %s and Authorization ID %s.', 'pagofacil'), $dataResponse->pf_message, $dataResponse->transaccion, $dataResponse->autorizacion ) );
        $order->payment_complete();

        return $order;
    }

    /**
     * @param WC_Order $order
     * @return WC_Order
     */
    private function paymentDeclined(WC_Order $order, $dataResponse)
    {
        $order->update_status('failed', $dataResponse->pf_message);

        return $order;
    }

    /**
     * @param WC_Order $order
     * @param $dataResponse
     * @return WC_Order
     * @throws
     */
    private function proccesingOrder(WC_Order $order, $dataResponse)
    {
        switch (true) {
            case PagoFacil_Descifrado_Descifrar::AUTORIZADO == $dataResponse->autorizado:
                $order = $this->completeOrder($order, $dataResponse);
                break;
            case PagoFacil_Descifrado_Descifrar::RECHAZADO == $dataResponse->autorizado:
                $order = $this->paymentDeclined($order, $dataResponse);
                break;
            default:
                if(isset($dataResponse->texto)){
                    $message = sprintf( __('Transaction Failed. %s', 'pagofacil'), $dataResponse->texto ).'<br>';
                    foreach( $dataResponse->error as $k => $v ){
                        $message .= $v.'<br>';
                    }
                    throw new PaymentError($message, $message);
                }else{
                    throw new PaymentError(
                        sprintf(
                            __('Transaction Failed. %s', 'pagofacil'),
                            $dataResponse->response->message
                        ),
                        sprintf( __('Transaction Failed. %s', 'pagofacil'), $dataResponse->response->message )
                    );
                }

                $order->update_status('failed', $dataResponse->pf_message);
        }

        return $order;
    }

    /**
     * @return void
     * @throws HttpError
     */
    private function validateResponse()
    {
        if(!isset($_POST['response']) && empty($_POST['response'])) {
            throw new HttpError("La petición response POST no exite");
        }
    }
}
