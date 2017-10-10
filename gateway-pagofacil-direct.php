<?php

class woocommerce_pagofacil_direct extends WC_Payment_Gateway {

	public function __construct() {
		global $woocommerce;

        $this->id			= 'pagofacil_direct';
        $this->method_title = __( 'Pago Facil Direct', 'woocommerce' );
		$this->icon     	= apply_filters( 'woocommerce_pagofacil_direct_icon', '' );
        $this->has_fields 	= TRUE;

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
                
		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->is_description_empty();

		// Define user set variables
		$this->title		= $this->get_option( 'title' );
		$this->description	= $this->get_option( 'description' );
		$this->sucursal 	= $this->get_option( 'sucursal' );
		$this->usuario		= $this->get_option( 'usuario' );
		$this->testmode		= $this->get_option( 'testmode' );
		$this->enabledivisa	= $this->get_option( 'enabledivisa' );
		$this->sendemail	= $this->get_option( 'sendemail' );
		$this->divisa		= $this->get_option( 'divisa' );
		$this->cardtypes	= $this->get_option( 'cardtypes' );
		$this->showdesc		= $this->get_option( 'showdesc' );                
                // add 10/03/2014
                $this->msi              = $this->get_option( 'msi' );
                $this->msioptions       = $this->get_option('msioptions');
		
                
		if($this->testmode == 'yes'){
			$this->request_url = 'https://sandbox.pagofacil.net/Wsrtransaccion/index/format/json/?method=transaccion';
		}else{
			$this->request_url = 'https://api.pagofacil.tech/Wsrtransaccion/index/format/json/?method=transaccion';
		}
		
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
    	<h3><?php _e('Pago Facil Direct', 'pagofacil'); ?></h3>
    	<p><?php _e('Pago Facil Gateway works by charging the customers Credit Card on site.', 'pagofacil'); ?></p>
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
		
		unset($currency_code_options['MXN']);
		
		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
    	
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'pagofacil' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Pago Facil Gateway', 'pagofacil' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'pagofacil' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'pagofacil' ),
							'default' => __( 'Credit Card', 'pagofacil' )
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
			'sendemail' => array(
							'title' => __( 'Enable PagoFacil Notifiaction Emails', 'pagofacil' ), 
							'type' => 'checkbox', 
							'label' => __( 'Allow PagoFacil to Send Notification Emails.', 'pagofacil' ), 
							'default' => 'no'
						),
			'enabledivisa' => array(
							'title' => __( 'Enable Divisa', 'pagofacil' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable sending the Currency Code to Pago Facil via divisa parameter.', 'pagofacil' ), 
							'default' => 'no'
						),
			'divisa' => array(
							'title' 	=> __( 'Divisa', 'pagofacil' ),
							'desc' 		=> __( "This controls what currency that is being sent in divisa parameter to Pago Facil.", 'woocommerce' ),
							'default'	=> 'USD',
							'type' 		=> 'select',
							'options'   => $currency_code_options
						),
			'testmode' => array(
							'title' => __( 'Sandbox', 'pagofacil' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Sandbox', 'pagofacil' ), 
							'default' => 'no'
						),
			'cardtypes'	=> array(
							'title' => __( 'Accepted Card Logos', 'pagofacil' ), 
							'type' => 'multiselect', 
							'description' => __( 'Select which card types you accept to display the logos for on your checkout page.  This is purely cosmetic and optional, and will have no impact on the cards actually accepted by your account.', 'pagofacil' ), 
							'default' => '',
							'options' => $this->card_type_options,
						)
                            // add 10/03/2014
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

    } // End init_form_fields()
	
    /**
	 * There are no payment fields for nmi, but we want to show the description if set.
	 **/
    function payment_fields() {

		if ($this->showdesc == 'yes') {
			echo wpautop(wptexturize($this->description));
		}
		else {
			$this->is_description_empty();
		}
		
		?>
		<p class="form-row" style="width:200px;">
		    <label>Card Number <span class="required">*</span></label>
		    <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" name="pagofacil_direct_creditcard" />
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
		    <label>Expiration Year  <span class="required">*</span></label>
		    <select name="pagofacil_direct_expdateyear">
			<?php
		    $today = (int)date('y', time());
			$today1 = (int)date('Y', time());
		    for($i = 0; $i < 8; $i++)
		    {
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

		    <input class="input-text" style="width:100px;" type="text" size="5" maxlength="5" name="pagofacil_direct_cvv" />
		</p>
		<div class="clear"></div>
                <?php
                // add 10/03/2014
                if ($this->msi == 'yes')
                {
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
                                    echo '<optgroup label="'.$group.'"></optgroup>';
                                    foreach ($option as $value) {
                                        echo '<option value="'.$value.'">'
                                            .$msi_label_options[$value].
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
	
    public function validate_fields()
    {
        global $woocommerce;

        if (!$this->isCreditCardNumber($_POST['pagofacil_direct_creditcard']))
            $this->showError(__('(Credit Card Number) is not valid.', 'pagofacil'));

        if (!$this->isCorrectExpireDate($_POST['pagofacil_direct_expdatemonth'], $_POST['pagofacil_direct_expdateyear']))
            $this->showError(__('(Card Expire Date) is not valid.', 'pagofacil'));

        if (!$_POST['pagofacil_direct_cvv'])
            $this->showError(__('(Card CVV) is not entered.', 'pagofacil'));
    }
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
        global $woocommerce;
		
		$order = new WC_Order( $order_id );
		
		$order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->billing_phone );		                
                
    	$transaction = array(
                        'idServicio'        => urlencode('3'),
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
		
		if($this->enabledivisa == 'yes'){
			$transaction = array_merge( $transaction, array( 'divisa' => urlencode( $this->divisa ) ) );
		}
		
		if($this->sendemail != 'yes'){
			$transaction = array_merge( $transaction, array( 'noMail' => urlencode( '1' ) ) );
		}
                                
                // add 10/03/2014                
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
			
        	$response = json_decode($response['body'],true);
			
			$response = $response['WebServices_Transacciones']['transaccion'];
			
		   	if($response["autorizado"] == "1" && strtolower($response['status']) == 'success') {
				
				// Payment completed
			    $order->add_order_note( sprintf( __('Pago Facil %s. The Pago Facil Transaction ID %s and Authorization ID %s.', 'pagofacil'), $response["texto"], $response["transaccion"], $response["autorizacion"] ) );
				
			    $order->payment_complete();
				
				return array(
					'result' 	=> 'success',
					'redirect'	=>  $this->get_return_url($order)
				);
				
			}else{
				
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
            
            if (function_exists('wc_add_notice')) { // version >= 2.3                
                wc_add_notice($message, 'error');
            } else { // version < 2.3
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
            
            if (class_exists('WC_HTTPS')) { // version >= 2.3                
                return WC_HTTPS::force_https_url($url);
            } else { // version < 2.3
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
}