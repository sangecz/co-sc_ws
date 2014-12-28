<?php

/**
 * Class Protocol is a container for JSON object Protocol. It provides setters and getters for its
 * class members (Protocol attributes). It stores both SSH and SNMP.
 *
 * auth = SNMP authentication,
 *
 * priv = SNMP encryption
 *
 * @author Petr Marek
 * @license Apache 2.0 http://www.apache.org/licenses/LICENSE-2.0
 */
class Protocol {

    /**
     * @var string name
     */
    private $name;

    /**
     * @var string description
     */
    private $desc;

    /**
     * @var int SNMP (default=161) or SSH (default=22) port number
     */
    private $port;

    /**
     * @var string SNMP version [1 | 2c | 3]
     */
    private $version;

    /**
     * @var string additional SSH arguments
     */
    private $sshArgs;

    /**
     * @var string SNMP auth level [noAuthNoPriv | authNoPriv | authPriv];
     *
     * noAuthNoPriv: for SNMPv1 and SNMPv2c - no security, only community string;
     *
     * authNoPriv: for SNMPv3 auth proto available - authentication;
     *
     * authPriv: for SNMPv3 auth proto and priv proto available - authentication and encryption
     */
    private $level;

    /**
     * @var string SNMP or SSH login
     */
    private $login;

    /**
     * @var string SSH password
     */
    private $passwd;

    /**
     * @var string SNMP priv password
     */
    private $privPasswd;

    /**
     * @var string SNMP auth password
     */
    private $authPasswd;

    /**
     * @var string auth proto [AES|DES] default=AES
     */
    private $authProto;

    /**
     * @var string priv proto [MD5|SHA] default=SHA
     */
    private $privProto;

    /**
     * @var string SNMPv1 and SNMPv2c community
     */
    private $community;

    /**
     * @var string [SNMP|SSH]
     */
    private $type;

    /**
     * @var int protocol-script role-visibility [1 | 2] (public=1, private 2)
     */
    private $role;

    /**
     * @var int DB row id
     */
    private $db_id;


    /**
     * Sets every class member to an empty string.
     */
    function __construct()
    {
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
        $this->role = "";

    }

    /**
     * Helper method - constructor. Arguments matches class members.
     *
     * @param $id
     * @param $name
     * @param $description
     * @param $ps_role_id
     * @param $port
     * @param $version
     * @param $sshArgs
     * @param $level
     * @param $passwd
     * @param $login
     * @param $authPasswd
     * @param $privPasswd
     * @param $privProto
     * @param $authProto
     * @param $community
     * @param $protocol_type_id
     * @param $created_at
     * @return Protocol
     */
    public static function withAttributes($id,	 $name, $description, $ps_role_id, $port, $version, $sshArgs, $level, $passwd,
                                          $login, $authPasswd, $privPasswd, $privProto, $authProto, $community, $protocol_type_id, $created_at)
    {
        $instance = new self();
        $instance->authPasswd = $authPasswd;
        $instance->authProto = $authProto;
        $instance->community = $community;
        $instance->desc = $description;
        $instance->level = $level;
        $instance->login = $login;
        $instance->name = $name;
        $instance->passwd = $passwd;
        $instance->port = $port;
        $instance->privPasswd = $privPasswd;
        $instance->privProto = $privProto;
        $instance->sshArgs = $sshArgs;
        $instance->version = $version;
        $instance->type = $protocol_type_id;
        $instance->role = $ps_role_id;
        $instance->db_id = $id;

        return $instance;
    }

    /**
     * @return mixed
     */
    public function getDbId()
    {
        return $this->db_id;
    }

    /**
     * @param mixed $db_id
     */
    public function setDbId($db_id)
    {
        $this->db_id = $db_id;
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
     * Encrypts SNMP auth password
     *
     * @param string $authPasswd
     */
    public function setAuthPasswd($authPasswd)
    {
        $this->authPasswd = PassHash::encrypt($authPasswd);
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
     *  Encrypts SSH password
     *
     * @param string $passwd
     */
    public function setPasswd($passwd)
    {
        $this->passwd = PassHash::encrypt($passwd);
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
     *  Encrypts SNMP priv password
     *
     * @param string $privPasswd
     */
    public function setPrivPasswd($privPasswd)
    {
        $this->privPasswd = PassHash::encrypt($privPasswd);
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