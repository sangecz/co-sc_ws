<?php


/**
 * This class greatly extends tutorial mentioned below
 *
 * @author Petr Marek
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class PassHash {

    const ENCRYPT_METHOD = 'AES-256-CBC';
    const HASH_METHOD = 'sha256';

    // CRYPT_SHA512 - http://php.net/manual/en/function.crypt.php
    private static $algorirm = '$6';
    // rounds parameter
    private static $rounds = '$rounds=9999';

    /** generate a hash
     * @param $password
     * @return string
     */
    public static function hash($password) {

        return crypt($password, self::$algorirm .
            self::$rounds .
            '$' . self::create_unique_salt());
    }

    /** compare a password against a hash
     * @param $hash
     * @param $password
     * @return bool
     */
    public static function check_password($hash, $password) {
        $salt = substr($hash, 0, 35);
        $new_hash = crypt($password, $salt);
        return ($hash == $new_hash);
    }

    public static function create_unique_salt() {
        return substr(sha1(mt_rand()), 0, 20);
    }


    public static function encrypt($string) {
        $output = false;

        // hash
        $key = hash(PassHash::HASH_METHOD, SECRET_KEY);

        // iv - encrypt method AES-256-CBC expects 16 bytes
        $iv = substr(hash(PassHash::HASH_METHOD, SECRET_IV), 0, 16);

        $output = openssl_encrypt($string, PassHash::ENCRYPT_METHOD, $key, 0, $iv);
        $output = base64_encode($output);


        return $output;
    }

    public static function decrypt($string) {
        $output = false;

        // hash
        $key = hash(PassHash::HASH_METHOD, SECRET_KEY);

        // iv - encrypt method AES-256-CBC expects 16 bytes
        $iv = substr(hash(PassHash::HASH_METHOD, SECRET_IV), 0, 16);

        $output = openssl_decrypt(base64_decode($string), PassHash::ENCRYPT_METHOD, $key, 0, $iv);

        return $output;
    }

}

?>