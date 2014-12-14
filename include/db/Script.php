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
    private $content;
    private $protocol_id;
    private $db_id;

    function __construct()
    {
        $this->address = "";
        $this->content = "";
        $this->description = "";
        $this->name = "";
        $this->protocol_id = "";
        $this->ps_role_id = "";
    }

    /**
     * @return array
     */

    public function getArray() {
        return get_object_vars($this);
    }

    /**
     * @param $id
     * @param $ps_role_id
     * @param $name
     * @param $description
     * @param $address
     * @param $content
     * @param $protocol_id
     * @return Script
     */
    public static function withAttributes($id, $ps_role_id, $name, $description, $address,$content, $protocol_id)
    {
        $instance = new self();
        $instance->db_id = $id;
        $instance->address = $address;
        $instance->content = $content;
        $instance->description = $description;
        $instance->name = $name;
        $instance->protocol_id = $protocol_id;
        $instance->ps_role_id = $ps_role_id;

        return $instance;
    }

    /**
     * @return mixed
     */
    public function getDbId()
    {
        return intval($this->db_id);
    }

    /**
     * @param mixed $db_id
     */
    public function setDbId($db_id)
    {
        $this->db_id = $db_id;
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
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
        return intval($this->protocol_id);
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
        return intval($this->ps_role_id);
    }

    /**
     * @param mixed $ps_role_id
     */
    public function setPsRoleId($ps_role_id)
    {
        $this->ps_role_id = $ps_role_id;
    }


} 