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

$hasMySQL = false;
$hasMySQLi = false;
$withMySQLnd = false;

if (function_exists('mysql_connect')) {
    $hasMySQL = true;
    $sentence.= "(Deprecated) MySQL <b>is installed</b> ";
} else
    $sentence.= "(Deprecated) MySQL <b>is not</b> installed ";

if (function_exists('mysqli_connect')) {
    $hasMySQLi = true;
    $sentence.= "and the new (improved) MySQL <b>is installed</b>. ";
} else
    $sentence.= "and the new (improved) MySQL <b>is not installed</b>. ";

if (function_exists('mysqli_fetch_all')) {
    $withMySQLnd = true;
    $sentence.= "This server is using MySQLnd as the driver.";
} else
    $sentence.= "This server is using libmysqlclient as the driver.";

echo $sentence;