<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/25/14
 * Time: 6:46 PM
 */

class JSONRequestParser {

    private $retProtocol;
    private $retScript;

    function __construct() {
        require_once  APP_PATH . '/db/DbHandler.php';
        require_once  APP_PATH . '/db/Protocol.php';
        require_once  APP_PATH . '/db/Script.php';
        require_once  APP_PATH . '/resp/Responder.php';
        require_once  APP_PATH . '/resp/Response.php';
        $this->retProtocol = new Protocol();
        $this->retScript = new Script();
    }

    /**
     * @return Protocol
     */
    public function getProtocol()
    {
        return $this->retProtocol;
    }

    /**
     * @return Script
     */
    public function getScript()
    {
        return $this->retScript;
    }

    /**
     * @param $script JSON obj
     */
    public function parseScript($script) {
        //TODO
        $this->checkMandatoryAttribute(array_key_exists("name", $script), "script->name");
        $this->retScript->setName($script->name);

        $this->checkMandatoryAttribute(array_key_exists("address", $script), "script->address");
        $this->retScript->setAddress($script->address);

        $this->checkMandatoryAttribute(array_key_exists("content", $script), "script->content");
        $this->checkContent($script->content);

        // determin role of script
        $role = $this->checkRole($script);
        $this->retScript->setPsRoleId($role);

        $this->checkMandatoryAttribute(array_key_exists("protocol_id", $script), "script->protocol_id");
        $this->retScript->setProtocolId($script->protocol_id);

    }

    private function checkContent($content) {
        if(strpos(trim($content), "#!/") !== FALSE) {
            $this->retScript->setContent($content);
        } else {
            $msg = "The content of the script should start with hashbang.";
            $this->printResponseErr(WS_CODE_REQUIRED, $msg);
        }
    }


    /**
     * @param $protocol Protocol
     * @return Protocol cheched and altered (default vals if not set) Protocol
     */
    public function parseProtocol($protocol){
        $this->checkProtocolMandatory($protocol);
        $this->checkProtocolOpt($protocol);
    }

    /**
     * Prints response
     * @param int $wsCode
     * @param String $msg
     */
    private function printResponseErr($wsCode, $msg){
        $resp = new Response();
        $resp->setWs($wsCode, $msg, true);
        Responder::echoResponse(400, $resp);
    }

    /**
     * Checks mandatory attribute
     * @param String $attr value of an attribute
     * @param String $name name of an attribute
     */
    private function checkMandatoryAttribute($attr, $name) {
        if(!$attr || empty($attr)) {
            $msg = "Bad JSON format: mandatory attribute '$name' is missing.";

            $this->printResponseErr(WS_CODE_REQUIRED, $msg);
        }
    }

    /**
     * Checks provided protocol
     * @param Object $protocol Http JSON object
     */
    private function checkProtocolMandatory($protocol) {

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
            // print error yet
            $msg = "Sorry. SNMP is not supported yet.";
            $this->printResponseErr(WS_CODE_DEPENDENCY, $msg);

            $this->checkMandatoryAttribute(array_key_exists("snmpAttr", $protocol), "snmpAttr");
            $snmpAttr = $protocol->snmpAttr;

            $this->checkMandatoryAttribute(array_key_exists("version", $snmpAttr), "snmpAttr->version");
            $this->checkMandatoryAttribute(array_key_exists("auth", $snmpAttr), "snmpAttr->auth");

            $version = trim(strtolower($snmpAttr->version));
            $this->retProtocol->setVersion($version);
            $snmpAttrAuth = $snmpAttr->auth;


            // check auth methods depending on version
            if($version != "1" && $version != "2c" && $version != "3") {
                $this->printResponseErr(WS_CODE_BAD_VALUE, "Bad JSON value: '$version' for 'snmpAttr->auth->level' not recognized, not in " .
                    "<1, 2c, 3>.");
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
                    $this->printResponseErr(WS_CODE_BAD_VALUE, "Bad JSON value: '$authLevel' for 'snmpAttr->auth->level' not recognized, not in" .
                        " <authPriv, authNoPriv>.");
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
    private function checkProtocolOpt($protocol) {
        // determin role of protocol
        $role = $this->checkRole($protocol);

        $this->retProtocol->setRole($role);

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
            $port = array_key_exists("port", $protocol->sshAttr) ? $protocol->sshAttr->port : "";
            $this->retProtocol->setPort($this->checkPort($port, DEFAULT_SSH_PORT, "sshAttr->port"));

            $sshArgs = array_key_exists("sshArgs", $protocol->sshAttr) ? $protocol->sshAttr->sshArgs : "";
            $this->checkArgs($sshArgs, "sshAttr->sshArgs");
            $this->retProtocol->setSshArgs($this->getArgsFromArray($sshArgs));
        }
    }

    /**
     * @param $protocol input protocol
     * @return int ps_role for protocol
     */
    private function checkRole($protocol) {
        if(array_key_exists("ps_role_id", $protocol)){
            $roleArr = array(PS_ROLE_PRIVATE, PS_ROLE_PUBLIC);
            $roleId = intval($protocol->ps_role_id);
            if($roleId != 0 && in_array($roleId, $roleArr)) {
                return $roleId;
            } else {
                $roles = PS_ROLE_PUBLIC."=public, ".PS_ROLE_PRIVATE ."=private";
                $msg = "Bad JSON value: '" . $roleId . "' for 'ps_role_id' parameter, not an integer or not in (".$roles.").";
                $this->printResponseErr(WS_CODE_BAD_VALUE, $msg);
            }
        } else {
            return PS_ROLE_PRIVATE;
        }
    }

    /**
     * Parses args array to string
     * @param array $args args array
     */

    private function getArgsFromArray($argsArr) {
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
            $this->printResponseErr(WS_CODE_BAD_VALUE, "Bad JSON value: '$args' for '$name' is not an array.");
        }
    }

    /**
     * Checks port
     * @param int $port port for protocol
     * @param String $default name of an attribute
     * @param int $name default port
     */
    private function checkPort($port, $default, $name) {
        $port = intval($port);
        if($port && !empty($port) && $port == 0) {
            $this->printResponseErr(WS_CODE_BAD_VALUE, "Bad JSON value: '$port' for '$name' is not an integer.");
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
            $this->printResponseErr(WS_CODE_BAD_VALUE, "Bad JSON value: '$proto' for 'snmpAttr->auth->authProto' not recognized, not in" .
                " <" . strtoupper($first) . ", " . strtoupper($second) . ">.");
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

        $sup = 0;
        if ($result != NULL) {
            while ($r = $result->fetch_assoc()) {
                if($this->retProtocol->getType() == trim(strtolower($r['type']))) {
                    $sup++;
                    break;
                }
            }
        } else {
            $msg = "Failed load protocol types from db";
            $this->printResponseErr(WS_CODE_BAD_VALUE, $msg);
        }

        if($sup == 0) {
            $this->printResponseErr(WS_CODE_BAD_VALUE,
                "Bad JSON value: '".$this->retProtocol->getType()."' for"
                ." 'protocol->type' not recognized <snmp, ssh>.");
        }
    }


} 