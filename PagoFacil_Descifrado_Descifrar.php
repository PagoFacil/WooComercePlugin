<?php

/**
 * Clase para descifrar contenido de transacciones con 3D Secure (3DS), en PHP
 *
 * NOTA:
 * Para PHP 7.1 o inferior, utilice el método "desencriptar"
 * Para PHP 7.2 o superior, utilice el método "desencriptar_php72"
 */
class PagoFacil_Descifrado_Descifrar
{
    const AUTORIZADO = 1;
    const RECHAZADO = 0;

    protected $_method;

    public function __construct() {
        $this->_method = 'AES-128-CBC';
    }

    public static function desencriptar($encodedInitialData, $key)
    {
        $encodedInitialData =  base64_decode($encodedInitialData);
        $cypher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        if (mcrypt_generic_init($cypher, $key, $key) != -1)
        {
            $decrypted = mdecrypt_generic($cypher, $encodedInitialData);
            mcrypt_generic_deinit($cypher);
            mcrypt_module_close($cypher);
            return self::pkcs5_unpad($decrypted);
        }
        return "";
    }

    /**
     * Permite desencriptar la respuesta que se retorna en transacciones 3D Secure
     * (3DS), para PHP versión 7.2 o superior. Requiere el módulo OpenSSL instalado.
     * @param type $encodedInitialData Cadena encriptada que regresa la API
     * @param type $key Llave de cifrado proporcionada por PagoFácil
     */
    function desencriptar_php72($encodedInitialData, $key) {
        $auth = false;
        $data = base64_decode($encodedInitialData, true);
        try {
            $iv_size = openssl_cipher_iv_length($this->getMethod());
            $iv = substr($data, 0, $iv_size);
            $data = substr($data, $iv_size);
            $decrypted = openssl_decrypt($data, $this->getMethod(), $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);

            $decrypted = preg_replace('/^(",")/', '"', self::pkcs5_unpad($decrypted));

            if(stripos($decrypted, 'Transaccion exitosa')) {
                $auth = true;
            }
            $decryptedArray = json_decode('{'.$decrypted);

            $decryptedArray->autorizado = $auth ? self::AUTORIZADO : self::RECHAZADO;

            return $decryptedArray;
        } catch (Exception $exc) {
            return '';
        }
    }

    /**
     * This class uses by default hex2bin function, but it is only available since 5.4, because the current php version
     * is 5.2 this function was created with the purpose to replace the default function.
     * @param $hex_string
     * @return string
     */
    private static function hexToBin($hex_string)
    {
        $pos = 0;
        $result = '';
        while ($pos < strlen($hex_string)) {
            if (strpos(" \t\n\r", $hex_string[$pos]) !== FALSE) {
                $pos++;
            } else {
                $code = hexdec(substr($hex_string, $pos, 2));
                $pos = $pos + 2;
                $result .= chr($code);
            }
        }
        return $result;
    }

    private static function pkcs5_unpad($text)
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public function getMethod() {
        return $this->_method;
    }
}
