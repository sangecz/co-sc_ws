<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/25/14
 * Time: 6:46 PM
 */

class JSONFormatChecker {

    private $retProtocol;

    function __construct() {
        require_once dirname(__FILE__) . '/Config.php';
        require_once dirname(__FILE__) . '/DbHandler.php';
        require_once dirname(__FILE__) . '/Protocol.php';
        $this->retProtocol = new Protocol();
    }

    public function getProtocol() {
        return $this->retProtocol;
    }

    /**
     * Checks mandatory attribute
     * @param String $attr value of an attribute
     * @param String $name name of an attribute
     */
    private function checkMandatoryAttribute($attr, $name) {
        if(!$attr || empty($attr)) {
            $msg = "Bad JSON format: mandatory attribute '$name' is missing.";
            $this->prepareExit($msg, WS_CODE_JSON_SYNTAX);
        }
    }

    /**
     * Checks provided protocol
     * @param Object $protocol Http JSON object
     */
    public function checkProtocolMandatory($protocol) {

        $this->checkMandatoryAttribute(array_key_exists("type", $protocol), "protocol->type");
        $this->retProtocol->setType(trim(strtolower($protocol->type)));
        $this->checkMandatoryAttribute(array_key_exists("name", $protocol), "protocol->name");
        $this->retProtocol->setName($protocol->name);

        $this->checkProtocolType();

        // check ssh mandatory attrs
        if($this->retProtocol->getType() == SSH_STR) {
            $this->checkMandatoryAttribute(array_key_exists("sshAttr", $protocol), "sshAttr");
            $sshAttr = $protocol->sshAttr;
            $this->checkMandatoryAttribute(array_key_exists("auth", $sshAttr), "sshAttr->auth");
            $this->checkMandatoryAttribute(array_key_exists("login", $sshAttr->auth), "sshAttr->auth->login");
            $this->retProtocol->setLogin($sshAttr->auth->login);
            $this->checkMandatoryAttribute(array_key_exists("passwd", $sshAttr->auth), "sshAttr->auth->passwd");
            $this->retProtocol->setPasswd($sshAttr->auth->passwd);
        }
        // check snmp mandatory attrs and possible combinations depending on version and auth level
        if($this->retProtocol->getType() == SNMP_STR) {
            $this->checkMandatoryAttribute(array_key_exists("snmpAttr", $protocol), "snmpAttr");
            $snmpAttr = $protocol->snmpAttr;

            $this->checkMandatoryAttribute(array_key_exists("version", $snmpAttr), "snmpAttr->version");
            $this->checkMandatoryAttribute(array_key_exists("auth", $snmpAttr), "snmpAttr->auth");

            $version = trim(strtolower($snmpAttr->version));
            $this->retProtocol->setVersion($version);
            $snmpAttrAuth = $snmpAttr->auth;


            // check auth methods depending on version
            if($version != "1" && $version != "2c" && $version != "3") {
                $this->prepareExit("Bad JSON value: '$version' for 'snmpAttr->auth->level' not recognized, not in " .
                    "<1, 2c, 3>.", 4);
            }
            if($version == "1" || $version == "2c") {
                $this->checkMandatoryAttribute(array_key_exists("community", $snmpAttrAuth), "snmpAttr->auth->community");
                $this->retProtocol->setCommunity($snmpAttrAuth->community);
            }
            if($version == "3") {
                $this->checkMandatoryAttribute(array_key_exists("level", $snmpAttrAuth), "snmpAttr->auth->level");
                $authLevel = trim(strtolower($snmpAttrAuth->level));
                $this->retProtocol->setLevel($authLevel);

                // check common auth attrs
                $this->checkMandatoryAttribute(array_key_exists("login", $snmpAttrAuth), "snmpAttr->auth->login");
                $this->retProtocol->setLogin($snmpAttrAuth->login);
                $this->checkMandatoryAttribute(array_key_exists("authPasswd" ,$snmpAttrAuth), "snmpAttr->auth->authPasswd");
                $this->retProtocol->setAuthPasswd($snmpAttrAuth->authPasswd);

                // check auth methods depending on level
                if($authLevel != "authpriv" && $authLevel != "authnopriv") {
                    $this->prepareExit("Bad JSON value: '$authLevel' for 'snmpAttr->auth->level' not recognized, not in" .
                        " <authPriv, authNoPriv>.", 4);
                }
                if($authLevel == "authpriv") {
                    $this->checkMandatoryAttribute(array_key_exists("privPasswd", $snmpAttrAuth),
                        "snmpAttr->auth->privPasswd");
                    $this->retProtocol->setPrivPasswd($snmpAttrAuth->privPasswd);
                }
            }
        }
    }

    /**
     * Checks provided protocol
     * @param Object $protocol Http JSON object
     */
    public function checkProtocolCreateOpt($protocol) {
        if($this->retProtocol->getType() == SNMP_STR) {
            $snmpAttrAuth = $protocol->snmpAttr->auth;

            $privProto = strtolower(array_key_exists("privProto", $snmpAttrAuth) ? $snmpAttrAuth->privProto : "");
            $this->retProtocol->setPrivProto($this->checkProto($privProto
                , DEFAULT_PRIVPROTO, "des", "aes"));

            $authProto = strtolower(array_key_exists("authProto", $snmpAttrAuth) ? $snmpAttrAuth->authProto : "");
            $this->retProtocol->setAuthProto($this->checkProto(
                $authProto, DEFAULT_AUTHPROTO, "md5", "sha"));

            $port = array_key_exists("port", $protocol->snmpAttr) ? $protocol->snmpAttr->port : "";
            $this->retProtocol->setPort($this->checkPort($port, DEFAULT_SNMP_PORT, "snmpAttr->port"));

        }
        if($this->retProtocol->getType() == SSH_STR) {
            $port = array_key_exists("port", $protocol->snmpAttr) ? $protocol->sshAttr->port : "";
            $this->retProtocol->setPort($this->checkPort($port, DEFAULT_SSH_PORT, "sshAttr->port"));

            $sshArgs = array_key_exists("sshArgs", $protocol->sshAttr) ? $protocol->sshAttr->sshArgs : "";
            $this->checkArgs($sshArgs, "sshAttr->sshArgs");
            $this->retProtocol->setSshArgs($this->getArgsString($sshArgs));
        }
    }

    /**
     * Parses args array to string
     * @param array $args args array
     */

    function getArgsFromArray($argsArr) {
        if (is_array($argsArr) && !empty($argsArr)) {
            $ret = "";
            for($i = 0; $i < count($argsArr); $i++) {
                $ret .= $argsArr[$i] . " ";
            }
            return $ret;
        } else {
            return "";
        }
    }

    /**
     * Checks arguments, must be an array
     * @param array $args args array
     * @param String $name name of an attribute
     */
    private function checkArgs($args, $name) {
        if($args && !empty($args) && !is_array($args)) {
            $this->prepareExit("Bad JSON value: '$args' for '$name' is not an array.", 4);
        }
    }

    /**
     * Checks port
     * @param int $port port for protocol
     * @param String $default name of an attribute
     * @param int $name default port
     */
    private function checkPort($port, $default, $name) {
        if($port && !empty($port) && !is_int($port)) {
            $this->prepareExit("Bad JSON value: '$port' for '$name' is not an integer.", 4);
        }
        if(!$port || empty($port)) {
            return $default;
        }
        if (is_int($port)) {
            return $port;
        }
    }


    /**
     * Checks protos - snmp specific
     * @param String $proto should be [authProto, privProto]
     * @param String $default default value of an attribute
     * @param String $first
     * @param String $second
     */
    private function checkProto($proto, $default, $first, $second) {
        if($proto && !empty($proto) && $proto != $first && $proto != $second) {
            $this->prepareExit("Bad JSON value: '$proto' for 'snmpAttr->auth->authProto' not recognized, not in" .
                " <" . strtoupper($first) . ", " . strtoupper($second) . ">.", 4);
        }
        if(!$proto || empty($proto)) {
            return $default;
        }
        if ($proto == $first || $proto == $second) {
            return strtoupper($proto);
        }
    }

    /**
     * Checks protocol type agains protocol types in db
     */
    private function checkProtocolType() {
        $db = new DbHandler();
        $result = $db->getAllProtocolTypes();

        $e = 0;
        if ($result != NULL) {
            while ($r = $result->fetch_assoc()) {
                if($this->retProtocol->getType() != trim(strtolower($r['type']))) {
                    $e++;
                }
            }
        } else {
            $msg = "Failed load protocol types from db";
            $this->prepareExit($msg, WS_CODE_BAD_VALUE);
        }

        if($e >= SUPPORTED_PROTOCOLS) {
            $this->prepareExit(
                "Bad JSON value: '$this->protocol_type' for"
                ." 'protocol->type' not recognized <snmp, ssh>.", WS_CODE_BAD_VALUE);
        }
    }

    /**
     * Echoing json response to client
     * @param String $status_code Http response code
     * @param Int $response Json response
     */
    private function echoResponse($status_code, $response) {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

        echo json_encode($response);
    }

    /**
    * Prepares response before exiting
    * @param String $cause what caused the problem
    * @param Int $wsCode ws exit code
    */
    private function prepareExit($cause, $wsCode) {
        $app = \Slim\Slim::getInstance();
// FIXME ukoncovani
        $response['ws']['httpCode'] = 400;
        $response['ws']['statusCode'] = $wsCode;
        $response['ws']['message'] = "Bad Request: " . $cause;

        $this->echoResponse(400, $response);

        $app->stop();
    }


} 