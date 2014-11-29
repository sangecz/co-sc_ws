<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/28/14
 * Time: 9:44 PM
 */

class Script {

    private $ps_role_id;
    private $name;
    private $description;
    private $address;
    private $contrent;
    private $protocol_id;

    function __construct()
    {
        $this->address = "";
        $this->contrent = "";
        $this->description = "";
        $this->name = "";
        $this->protocol_id = "";
        $this->ps_role_id = PS_ROLE_PRIVATE;
    }


    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->contrent;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->contrent = $content;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getProtocolId()
    {
        return $this->protocol_id;
    }

    /**
     * @param mixed $protocol_id
     */
    public function setProtocolId($protocol_id)
    {
        $this->protocol_id = $protocol_id;
    }

    /**
     * @return mixed
     */
    public function getPsRoleId()
    {
        return $this->ps_role_id;
    }

    /**
     * @param mixed $ps_role_id
     */
    public function setPsRoleId($ps_role_id)
    {
        $this->ps_role_id = $ps_role_id;
    }


} 