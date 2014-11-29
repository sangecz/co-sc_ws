<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/26/14
 * Time: 12:31 AM
 */

class Protocol {

    private $name;
    private $desc;
    private $port;
    private $version;
    private $sshArgs;
    private $level;
    private $login;
    private $passwd;
    private $privPasswd;
    private $authPasswd;
    private $authProto;
    private $privProto;
    private $community;
    private $type;
    private $role;

    function __construct()
    {
        require_once dirname(__FILE__) . '/Config.php';
        $this->authPasswd = "";
        $this->authProto = "";
        $this->community = "";
        $this->desc = "";
        $this->level = "";
        $this->login = "";
        $this->name = "";
        $this->passwd = "";
        $this->port = "";
        $this->privPasswd = "";
        $this->privProto = "";
        $this->sshArgs = "";
        $this->version = "";
        $this->type = "";
        $this->role = PS_ROLE_PRIVATE;
    }

    /**
     * Constructor
     * @param Protocol $protocol
     * @return Protocol
     */


    public static function withProtocol($protocol)
    {
        $instance = new self();
        $instance->authPasswd = $protocol->getAuthPasswd();
        $instance->authProto = $protocol->getAuthProto();
        $instance->community = $protocol->getCommunity();
        $instance->desc = $protocol->getDesc();
        $instance->level = $protocol->getLevel();
        $instance->login = $protocol->getLogin();
        $instance->name = $protocol->getName();
        $instance->passwd = $protocol->getPasswd();
        $instance->port = $protocol->getPort();
        $instance->privPasswd = $protocol->getPrivPasswd();
        $instance->privProto = $protocol->getPrivProto();
        $instance->sshArgs = $protocol->getSshArgs();
        $instance->version = $protocol->getVersion();
        $instance->type = $protocol->getType();
        $instance->role = $protocol->getRole();

        return $instance;
    }

    /**
     * @return int ps_role_id
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param int $role ps_role_id
     */
    public function setRole($role)
    {
        $this->role = $role;
    }


    /**
     * @return array
     */

    public function getArray() {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getAuthPasswd()
    {
        return $this->authPasswd;
    }

    /**
     * @param string $authPasswd
     */
    public function setAuthPasswd($authPasswd)
    {
        $this->authPasswd = $authPasswd;
    }

    /**
     * @return string
     */
    public function getAuthProto()
    {
        return $this->authProto;
    }

    /**
     * @param string $authProto
     */
    public function setAuthProto($authProto)
    {
        $this->authProto = $authProto;
    }

    /**
     * @return string
     */
    public function getCommunity()
    {
        return $this->community;
    }

    /**
     * @param string $community
     */
    public function setCommunity($community)
    {
        $this->community = $community;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPasswd()
    {
        return $this->passwd;
    }

    /**
     * @param string $passwd
     */
    public function setPasswd($passwd)
    {
        $this->passwd = $passwd;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getPrivPasswd()
    {
        return $this->privPasswd;
    }

    /**
     * @param string $privPasswd
     */
    public function setPrivPasswd($privPasswd)
    {
        $this->privPasswd = $privPasswd;
    }

    /**
     * @return string
     */
    public function getPrivProto()
    {
        return $this->privProto;
    }

    /**
     * @param string $privProto
     */
    public function setPrivProto($privProto)
    {
        $this->privProto = $privProto;
    }

    /**
     * @return string
     */
    public function getSshArgs()
    {
        return $this->sshArgs;
    }

    /**
     * @param string $sshArgs
     */
    public function setSshArgs($sshArgs)
    {
        $this->sshArgs = $sshArgs;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }



} 