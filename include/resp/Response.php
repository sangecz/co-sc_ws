<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/26/14
 * Time: 11:32 AM
 */

class Response {

    private $ws;
    private $data;

    function __construct()
    {
        $this->ws = NULL;
        $this->data = NULL;
    }

    public function jsonSerialize()
    {
        $objectArray = [];
        foreach($this as $key => $value) {
            $objectArray[$key] = $value;
        }

        return $objectArray;
    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $output
     */
    public function setCmd($output, $exitCode)
    {
        $this->data = array();
        $this->data ['scriptOutput'] = $output;
        $this->data ['exitCode'] = $exitCode;
    }

    /**
     * @return array
     */
    public function getWs()
    {
        return $this->ws;
    }

    /**
     * @param int $statusCode
     * @param String $msg
     * @param bool $err
     */
    public function setWs($wsCode, $msg, $err)
    {
        $this->ws = array();
        $this->ws['statusCode'] = $wsCode;
        $this->ws['message'] = $msg;
        $this->ws['error'] = $err;
    }

    public function getExitCode(){
        if($this->data != NULL) {
            return $this->data['exitCode'];
        } else {
            return NULL;
        }
    }

    /**
     * @param $statusCode
     */
    public function setExitCode($exitCode){
        if($this->data == NULL) {
            $this->data = array();
        }
        $this->data['exitCode'] = $exitCode;
        $this->data['scriptOutput'] = NULL;
    }



}