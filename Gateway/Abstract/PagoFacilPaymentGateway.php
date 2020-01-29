<?php

abstract class PagoFacilPaymentGateway extends WC_Payment_Gateway
{
    /** @var string $id */
    protected $id;
    /** @var string $title */
    protected $title;
    /** @var array $method_title */
    protected $method_title;
    /** @var bool $has_fields */
    protected $has_fields;
    /** @var string $description */
    protected $description;
    /** @var string $image */
    protected $image;
    /** @var string $sucursal */
    protected $sucursal;
    /** @var string $usuario */
    protected $usuario;
    /** @var string $sucursal_test */
    protected $sucursal_test;
    /** @var string $usuario_test */
    protected $usuario_test;
    /** @var string $testmode */
    protected $testmode;
    /** @var string $showdesc */
    protected $showdesc;
    /** @var string $concept */
    protected $concept;
    /** @var string */
    protected $instructions;
    /** @var string */
    protected $use_sucursal;
    /** @var string $use_usuario */
    protected $use_usuario;
    /** @var string $form_fields */
    protected $form_fields;
    /** @var string $stores_endpoint */
    protected $stores_endpoint;
    /** @var string $request_url */
    protected $request_url;
    /** @var int $idServApi */
    protected $idServApi;

    public function __construct()
    {
        global $woocommerce;
        $this->has_fields   = TRUE;
        $this->init_form_fields();
        $this->init_settings();
        $this->is_description_empty();
    }

    abstract public function init_form_fields();
    abstract public function process_payment($order_id);
    public function is_description_empty(){
        $this->showdesc = "";
    }
}