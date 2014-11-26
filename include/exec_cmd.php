<?php
//
//include 'common.php';
//
//function checkExpectedJSONFormat($obj) {
//	checkExpectedJSONFormatCommon($obj);
//
//	checkMandatoryAttribute($obj->cmd, "cmd");
//	checkMandatoryAttribute($obj->cmd->path, "cmd->path");
//}
//
//function checkOptionalValues($obj) {
//	checkOptionalValuesCommon($obj);
//	checkSudo($obj->cmd->sudo);
//	checkArgs($obj->cmd->args, "cmd->args");
//}
//
//function getCmd($obj) {
//	$cmdPath = $obj->cmd->path;
//	$cmdArgs = trim(getArgsFromArray($obj->cmd->args));
//
//	return trim("\"" . $cmdPath . " " . $cmdArgs . "\"");
//}
//
//function processValid($obj) {
//	$cmdPath = $obj->cmd->path;
//	$host = $obj->host;
//	global $retJSON;
//
//	if($obj->protocol->type == SSH_STR) {
//		$execStr = constructSSHCmd($obj);
//		execute($execStr, $host);
//	}
//	if($obj->protocol->type == SNMP_STR) {
//
//		$version = $obj->protocol->snmpAttr->version;
//		$versionAuthHost = $version." ".getSNMPAuthPart($obj)." ".$host." ";
//
//		$cmdArgs = $obj->cmd->args;
//		$execStr = "snmpset -m +NET-SNMP-EXTEND-MIB -v ".$versionAuthHost
//					."'nsExtendStatus.\"cmd\"' = 'createAndGo' "
//					."'nsExtendCommand.\"cmd\"' = '".$cmdPath."' ";
//
//		$execStr .= "'nsExtendArgs.\"cmd\"' = '".trim(getArgsFromArray($cmdArgs))."'";
//
//		execute($execStr, $host);
//
//		// TODO sudo
//		$execStr = "snmpwalk -m +NET-SNMP-EXTEND-MIB -v".$versionAuthHost;
//		// execute remote script
//		execute($execStr . " 'nsExtendOutputFull.\"cmd\"'", $host);
//		// get script result
//		execute($execStr . " 'nsExtendResult.\"cmd\"'", $host);
//	}
//	// print reply
//	echo json_encode($retJSON);
//}
//
//// start of the script
//main();
//
//// SNMP specific
//function checkProto($proto, $default, $first, $second) {
//	if($proto && !empty($proto) && $proto != $firts && $proto != $second) {
//		prepareExit("Bad JSON value: '$proto' for 'protocol->snmpAttr->auth->authProto' not recognized, not in" .
//			" <" . strtoupper($first) . ", " . strtoupper($second) . ">.", 4);
//	}
//	if(!$proto || empty($proto)) {
//		return $default;
//	}
//	if ($proto == $first || $proto == $second) {
//		return strtoupper($proto);
//	}
//}
//
//function checkSudo($sudo) {
//	if($sudo && !empty($sudo) && is_bool($sudo) === false) {
//		prepareExit("Bad JSON value: '$sudo' for 'cmd->sudo' is not bool.", 4);
// 	}
//}
//
//function getSNMPAuthPart($obj) {
//	$version = $obj->protocol->snmpAttr->version;
//	$snmpAuthPart = "";
//
//	if($version == "1" || $version == "2c") {
//		$community = $obj->protocol->snmpAttr->auth->community;
//		$snmpAuthPart .= "-c ".$community;
//	}
//	if($version == "3") {
//		$login = $obj->protocol->snmpAttr->auth->login;
//		$port = $obj->protocol->snmpAttr->port;
//		$authPasswd = $obj->protocol->snmpAttr->auth->authPasswd;
//		$privPasswd = $obj->protocol->snmpAttr->auth->privPasswd;
//		$authProto = $obj->protocol->snmpAttr->auth->authProto;
//		$privProto = $obj->protocol->snmpAttr->auth->privProto;
//		$level = strtolower($obj->protocol->snmpAttr->auth->level);
//		$snmpAuthPart .= "-u ".$login." -A ".$authPasswd." -a ".$authProto;
//		if($level == "authpriv") {
//			$snmpAuthPart .= " -X ".$privPasswd." -x ".$privProto;
//		}
//		$snmpAuthPart .= " -l ".$level;
//	}
//	return $snmpAuthPart;
//}
//
//?>
