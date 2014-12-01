<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/30/14
 * Time: 10:02 PM
 */

class MySSH {

    private $ssh;
    private  $sftp;

    private $port;
    private $address;
    private $password;
    private $login;

    private $response;
    const REMOTE_FILE = 'remote.ext';

    function __construct($address, $login, $password, $port)
    {
        set_include_path( LIB_PATH . '/phpseclib/');
        require_once LIB_PATH . '/phpseclib/Net/SSH2.php';
        require_once LIB_PATH . '/phpseclib/Net/SFTP.php';
        require_once APP_PATH . '/resp/Response.php';

        $this->port = $port;
        $this->address = $address;
        $this->password = $password;
        $this->login = $login;
        $this->response = new Response();
    }

    public function executeScript($local_file) {
        // connect
        $this->ssh = new Net_SSH2($this->address, $this->port, SSH_TIMEOUT);
        if (!$this->ssh->login($this->login, $this->password)) {
            $msg = 'Login to remote host device failed.';
            $this->response->setCmd($msg, SSH_ERR_LOGIN_CODE);
            return $this->response;
        }

        // connect
        $this->sftp = new Net_SFTP($this->address, $this->port, SSH_TIMEOUT);
        if (!$this->sftp->login($this->login, $this->password)) {
            $msg = 'Login to remote host device failed.';
            $this->response->setCmd($msg, SSH_ERR_LOGIN_CODE);
            return $this->response;
        }

        // copy file
        if(!$this->sftp->put(MySSH::REMOTE_FILE, $local_file, NET_SFTP_LOCAL_FILE)){
            $msg = 'Copying script to remote file failed.';
            $this->response->setCmd($msg, SSH_ERR_COPY_CODE);
            return $this->response;
        }

        // get outputs
        $mixedOut = $this->ssh->exec('chmod +x '.MySSH::REMOTE_FILE.' && ./'.MySSH::REMOTE_FILE);
        $exitCode= $this->ssh->getExitStatus();

        // clean up
        if(!$this->sftp->delete(MySSH::REMOTE_FILE)) {
            $msg = "Removing temp file '".MySSH::REMOTE_FILE."' failed.";
            $mixedOut .=  '\n'.$msg;
        }

        // close channels
        $this->ssh->reset();
        $this->sftp->reset();

        $this->response->setCmd($mixedOut, $exitCode);

    }


}