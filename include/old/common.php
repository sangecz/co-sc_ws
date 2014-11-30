<?php

//define('DEBUG', 0);

define('DEFAULT_PRIVPROTO', "AES");
define('DEFAULT_AUTHPROTO', "SHA");
define('DEFAULT_SSH_PORT', 22);
define('DEFAULT_SNMP_PORT', 161);
define('SSH_STR', "ssh");
define('SNMP_STR', "snmp");
define('EXIT_CODE_STR', "exitCode");
define('CMD_OUTPUT_STR',"nsExtendOutputFull");
define('CMD_RESULT_STR', "nsExtendResult");

$retJSON->ws->httpCode = 200;
$retJSON->ws->statusCode = 0;
$retJSON->ws->message = "OK";
$retJSON->cmd->exitCode = 0;
$retJSON->cmd->output = "";
$retJSON->protocol->output = "";
$retJSON->protocol->exitCode = 0;
$obj = new stdClass();
$http_response_enabled_g = false;
$protocolType_g = "";
$APPENDIX_g = " 2>&1 ; echo \"" .EXIT_CODE_STR. " = $?\"";

function checkDependencies() {
	// check is http_response_code function is available
	if(function_exists('http_response_code')) {
		$http_response_enabled_g = true;
	}
	// check if function shell_exec is enabled
	if(!function_exists('shell_exec')) {
		prepareExit("Required function 'shell_exec' is missing or not enabled.", 1);
	}
}

function loadJSON(){
	$obj = json_decode($_POST['json']);
	if($obj == null) {
		prepareExit("Bad JSON format: syntax error.", 2);
	}
	return $obj;
}

function prepareExit($cause, $wsCode) {
	global $retJSON, $http_response_enabled_g;
	$retJSON->ws->httpCode = 400;
    $retJSON->ws->statusCode = $wsCode;
	$retJSON->ws->message = "Bad Request: " . $cause;

	if($http_response_enabled_g) {
		http_response_code($retJSON->ws->httpCode);
	}
	exit(json_encode($retJSON));
}

function checkExpectedJSONFormatCommon($obj) {
		global $protocolType_g;
		// check mandatory attributes regardles on protocol->type
		checkMandatoryAttribute($obj->host, "host");

		$protocol = $obj->protocol;
		checkMandatoryAttribute($protocol, "protocol");
		checkMandatoryAttribute($protocol->type, "protocol->type");

		// check protocol->type and appropriate tools
		$protocolType_g = trim(strtolower($protocol->type));
		$obj->protocol->type = $protocolType_g;
		checkProtocolType();

		// check ssh mandatory attrs
		if($protocolType_g == SSH_STR) {
			$sshAttr = $protocol->sshAttr;
			checkMandatoryAttribute($sshAttr, "protocol->sshAttr");
			checkMandatoryAttribute($sshAttr->auth, "protocol->sshAttr->auth");
			checkMandatoryAttribute($sshAttr->auth->login, "protocol->sshAttr->auth->login");
			checkMandatoryAttribute($sshAttr->auth->passwd, "protocol->sshAttr->auth->passwd");
		}
		// check snmp mandatory attrs and possible combinations depending on version and auth level
		if($protocolType_g == SNMP_STR) {
			$snmpAttr = $protocol->snmpAttr;
			$version = strtolower($snmpAttr->version);
			$snmpAttrAuth = $snmpAttr->auth;

			checkMandatoryAttribute($snmpAttr, "protocol->snmpAttr");
			checkMandatoryAttribute($version, "protocol->snmpAttr->version");
			checkMandatoryAttribute($snmpAttrAuth, "protocol->snmpAttr->auth");

			// check auth methods depending on version
			if($version != "1" && $version != "2c" && $version != "3") {
				prepareExit("Bad JSON value: '$version' for 'protocol->snmpAttr->auth->level' not recognized, not in " .
					"<1, 2c, 3>.", 4);
			}
			if($version == "1" || $version == "2c") {
				checkMandatoryAttribute($snmpAttrAuth->community, "protocol->snmpAttr->auth->community");
			}
			if($version == "3") {
				$authLevel = strtolower($snmpAttrAuth->level);
				checkMandatoryAttribute($authLevel, "protocol->snmpAttr->auth->level");

				// check common auth attrs
				checkMandatoryAttribute($snmpAttrAuth->login, "protocol->snmpAttr->auth->login");
				checkMandatoryAttribute($snmpAttrAuth->authPasswd, "protocol->snmpAttr->auth->authPasswd");

				// check auth methods depending on level
				if($authLevel != "authpriv" && $authLevel != "authnopriv") {
					prepareExit("Bad JSON value: '$authLevel' for 'protocol->snmpAttr->auth->level' not recognized, not in" .
						" <authPriv, authNoPriv>.", 4);
				}
				if($authLevel == "authpriv") {
					checkMandatoryAttribute($snmpAttrAuth->privPasswd,
						"protocol->snmpAttr->auth->privPasswd");
				}
			}
		}
	}

function checkMandatoryAttribute($attr, $name) {
	if(!$attr || empty($attr)) {
		prepareExit("Bad JSON format: mandatory attribut '$name' is missing.", 2);
	}
}

function checkSSHTunnelRequirements() {
	// first check SSH req.
	checkSSHRequirements();
	// check if socat is available
	$ret = shell_exec("socat -h >/dev/null ; echo $?");
	if($ret != 0) {
		prepareExit("Required program 'socat' is missing. Try to install it first.", 3);
	}
}

function checkSSHRequirements() {
	// check if sshpass is available
	$ret = shell_exec("sshpass > /dev/null ; echo $?");
	if($ret != 0) {
		prepareExit("Required program 'sshpass' is missing. Try to install it first.", 3);
	}
	// check if ssh is available
	$ret = shell_exec("ssh -V 2> /dev/null ; echo $?");
	if($ret != 0) {
		prepareExit("Required program 'ssh' is missing. Try to install it first.", 3);
	}
}

function checkSNMPRequirements() {
	// check if snmp tools are available >> unknown command: retcode=127
	$ret = shell_exec("snmpset 2> /dev/null ; echo $?");
	if($ret == 127) {
		prepareExit("Required program 'snmpset' is missing. Try to install it first.", 3);
	}
	$ret = shell_exec("snmpgetnext 2> /dev/null ; echo $?");
	if($ret == 127) {
		prepareExit("Required program 'snmpwalk' is missing. Try to install it first.", 3);
	}
}

function checkProtocolType() {
	global $protocolType_g;

	if($protocolType_g != SNMP_STR && $protocolType_g != SSH_STR) {
		prepareExit("Bad JSON value: '$protocolType_g' for 'protocol->type' not recognized <snmp, ssh>.", 4);
	}
	if($protocolType_g == SSH_STR) {
		checkSSHRequirements();
	}
	if($protocolType_g == SNMP_STR) {
		checkSNMPRequirements();
	}
}

function checkArgs($args, $name) {
	if($args && !empty($args) && !is_array($args)) {
		prepareExit("Bad JSON value: '$args' for '$name' is not an array.", 4);
	}
}

function checkPort($port, $default, $name) {
	if($port && !empty($port) && !is_int($port)) {
		prepareExit("Bad JSON value: '$port' for '$name' is not an integer.", 4);
	}
	if(!$port || empty($port)) {
		return $default;
	}
	if (is_int($port)) {
		return $port;
	}
}

function checkOptionalValuesCommon($obj) {

	if($obj->protocol->type == SNMP_STR) {
		$snmpAttrAuth = $obj->protocol->snmpAttr->auth;
		$obj->protocol->snmpAttr->auth->privProto = checkProto(
			strtolower($snmpAttrAuth->privProto), DEFAULT_PRIVPROTO, "des", "aes");

		$obj->protocol->snmpAttr->auth->authProto = checkProto(
			strtolower($snmpAttrAuth->authProto), DEFAULT_AUTHPROTO, "md5", "sha");

		$obj->protocol->snmpAttr->port = checkPort($obj->protocol->snmpAttr->port,
			DEFAULT_SNMP_PORT, "protocol->snmpAttr->port");

		// TODO impl socat ssh tunnel
		//if($obj->protocol->snmpAttr->port != $DEFAULT_SNMP_PORT) {
		//	checkSSHTunnelRequirements();
		//}
	}
	if($obj->protocol->type == SSH_STR) {
		$obj->protocol->sshAttr->port = checkPort($obj->protocol->sshAttr->port,
			DEFAULT_SSH_PORT, "protocol->sshAttr->port");
		checkArgs($obj->protocol->sshAttr->sshArgs, "protocol->sshAttr->sshArgs");
	}
}

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

function constructSSHCmd($obj) {
	$host = $obj->host;

	$passwd = "-p " . $obj->protocol->sshAttr->auth->passwd;
	$login = $obj->protocol->sshAttr->auth->login;
	$port = $obj->protocol->sshAttr->port;

	$sshArgs = trim("-o StrictHostKeyChecking=no -t -t -p " . $port . " " .
				getArgsFromArray($obj->sshAttr->sshArgs));

		// TODO sudo

	return "sshpass " . $passwd . " ssh " . $sshArgs . " " . $login . "@" . $host . " "
				. getCmd($obj);
}

function parseExitCode($lines) {
	global $retJSON;

	$lastLine = $lines[count($lines) - 1];
	if(empty($lastLine)) {
		$lastLine = $lines[count($lines) - 2];
	}
	$arr = explode("=", $lastLine);

	if(trim($arr[0]) == EXIT_CODE_STR) {
		$retJSON->protocol->exitCode = trim($arr[1]);
	}
}


function parseReply($str) {
	global $protocolType_g, $retJSON;
	// split reply into an array (each line)
	$lines = explode("\r\n", $str);

	parseExitCode($lines);

	if ($retJSON->protocol->exitCode == 0) {

		for($i = 0; $i < count($lines); $i++) {

			// split each line: parameter = value
			$arr2 = explode("=", $lines[$i]);

			if($protocolType_g == SNMP_STR){
				// get cmd output
				if(strpos($arr2[0], CMD_OUTPUT_STR) !== false) {
					$retJSON->cmd->output = trim(str_replace("STRING:", "", $arr2[1]));
				}
				// get cmd result
				if(strpos($arr2[0], CMD_RESULT_STR) !== false) {
					$retJSON->cmd->exitCode = trim(str_replace("INTEGER:", "", $arr2[1]));
				}
				if($retJSON->cmd->exitCode != 0 && !empty($retJSON->cmd->output)) {
					prepareExit("Remote command error", 7);
				}
			}
			if($protocolType_g == SSH_STR) {
				// get cmd output without last two lines (connection closed and exitCode )
				if($i < count($lines) - 2) {
					$retJSON->cmd->output .= trim($lines[$i]);
				}
			}
		}
	}
}

function handleExitCode($returned, $host) {
	global $retJSON, $protocolType_g;
	$exitCode = $retJSON->protocol->exitCode;

	// remove helper appendix
	$rem = "\n" . EXIT_CODE_STR . " = " . $exitCode . "\n";
	$out = str_replace($rem, "", $returned);

	if($exitCode != 0 && $exitCode != 2 && $protocolType_g == SNMP_STR) {
		$rem = "snmpset: ";
		$out = str_replace($rem, "", $out);
		$retJSON->protocol->output = trim($out);
		prepareExit("Protocol error", 6);
	}

	if($exitCode != 0 && $protocolType_g == SSH_STR) {
		if($exitCode == 255) {
			// error in ssh
			$retJSON->protocol->output = trim($out);
			$retJSON->protocol->exitCode = $exitCode;
			prepareExit("Protocol error", 6);
		} else {
			// error in remote script
			$retJSON->cmd->output = trim($out);
			$retJSON->cmd->exitCode = $exitCode;
			prepareExit("Remote command error", 7);
		}
	}
}

function execute($cmd, $host) {
	global $retJSON, $APPENDIX_g;

	$cmd .= $APPENDIX_g;

	if(DEBUG == 1) {
		echo "COMMAND:\n".$cmd."\n";
	}

	$returned = shell_exec($cmd);

	if(DEBUG == 1) {
		echo "RETURNED:\n".$returned."\n";
	}
	parseReply($returned);

	$exitCode = $retJSON->protocol->exitCode;
	// return check (odd and even fenomenon: try again)
	if($exitCode == 2) {
		$returned = shell_exec($cmd);
		parseReply($returned);
	}

	handleExitCode($returned, $host);
}

function main() {
	// checking
	checkDependencies();

	$obj = loadJSON();
	checkExpectedJSONFormat($obj);
	checkOptionalValues($obj);

	// processing
	processValid($obj);
}

//?>
<!---->
