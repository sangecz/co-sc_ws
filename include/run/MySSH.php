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
    const REMOTE_FILE = 'remote.script';

    /**
     * @param String $address
     * @param String $login
     * @param String $password
     * @param int $port
     */
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

    /**
     * @param String $local_file path to local cache file
     * @return Response
     */
    public function executeScript($local_file) {
        // connect
        $this->ssh = new Net_SSH2($this->address, $this->port, SSH_TIMEOUT);
        if (!$this->ssh->login($this->login, $this->password)) {
            $msg = 'Login to remote host device failed.' ;
            $this->response->setWs(SSH_ERR_LOGIN_CODE, $msg, true);
            return $this->response;
        }

        // connect
        $this->sftp = new Net_SFTP($this->address, $this->port, SSH_TIMEOUT);
        if (!$this->sftp->login($this->login, $this->password)) {
            $msg = 'Login to remote host device failed.';
            $this->response->setWs(SSH_ERR_LOGIN_CODE, $msg, true);
            return $this->response;
        }

        // copy file
        if(!$this->sftp->put(MySSH::REMOTE_FILE, $local_file, NET_SFTP_LOCAL_FILE)){
            $msg = 'Copying script to remote file failed.';
            $this->response->setWs(SSH_ERR_COPY_CODE, $msg, true);
            return $this->response;
        }

        // get outputs
        $execStr = 'chmod +x '.MySSH::REMOTE_FILE.' && '.
                   'nohup ./'.MySSH::REMOTE_FILE.' >out 2>/dev/null </dev/null ; '.
                   'cat out 2>/dev/null && '.
                   'rm -r out 2>/dev/null &&'.
                   'rm -r '.MySSH::REMOTE_FILE.' 2>/dev/null';
        $scriptOutput = $this->ssh->exec($execStr);
        $exitCode= $this->ssh->getExitStatus();
        $this->response->setCmd($scriptOutput, $exitCode);

        // clean up
//        if(!$this->sftp->delete(MySSH::REMOTE_FILE)) {
//            $msg = "Removing temp file '".MySSH::REMOTE_FILE."' failed.";
//            $this->response->setWs(SSH_WARN_REMOVE_CODE, $msg, false);
//        }

        // close channels
//        $this->ssh->reset();
//        $this->sftp->reset();

        return $this->response;
    }


}