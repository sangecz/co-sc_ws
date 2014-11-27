<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/26/14
 * Time: 11:32 AM
 */

class Response {

    private $ws;
    private $cmd;
    private $protocol;
    private $data;

    function __construct()
    {
        $this->ws = NULL;
        $this->protocol = NULL;
        $this->cmd = NULL;
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
     * @return array
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * @param string $output
     * @param int $exitCode
     */
    public function setCmd($output, $exitCode)
    {
        $this->cmd = array();
        $this->cmd ['output'] = $output;
        $this->cmd ['exitCode'] = $exitCode;
    }

    /**
     * @param array $protocol
     */
    public function setCmdArray($cmd)
    {
        $this->cmd = $cmd;
    }

    /**
     * @return array
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param array $protocol
     */
    public function setProtocolArray($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @param string $output
     * @param int $exitCode
     */
    public function setProtocol($output, $exitCode)
    {
        $this->protocol = array();
        $this->protocol['output'] = $output;
        $this->protocol['exitCode'] = $exitCode;
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


}