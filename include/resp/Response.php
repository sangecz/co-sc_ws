<?php

/**
 * Responder class responses via slim framework instance to a client.
 * Response is JSON, It's a singleton.
 *
 * @author Petr Marek
 * @license Apache 2.0 http://www.apache.org/licenses/LICENSE-2.0
 */
class Response {

    /**
     * @var JSON web service (application) part of response
     */
    private $ws;

    /**
     * @var JSON data part of response
     */
    private $data;

    /**
     * Constructor sets JSON parts of response to default: null.
     */
    function __construct()
    {
        $this->ws = NULL;
        $this->data = NULL;
    }

    /**
     * Serializes Response - creates array even from private class members.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $objectArray = [];
        foreach($this as $key => $value) {
            $objectArray[$key] = $value;
        }

        return $objectArray;
    }

    /**
     * JSON Data getter.
     *
     * @return JSON|NULL
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * JSON data setter.
     *
     * @param JSON $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Sets script output for a client.
     *
     * @param String $output Output message of the executed script
     * @param int $exitCode exit code of the executed script
     */
    public function setCmd($output, $exitCode)
    {
        $this->data = array();
        $this->data ['scriptOutput'] = $output;
        $this->data ['exitCode'] = $exitCode;
    }

    /**
     * Getter of WS JSON part of response.
     *
     * @return array
     */
    public function getWs()
    {
        return $this->ws;
    }

    /**
     * Setter of WS JSON part of response.
     *
     * @param int $wsCode Web service (app) exit code from Config.php
     * @param String $msg Web service output message
     * @param bool $err Web service error [true|false] for easier client app handling
     */
    public function setWs($wsCode, $msg, $err)
    {
        $this->ws = array();
        $this->ws['statusCode'] = $wsCode;
        $this->ws['message'] = $msg;
        $this->ws['error'] = $err;
    }

    /**
     * Returns exit code of the executed script, default=NULL
     *
     * @return int|NULL exit code of the executed script
     */
    public function getExitCode(){
        if($this->data != NULL) {
            return $this->data['exitCode'];
        } else {
            return NULL;
        }
    }

    /**
     * Sets exit code of the executed script, default=NULL
     *
     * @param int $exitCode exit code of the executed script
     */
    public function setExitCode($exitCode){
        if($this->data == NULL) {
            $this->data = array();
        }
        $this->data['exitCode'] = $exitCode;
        $this->data['scriptOutput'] = NULL;
    }



}