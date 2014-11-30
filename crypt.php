<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/30/14
 * Time: 12:10 AM
 */
require_once 'include/db/PassCrypt.php';

$crypt = new PassCrypt();

$plain_txt = "This is my plain text";
echo "Plain Text = $plain_txt\n";

$encrypted_txt = $crypt->encrypt($plain_txt);
echo "Encrypted Text = $encrypted_txt\n";

$decrypted_txt = $crypt->decrypt($encrypted_txt);
echo "Decrypted Text = $decrypted_txt\n";

if( $plain_txt === $decrypted_txt ) echo "SUCCESS";
else echo "FAILED";
