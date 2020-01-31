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
    /** @var string $msi */
    protected $msi;
    /** @var string $pf_sandbox_service */
    protected $pf_sandbox_service;
    /** @var string $pf_sandbox_3ds_service */
    protected $pf_sandbox_3ds_service;
    /** @var string $pf_production_service */
    protected $pf_production_service;
    /** @var string $pf_production_3ds_service */
    private $pf_production_3ds_service;
    protected $woocommerce;

    public function __construct()
    {
        global $woocommerce;
        $this->woocommerce = $woocommerce;
        $this->has_fields   = true;
        $this->init_form_fields();
        $this->init_settings();
        $this->is_description_empty();
    }

    abstract public function init_form_fields();
    abstract public function process_payment($order_id);
    abstract protected function getUrlEnvironment();

    public function is_description_empty()
    {
        $this->showdesc = "";
    }

    private function __destruct()
    {
        $_POST = [];
    }

    /**
     * @param $response
     * @throws PaymentError
     */
    protected function throwResponse($response)
    {
        if (isset($response['texto'])) {
            $message = sprintf(__('Transaction Failed. %s', 'pagofacil'), $response['texto']).'<br>';
            foreach ($response['error'] as $k => $v) {
                $message .= $v.'<br>';
            }
            throw new PaymentError($message, $message);
        } else {
            throw new PaymentError(
                sprintf(
                    __('Transaction Failed. %s', 'pagofacil'),
                    $response['response']['message']
                ),
                sprintf(__('Transaction Failed. %s', 'pagofacil'), $response['response']['message'])
            );
        }
    }

    /**
     * @param $response
     */
    protected function isWordPressError($response)
    {
        if (is_wp_error($response) && $response['response']['code'] <= 200 && $response['response']['code'] > 300) {
            throw new HttpError("Gateway Error.". $response->get_error_message());
        }
    }

    /**
     * Envía mesajes de error al checkout según la versión
     * @param $message
     */
    protected function showError($message)
    {

        if (function_exists('wc_add_notice')) {
            wc_add_notice($message, 'error');
        } else {
            $this->woocommerce->add_error($message);
        }
    }

    /**
     *
     * Envia mesajes de error al checkout segun la version
     * @param $url string
     * @return string
     */
    protected function forceSSL($url)
    {
        if (class_exists('WC_HTTPS')) {
            return WC_HTTPS::force_https_url($url);
        } else {
            return $this->woocommerce->force_ssl($url);
        }
    }

    /**
     * Obtiene la ip real del comprador
     * @return string
     */
    protected function getIpBuyer()
    {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];

        if (isset($_SERVER["HTTP_CLIENT_IP"])
            && (!empty($_SERVER["HTTP_CLIENT_IP"]))
            && (strtolower($_SERVER["HTTP_CLIENT_IP"]) != "unknown")
        ) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
            if (strpos($ip, ",") !== false) {
                $ip = substr($ip, 0, strpos($ip, ","));
            }
            return  trim($ip);
        }

        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])
            && (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
            && (strtolower($_SERVER["HTTP_X_FORWARDED_FOR"]) != "unknown")
        ) {
            if (strpos($ip, ",") !== false) {
                $ip = substr($ip, 0, strpos($ip, ","));
            }
            return  trim($ip);
        }

        if (strpos($ip, ",") !== false) {
            $ip = substr($ip, 0, strpos($ip, ","));
        }
        return  trim($ip);
    }
}
