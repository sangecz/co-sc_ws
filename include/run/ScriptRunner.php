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
        require_once APP_PATH . '/parsers/CommandResponseParser.php';
        require_once 'MySSH.php';
        $this->response = new Response();
        $this->script = $script;
        $this->protocol = $protocol;
    }

    public function process() {
        $this->checkDependencies();

        $this->runScript();

        return $this->response;

    }

    /**
     * ------------------------- RUN SCRIPT ---------------------
     */
    private function runScript() {


        if($this->protocol->getType() == SSH_STR) {

            // first prepare TEMP_FILE
            $this->prepareTmpFile();

            $decrypted = PassHash::decrypt($this->protocol->getPasswd());
            $passwd = (empty($decrypted)) ? "dummy_string_to_fail" : $decrypted;
            $addr = $this->script->getAddress();
            $login = $this->protocol->getLogin();
            $port = $this->protocol->getPort();

            // construct ssh connection
            $myssh = new MySSH($addr, $login, $passwd, $port);
            // execute script and get response
            $this->response = $myssh->executeScript(TEMP_FILE_PATH);

        }
//        if($this->protocol->getType() == SNMP_STR) {
//            $cmd = new SNMPCommand($this->script, $this->protocol);
//        }
    }

    private function prepareTmpFile () {
        $myfile = fopen(TEMP_FILE_PATH, "w");
        if($myfile == FALSE) {
            $this->response->setWs(WS_CODE_EXECUTE_ERR, "Could not open temp file, contact WS administrator.", true);
        }

        $txt = $this->script->getContent();
        fwrite($myfile, $txt);

        fclose($myfile);

        if($this->response->getWs()['error']) {
            Responder::echoResponse(500, $this->response);
        }
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
//        $ret = shell_exec("sshpass > /dev/null ; echo $?");
//        if($ret != 0) {
//            $msg = "Required program 'sshpass' is missing. Try to install it first.";
//            $this->response->setWs(WS_CODE_DEPENDENCY, $msg, true);
//            Responder::echoResponse(404, $this->response);
//        }
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

    // TODO tunnel
//    private function checkSSHTunnelRequirements() {
//        // first check SSH req.
//        checkSSHRequirements();
//        // check if socat is available
//        $ret = shell_exec("socat -h >/dev/null ; echo $?");
//        if($ret != 0) {
//            prepareExit("Required program 'socat' is missing. Try to install it first.", 3);
//        }
//    }
}