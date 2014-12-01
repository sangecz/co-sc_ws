<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/30/14
 * Time: 12:10 AM
 */
//set_include_path('/var/www/co-sc');
include_once 'Config.php';
set_include_path( LIB_PATH . '/phpseclib/');
include('libs/phpseclib/Net/SSH2.php');

include('libs/phpseclib/Net/SFTP.php');
$t = microtime();
$ssh = new Net_SSH2('sange-icinga.hukot.net', 22);
if (!$ssh->login('root', 'spring234')) {
    exit('Login Failed');
}

$sftp = new Net_SFTP('sange-icinga.hukot.net');
if (!$sftp->login('root', 'spring234')) {
    exit('Login Failed');
}

$myfile = fopen("tmp/local.txt", "w") or die("Unable to open file!");
$txt = <<<COSC
#!/usr/bin/python

import os
import socket

print(os.getloadavg())
print(socket.gethostname())
COSC;

fwrite($myfile, $txt);
fclose($myfile);

$sftp->put('remote.py', 'tmp/local.txt', NET_SFTP_LOCAL_FILE);


$ssh->enableQuietMode();
echo "stdout: " . $ssh->exec('chmod +x remote.py && ./remote.py') . "<br>";
echo "stderr: " . $ssh->getStdError() . "<br>";
echo "exitcode: " . $ssh->getExitStatus() . "<br>";

$sftp->delete('remote.py');

//print_r($sftp->nlist());

//echo "<br><br>";
//echo $ssh->read('root@sange-icinga:~#');


