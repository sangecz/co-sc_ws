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
        require_once 'Command.php';
        $this->response = new Response();
        $this->script = $script;
        $this->protocol = $protocol;
    }

    public function process() {
        $this->checkDependencies();

        // TODO construct run
        $command = $this->constructCommand();

        // TODO run & parse response
        $partialResponse = $this->execute($command);


        $this->respond($partialResponse);

    }

    /**
     * --------------------- RESPOND TO COMMAND's RESPONSE ----------------
     */

    /**
     * @param Response $resp
     */
    private function respond($resp){
        if(DEBUG == 1) {
            echo "FINAL_RESP::\n";
            var_dump($resp);
        }

        if($resp->getWs()['error']) {
            Responder::echoResponse(400, $resp);
        }
        $resp->setWs(WS_CODE_OK, "Script run successfully", false);
        Responder::echoResponse(200, $resp);
    }

    /**
     * ------------------------- EXECUTE & PARSE COMMAND ------------------
     */


    private function execute($cmd) {

        if(DEBUG == 1) {
            echo "COMMAND:\n".$cmd."\n";
        }

        $returned = shell_exec($cmd);

        if(DEBUG == 1) {
            echo "RETURNED:\n**".$returned."**\n";
        }

        $respParser = new CommandResponseParser($this->protocol->getType());
        $returnedAltered = $respParser->parse($returned);

        $exitCode = $respParser->getResponse()->getExitCode();
        // return check (odd and even fenomenon: try again)
        if($exitCode == 2) {
            $returned = shell_exec($cmd);
            $returnedAltered = $respParser->parse($returned);
        }

        $respParser->handleExitCode($returnedAltered);

        return $respParser->getResponse();
    }
    /**
     * ------------------------- CONSTRACT COMMAND ---------------------
     */
    private function constructCommand() {
        $cmd = NULL;

        if($this->protocol->getType() == SSH_STR) {
            $cmd = new SSHCommand($this->script, $this->protocol);
        }
        if($this->protocol->getType() == SNMP_STR) {
            $cmd = new SNMPCommand($this->script, $this->protocol);
        }

        $cmd->constructCmd();
        return $cmd->getExecString();
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