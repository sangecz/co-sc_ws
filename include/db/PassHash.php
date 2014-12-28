<?php


/**
 * This class manages encryption (encrypting, hashing, etc.) for whole web service.
 *
 * This class extends tutorial mentioned below
 *
 * @author Petr Marek, Ravi Tamada
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class PassHash {

    /**
     * Encryption method
     */
    const ENCRYPT_METHOD = 'AES-256-CBC';

    /**
     * Hashing method
     */
    const HASH_METHOD = 'sha256';

    /**
     * Encryption algorithm
     *
     * @link http://php.net/manual/en/function.crypt.php
     */
    private static $algorithm = '$6';
    /**
     * Number of rounds parameter
     */
    private static $rounds = '$rounds=9999';

    /** generate a hash
     * @param $password
     * @return String
     */
    public static function hash($password) {

        return crypt($password, self::$algorithm .
            self::$rounds .
            '$' . self::create_unique_salt());
    }

    /**
     * Compares a password against a hash stored in DB.
     *
     * @param String $hash hash stored in DB in users table
     * @param String $password provided password to check
     * @return bool
     */
    public static function check_password($hash, $password) {
        $salt = substr($hash, 0, 35);
        $new_hash = crypt($password, $salt);
        return ($hash == $new_hash);
    }

    /**
     * Creates unique salt for encryption.
     *
     * @return String unique salt
     */
    public static function create_unique_salt() {
        return substr(sha1(mt_rand()), 0, 20);
    }

    /**
     * Encrypts provided plaintext.
     *
     * @param String $string plaintext
     * @return String encrypted string
     */
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

    /**
     * Decrypts to plaintext.
     *
     * @param String $string encrypted string
     * @return String decrypted string
     */
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