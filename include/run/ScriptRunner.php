<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/29/14
 * Time: 11:53 AM
 */

class ScriptRunner {

    private $response;
    private $script;
    private $protocol;

    /**
     * @param $script Script
     * @param $protocol Protocol
     */
    function __construct($script, $protocol)
    {
        require_once APP_PATH . '/db/Script.php';
        require_once APP_PATH . '/db/Protocol.php';
        require_once APP_PATH . '/resp/Response.php';
        require_once APP_PATH . '/resp/Responder.php';
        $this->response = new Response();
        $this->script = $script;
        $this->protocol = $protocol;
    }

    public function process() {
        $this->checkDependencies();

        // TODO construct run
        $command = $this->constructCommand();

        // TODO run
        $this->execute($command, $this->script->getAddress());

        // TODO parse response

        // TODO respond


//        return $this->response;
    }

    /**
     * ------------------------- EXECUTE COMMAND -----------------------
     */
    private function execute($cmd, $host) {
        $cmd .= APPENDIX;

        if(DEBUG == 1) {
            echo "COMMAND:\n".$cmd."\n";
        }

        $returned = shell_exec($cmd);

        if(DEBUG == 1) {
            echo "RETURNED:\n".$returned."\n";
        }
//        parseReply($returned);
//
//        $exitCode = $retJSON->protocol->exitCode;
//        // return check (odd and even fenomenon: try again)
//        if($exitCode == 2) {
//            $returned = shell_exec($cmd);
//            parseReply($returned);
//        }
//
//        handleExitCode($returned, $host);
    }
    /**
     * ------------------------- CONSTRACT COMMAND ---------------------
     */

    /**
     * @param $protocol Protocol
     * @param $script Script
     */
    private function constructCommand() {
        if($this->protocol->getType() == SSH_STR) {
            $execStr = $this->constructSSHCmd();
            return $execStr;
        }
//        if($this->protocol->getType() == SNMP_STR) {
//
//            $version = $obj->protocol->snmpAttr->version;
//            $versionAuthHost = $version." ".getSNMPAuthPart($obj)." ".$host." ";
//
//            $cmdArgs = $obj->cmd->args;
//            $execStr = "snmpset -m +NET-SNMP-EXTEND-MIB -v ".$versionAuthHost
//                ."'nsExtendStatus.\"cmd\"' = 'createAndGo' "
//                ."'nsExtendCommand.\"cmd\"' = '".$cmdPath."' ";
//
//            $execStr .= "'nsExtendArgs.\"cmd\"' = '".trim(getArgsFromArray($cmdArgs))."'";
//
//            execute($execStr, $host);
//
//            // TODO sudo
                // TODO PassHash::decrypt();
//            $execStr = "snmpwalk -m +NET-SNMP-EXTEND-MIB -v".$versionAuthHost;
//            // execute remote script
//            execute($execStr . " 'nsExtendOutputFull.\"cmd\"'", $host);
//            // get script result
//            execute($execStr . " 'nsExtendResult.\"cmd\"'", $host);
//        }

    }

    private function constructSSHCmd() {
        $host = $this->script->getAddress();

        $passwd = "-p " . PassHash::decrypt($this->protocol->getPasswd());
        $login = $this->protocol->getLogin();
        $port = $this->protocol->getPort();
        $args = $this->protocol->getSshArgs();

        $sshArgs = trim("-o StrictHostKeyChecking=no -p " . $port . " " . $args);

        $sshCmd = "sshpass " . $passwd . " ssh " . $sshArgs . " " . $login . "@" . $host . " "
            . $this->getCmd();

        return $sshCmd;
    }

    /**
     * @return string command
     */
    private function getCmd() {
        $content = $this->script->getContent();

        $cmd = "<<COSC\n"
              . $content . "\n"
              ."COSC\n";

        return $cmd;
    }

    /**
     * ----------------------- DEPENDENCIES ------------------------------
     */

    /**
     * @param $protocol Protocol
     */
    private function checkDependencies() {
        // check if function shell_exec is enabled
        if(!function_exists('shell_exec')) {
            $msg = "Required function 'shell_exec' is missing or not enabled.";
            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
            Responder::echoResponse(404, $this->response);
        }

        if($this->protocol->getType() == SSH_STR) {
            $this->checkSSHDependency();
        }
        if($this->protocol->getType() == SNMP_STR) {
            $this->checkSNMPDependency();
        }
    }

    private function checkSSHDependency() {
        // check if sshpass is available
        $ret = shell_exec("sshpass > /dev/null ; echo $?");
        if($ret != 0) {
            $msg = "Required program 'sshpass' is missing. Try to install it first.";
            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
            Responder::echoResponse(404, $this->response);
        }
        // check if ssh is available
        $ret = shell_exec("ssh -V 2> /dev/null ; echo $?");
        if($ret != 0) {
            $msg = "Required program 'ssh' is missing. Try to install it first.";
            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
            Responder::echoResponse(404, $this->response);
        }
    }

    private function checkSNMPDependency() {
        // check if snmp tools are available >> unknown command: retcode=127
        $ret = shell_exec("snmpset 2> /dev/null ; echo $?");
        if($ret == 127) {
            $msg = "Required program 'snmpset' is missing. Try to install it first.";
            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
            Responder::echoResponse(404, $this->response);
        }
        $ret = shell_exec("snmpgetnext 2> /dev/null ; echo $?");
        if($ret == 127) {
            $msg = "Required program 'snmpwalk' is missing. Try to install it first.";
            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
            Responder::echoResponse(404, $this->response);
        }
    }

    // TODO
    private function checkSSHTunnelRequirements() {
        // first check SSH req.
        checkSSHRequirements();
        // check if socat is available
        $ret = shell_exec("socat -h >/dev/null ; echo $?");
        if($ret != 0) {
            prepareExit("Required program 'socat' is missing. Try to install it first.", 3);
        }
    }
}