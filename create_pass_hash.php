<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/25/14
 * Time: 12:44 PM
 */

require 'include/PassHash.php';

$hash = PassHash::hash('tajne_heslo');
echo "Insert in DB <br>";
echo $hash. '<br>';
echo PassHash::check_password('$6$rounds=9999$d8a42aa6f00837d5$JXpgh7uMcy8cm0lhg0VCh6EBiqRs1J.eMRv1iRr9vaxPeRUsloFKawRhr0EwvREbiSB/.bKF6aPBo5jzUUeCr1', 'tajne_heslo') . "<br>";



