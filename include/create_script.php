<?php
//
//include 'common.php';
//define('CREATE_SCRIPT_NAME', 'create_co-sc.sh');
//
//function endsWith($str, $pattern) {
//	return $pattern === "" || substr($str, -strlen($pattern)) === $pattern;
//}
//
//function checkExpectedJSONFormat($obj) {
//	checkExpectedJSONFormatCommon($obj);
//
//	checkMandatoryAttribute($obj->cmd, "cmd");
//	checkMandatoryAttribute($obj->cmd->path, "cmd->path");
//	checkMandatoryAttribute($obj->cmd->baseDir, "cmd->baseDir");
//	checkMandatoryAttribute($obj->cmd->content, "cmd->content");
//}
//
//function checkOptionalValues($obj) {
//	checkOptionalValuesCommon($obj);
//}
//
//function getCmd($obj) {
//	$path = $obj->cmd->path;
//	$baseDir = $obj->cmd->baseDir;
//	$content = $obj->cmd->content;
//
//	// strip last /
//	if (endsWith($baseDir, "/")) {
//		$baseDir = substr($baseDir, 0, strlen($baseDir) - 1);
//	}
//
//	return "\"".$baseDir."/".CREATE_SCRIPT_NAME." ".$baseDir." ".$path." '".$content."'\"";
//}
//
//function processValid($obj) {
//	global $retJSON;
//	$host = $obj->host;
//	$cmdPath = $obj->cmd->path;
//
//	if($obj->protocol->type == SSH_STR) {
//
//		$execStr = constructSSHCmd($obj);
//		//echo "'$execStr'";
//		execute($execStr, $host);
//
//	} else {
//		prepareExit("Required protocol type is SSH.", 5);
//	}
//	// print reply
//	echo json_encode($retJSON);
//}
//
//main();
//
//?>
