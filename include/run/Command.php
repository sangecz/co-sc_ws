<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/29/14
 * Time: 6:03 PM
 */

class Command {

    protected $host;
    protected $login;
    protected $port;

    protected $scriptContent;
    protected $interpreterPath;
    protected $execString;

    protected $script;
    protected $protocol;

    /**
     * @param Script $script
     * @param Protocol $protocol
     */
    function __construct($script, $protocol)
    {
        $this->protocol = $protocol;
        $this->script = $script;
        $this->host = $script->getAddress();
        $this->login = $protocol->getLogin();
        $this->port = $protocol->getPort();
        $this->scriptContent = $script->getContent();

        $this->setInterpreterPath();
        $this->execString = "";
    }

    /**
     * @return string
     */
    public function getExecString()
    {
        return $this->execString;
    }

    private function setInterpreterPath() {
        // trim content first, then explode, suppose hashbang should be at the beginning of the script
        $arr = explode(" ", $this->scriptContent);
        // find hashbang
        $start = strpos($arr[0], "#!");
        // get
        $hashbang = substr($arr[0], $start, strlen($arr[0]));
        $path = str_replace("#!", "", $hashbang);

        if (!empty($path)) {
            $this->interpreterPath = $path;
        } else {
            $this->interpreterPath = DEFAULT_INTERPRETER_PATH;
        }
    }

    /**
     * @return string command
     */
    protected function getCmd() {
        $content = $this->script->getContent();

        $cmd = "<<COSC\n"
            . $content . "\n"
            ."COSC\n"
            .APPENDIX_SSH;

        return $cmd;
    }

}

class SSHCommand extends Command {


    /**
     * @param Script $script
     * @param Protocol $protocol
     */
    function __construct($script, $protocol)
    {
        parent::__construct($script, $protocol);
    }

    public function constructCmd(){
        $decrypted = PassHash::decrypt($this->protocol->getPasswd());
        $passwd = (empty($decrypted)) ? "dummy_string_to_fail" : $decrypted;

        $passwdStr = "-p " . $passwd;
        $userSshArgs = $this->protocol->getSshArgs();

        $sshArgs = trim("-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -p " . $this->port . " " . $userSshArgs);

        $this->execString  = "sshpass " . $passwdStr . " ssh " . $sshArgs . " " . $this->login . "@" . $this->host . " "
            . " 2>&1 '" . $this->interpreterPath . "' " . $this->getCmd();

    }

}

class SNMPCommand extends Command {

    /**
     * @param Script $script
     * @param Protocol $protocol
     */
    function __construct($script, $protocol)
    {
        parent::__construct($script, $protocol);
    }

    public function constructCmd () {
//        $version = $obj->protocol->snmpAttr->version;
//        $versionAuthHost = $version." ".getSNMPAuthPart($obj)." ".$host." ";
//
//        $cmdArgs = $obj->cmd->args;
//        $execStr = "snmpset -m +NET-SNMP-EXTEND-MIB -v ".$versionAuthHost
//            ."'nsExtendStatus.\"cmd\"' = 'createAndGo' "
//            ."'nsExtendCommand.\"cmd\"' = '".$cmdPath."' ";
//
//        $execStr .= "'nsExtendArgs.\"cmd\"' = '".trim(getArgsFromArray($cmdArgs))."'";
//
//        execute($execStr, $host);
//
//        // TODO sudo
//        TODO PassHash::decrypt();
//            $execStr = "snmpwalk -m +NET-SNMP-EXTEND-MIB -v".$versionAuthHost;
//            // execute remote script
//            execute($execStr . " 'nsExtendOutputFull.\"cmd\"'", $host);
//            // get script result
//            execute($execStr . " 'nsExtendResult.\"cmd\"'", $host);
    }

}
