<?php
/**
 * Class MySSH takes care of SSH and SFTP connections,
 * which are needed for remote script execution.
 *
 * @author Petr Marek
 * @license Apache 2.0 http://www.apache.org/licenses/LICENSE-2.0
 */
class MySSH {

    /**
     * @var Net_SSH2 Class for SSH connection from library phpseclib
     */
    private $ssh;

    /**
     * @var Net_SFTP Class for SSH connection from library phpseclib
     */
    private  $sftp;

    /**
     * @var int remote device SSH port
     */
    private $port;

    /**
     * @var String remote device IP or FQDN address
     */
    private $address;

    /**
     * @var String remote device SSH password
     */
    private $password;

    /**
     * @var String remote device SSH login
     */
    private $login;

    /**
     * @var Response application response in JSON format.
     */
    private $response;

    /**
     * Remote script name.
     */
    const REMOTE_FILE = 'remote.script';

    /**
     * Prepares class attributes for usage in executeScript method.
     *
     * @param String $address remote device IP or FQDN address
     * @param String $login remote device SSH login
     * @param String $password remote device SSP password
     * @param int $port remote device SSH port
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
     * Establish SSH and SFTP connections, copy a script to remote device and execute it,
     * then remove it.
     *
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

        return $this->response;
    }


}