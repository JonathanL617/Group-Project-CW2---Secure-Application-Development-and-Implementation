<?php
class SecurityHelper {
    private static $key = "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef"; 
    private static $cipher = "aes-256-gcm";

    public static function encrypt($plaintext) {
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, self::$cipher, hex2bin(self::$key), $options=0, $iv, $tag);
        return base64_encode($iv . $tag . $ciphertext_raw);
    }

    public static function decrypt($ciphertext_base64) {
        $c = base64_decode($ciphertext_base64);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($c, 0, $ivlen);
        $tag = substr($c, $ivlen, 16);
        $ciphertext_raw = substr($c, $ivlen + 16);
        return openssl_decrypt($ciphertext_raw, self::$cipher, hex2bin(self::$key), $options=0, $iv, $tag);
    }

    public static function maskData($data) {
        $len = strlen($data);
        if ($len <= 4) return "****";
        return substr($data, 0, 2) . str_repeat("*", $len - 4) . substr($data, -2);
    }
}
?>
