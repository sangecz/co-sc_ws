<?php


/**
 * This class greatly extends tutorial mentioned below
 *
 * @author Petr Marek
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class PassHash {

    // CRYPT_SHA512 - http://php.net/manual/en/function.crypt.php
    private static $algorirm = '$6';
    // rounds parameter
    private static $rounds = '$rounds=9999';

    // generate a hash
    public static function hash($password) {

        return crypt($password, self::$algorirm .
            self::$rounds .
            '$' . self::create_unique_salt());
    }

    // compare a password against a hash
    public static function check_password($hash, $password) {
        $salt = substr($hash, 0, 35);
        $new_hash = crypt($password, $salt);
        return ($hash == $new_hash);
    }

    public static function create_unique_salt() {
        return substr(sha1(mt_rand()), 0, 20);
    }

}

?>