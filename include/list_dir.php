<?php
//
//include 'common.php';
//
//function checkExpectedJSONFormat($obj) {
//	checkExpectedJSONFormatCommon($obj);
//}
//
//function checkOptionalValues($obj) {
//	checkOptionalValuesCommon($obj);
//}
//
//function getCmd($obj) {
//	$cmdPath = $obj->cmd->path;
//
//	return "\"find ".$cmdPath." -maxdepth 1 -executable -type f" . "\"";
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
