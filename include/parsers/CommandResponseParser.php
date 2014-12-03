<?php
///**
// * Created by PhpStorm.
// * User: sange
// * Date: 11/30/14
// * Time: 1:58 PM
// */
//
//class CommandResponseParser {
//
//    private $protocolType;
//    private $response;
//
//    function __construct($type)
//    {
//        require_once  APP_PATH . '/resp/Responder.php';
//        require_once  APP_PATH . '/resp/Response.php';
//        $this->response = new Response();
//        $this->protocolType = $type;
//    }
//
//    /**
//     * @return Response
//     */
//    public function getResponse()
//    {
//        return $this->response;
//    }
//
//
//    /**
//     * @param String $returned
//     */
//    public function parse($returned) {
//        // split reply into an array (each line)
//        $returned = str_replace("\r", "", $returned);
//        $lines = explode("\n", $returned);
//        if(DEBUG == 1) {
//            echo "LINES::\n";
//            var_dump($lines);
//        }
//
//        $lines = $this->parseExitCode($lines);
//        $exitCode = $this->response->getData()['exitCode'];
//
//        if(DEBUG == 1) {
//            echo "LINES2::\n";
//            var_dump($lines);
//        }
//
//        if(DEBUG == 1) echo "ExitCode=" . $exitCode. "\n";
//
//        if ($exitCode == 0) {
//            $cmdOutput = "";
//
//            for($i = 0; $i < count($lines); $i++) {
//
//                // split each line: parameter = value
//                $arr2 = explode("=", $lines[$i]);
//
//                if($this->protocolType == SNMP_STR){
//                    // get cmd output
//                    if(strpos($arr2[0], CMD_OUTPUT_STR) !== false) {
//                        $cmdOutput = trim(str_replace("STRING:", "", $arr2[1]));
//                    }
//                    // get cmd result
//                    if(strpos($arr2[0], CMD_RESULT_STR) !== false) {
//                        $exitCode = trim(str_replace("INTEGER:", "", $arr2[1]));
//                    }
//                    if($exitCode != 0 && !empty($cmdOutput)) {
//                        $msg = "Remote command error";
//                        $this->response->setWs(WS_CODE_EXECUTE_ERR, $msg, true);
//                    }
//                }
//                if($this->protocolType == SSH_STR) {
//                    // get cmd output without last two lines (connection closed and exitCode )
//                        $cmdOutput .= trim($lines[$i]) . "\n";
//                }
//            }
//
//            $this->response->setCmd($cmdOutput, $exitCode );
//        }
//
//        return implode("\n", $lines);
//    }
//
//    /**
//     * @param $lines
//     * @return string
//     */
//    private function parseExitCode($lines) {
//        $lastLine = $lines[count($lines) - 1];
//        $pop = 1;
//        if(empty($lastLine)) {
//            $lastLine = $lines[count($lines) - 2];
//            $pop++;
//        }
//        $arr = explode("=", $lastLine);
//
//        if(trim($arr[0]) == EXIT_CODE_STR) {
//            $this->response->setExitCode(trim($arr[1]));
//        }
//
//        // remove last line(s) from output array
//        while ($pop-- > 0) array_pop($lines);
//
//        return $lines;
//    }
//
//    /**
//     * @param $returned
//     */
//    public function handleExitCode($returned) {
//        $exitCode = $this->response->getExitCode();
//
//        if(DEBUG == 1) {
//            echo "::handleExitCode\n";
//            var_dump($returned);
//        }
//
//        if($exitCode != 0 && $exitCode != 2 && $this->protocolType == SNMP_STR) {
//            $msg = "Protocol error";
//            $this->response->setWs(WS_CODE_EXECUTE_ERR, $msg, true);
//            $this->response->setCmd(trim($returned), $exitCode);
//        }
//
//        if($exitCode != 0 && $this->protocolType == SSH_STR) {
//            //  ssh man:ssh exits with the exit status of the remote command or with 255 if an error occurred.
//            // error in ssh
//            $msg = "An error has occurred";
//            $this->response->setWs(WS_CODE_EXECUTE_ERR, $msg, true);
//            $this->response->setCmd(trim($returned), $exitCode);
//        }
//
//        if(DEBUG == 1) {
//            var_dump($this->response);
//        }
//    }
//}